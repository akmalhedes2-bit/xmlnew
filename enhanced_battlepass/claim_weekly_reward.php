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
    // Get POST data - compatible dengan existing AJAX call
    $uid = isset($_POST['uid']) ? intval($_POST['uid']) : 0;
    $day = isset($_POST['day']) ? intval($_POST['day']) : 0;
    $cid = isset($_POST['cid']) ? $_POST['cid'] : '';
    
    if ($uid <= 0 || $day <= 0 || empty($cid)) {
        echo json_encode([
            'success' => false,
            'error' => 'Missing required parameters: uid, day, or cid'
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
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Get current active season
    $stmt = $pdo->prepare("SELECT id FROM weekly_seasons WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $season = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$season) {
        $pdo->rollback();
        echo json_encode([
            'success' => false,
            'error' => 'No active weekly season'
        ]);
        exit;
    }
    
    $seasonId = $season['id'];
    
    // Get reward info
    $stmt = $pdo->prepare("SELECT * FROM weekly_rewards WHERE season_id = ? AND day = ? LIMIT 1");
    $stmt->execute([$seasonId, $day]);
    $reward = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reward) {
        $pdo->rollback();
        echo json_encode([
            'success' => false,
            'error' => 'No reward found for this day'
        ]);
        exit;
    }
    
    // Get user progress
    $stmt = $pdo->prepare("SELECT * FROM user_weekly_progress WHERE uid = ? AND season_id = ? LIMIT 1");
    $stmt->execute([$uid, $seasonId]);
    $progress = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$progress) {
        // Create new progress
        $currentDay = (int)date('N');
        $stmt = $pdo->prepare("INSERT INTO user_weekly_progress (uid, season_id, current_day, claimed_days, online_time_minutes) VALUES (?, ?, ?, '[]', 0)");
        $stmt->execute([$uid, $seasonId, $currentDay]);
        $progress = [
            'uid' => $uid,
            'season_id' => $seasonId,
            'current_day' => $currentDay,
            'claimed_days' => '[]',
            'online_time_minutes' => 0
        ];
    }
    
    // Parse claimed days
    $claimedDays = json_decode($progress['claimed_days'], true) ?: [];
    
    // Check if already claimed
    if (in_array($day, $claimedDays)) {
        $pdo->rollback();
        echo json_encode([
            'success' => false,
            'error' => 'Reward already claimed for this day'
        ]);
        exit;
    }
    
    // Check if day is available (current day or past days in this week)
    $currentDay = (int)date('N');
    if ($day > $currentDay) {
        $pdo->rollback();
        echo json_encode([
            'success' => false,
            'error' => 'Cannot claim future rewards'
        ]);
        exit;
    }
    
    // Add to claimed days
    $claimedDays[] = $day;
    
    // Update user progress
    $stmt = $pdo->prepare("UPDATE user_weekly_progress SET claimed_days = ?, last_claim_date = NOW() WHERE uid = ? AND season_id = ?");
    $stmt->execute([json_encode($claimedDays), $uid, $seasonId]);
    
    // Log the claim
    $stmt = $pdo->prepare("INSERT INTO weekly_claims (uid, season_id, day, item_prototype_id, amount, cid) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$uid, $seasonId, $day, $reward['item_prototype_id'], $reward['amount'], $cid]);
    
    // TODO: Add item to character inventory (integrate dengan existing item system)
    // This depends on your existing game database structure
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully claimed {$reward['item_name']}!",
        'reward' => [
            'day' => $day,
            'item_name' => $reward['item_name'],
            'item_prototype_id' => $reward['item_prototype_id'],
            'amount' => $reward['amount'],
            'photo_url' => $reward['photo_url']
        ]
    ]);
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollback();
    }
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
?>