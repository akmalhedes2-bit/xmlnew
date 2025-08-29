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
    // Get UID from parameter
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
    $dbname = 'ryl_weekly_battlepass';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get current active season
    $stmt = $pdo->prepare("SELECT id FROM weekly_seasons WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $season = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$season) {
        echo json_encode([
            'success' => false,
            'message' => 'No active weekly season'
        ]);
        exit;
    }
    
    $seasonId = $season['id'];
    
    // Get user progress
    $stmt = $pdo->prepare("SELECT * FROM user_weekly_progress WHERE uid = ? AND season_id = ? LIMIT 1");
    $stmt->execute([$uid, $seasonId]);
    $progress = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$progress) {
        // Create new progress for user
        $currentDay = (int)date('N'); // 1=Monday, 7=Sunday
        $stmt = $pdo->prepare("INSERT INTO user_weekly_progress (uid, season_id, current_day, claimed_days, online_time_minutes) VALUES (?, ?, ?, '[]', 0)");
        $stmt->execute([$uid, $seasonId, $currentDay]);
        
        $progress = [
            'uid' => $uid,
            'season_id' => $seasonId,
            'current_day' => $currentDay,
            'claimed_days' => [],
            'last_claim_date' => null,
            'online_time_minutes' => 0
        ];
    } else {
        // Update current day based on today
        $currentDay = (int)date('N');
        if ($progress['current_day'] != $currentDay) {
            $stmt = $pdo->prepare("UPDATE user_weekly_progress SET current_day = ? WHERE uid = ? AND season_id = ?");
            $stmt->execute([$currentDay, $uid, $seasonId]);
            $progress['current_day'] = $currentDay;
        }
        
        // Parse claimed days
        $progress['claimed_days'] = json_decode($progress['claimed_days'], true) ?: [];
    }
    
    echo json_encode([
        'success' => true,
        'progress' => $progress
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>