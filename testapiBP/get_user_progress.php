<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

try {
    // Get UID from query parameter
    $uid = isset($_GET['uid']) ? intval($_GET['uid']) : 0;
    
    if ($uid <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid UID'
        ]);
        exit;
    }
    
    // Database connection
    $host = 'localhost';
    $dbname = 'ryl_battlepass';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get current active season
    $stmt = $pdo->prepare("SELECT id FROM battlepass_seasons WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $season = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$season) {
        echo json_encode([
            'success' => false,
            'message' => 'No active battle pass season'
        ]);
        exit;
    }
    
    $seasonId = $season['id'];
    
    // Get user progress
    $stmt = $pdo->prepare("SELECT * FROM user_battlepass_progress WHERE uid = ? AND season_id = ? LIMIT 1");
    $stmt->execute([$uid, $seasonId]);
    $progress = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$progress) {
        // Create new progress for user
        $stmt = $pdo->prepare("INSERT INTO user_battlepass_progress (uid, season_id, current_day, claimed_days, last_claim_date, created_at) VALUES (?, ?, 1, '[]', NULL, NOW())");
        $stmt->execute([$uid, $seasonId]);
        
        echo json_encode([
            'success' => true,
            'progress' => [
                'uid' => $uid,
                'season_id' => $seasonId,
                'current_day' => 1,
                'claimed_days' => [],
                'last_claim_date' => null,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        // Parse claimed days JSON
        $claimedDays = json_decode($progress['claimed_days'], true) ?: [];
        
        echo json_encode([
            'success' => true,
            'progress' => [
                'uid' => $progress['uid'],
                'season_id' => $progress['season_id'],
                'current_day' => $progress['current_day'],
                'claimed_days' => $claimedDays,
                'last_claim_date' => $progress['last_claim_date'],
                'created_at' => $progress['created_at']
            ]
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>