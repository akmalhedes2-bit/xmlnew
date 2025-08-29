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
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        // Fallback to POST parameters
        $uid = isset($_POST['uid']) ? intval($_POST['uid']) : 0;
        $day = isset($_POST['day']) ? intval($_POST['day']) : 0;
    } else {
        $uid = isset($input['uid']) ? intval($input['uid']) : 0;
        $day = isset($input['day']) ? intval($input['day']) : 0;
    }
    
    if ($uid <= 0 || $day <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid UID or day'
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
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Get current active season
    $stmt = $pdo->prepare("SELECT id FROM battlepass_seasons WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $season = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$season) {
        $pdo->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'No active battle pass season'
        ]);
        exit;
    }
    
    $seasonId = $season['id'];
    
    // Get reward info
    $stmt = $pdo->prepare("SELECT * FROM battlepass_rewards WHERE season_id = ? AND day = ? LIMIT 1");
    $stmt->execute([$seasonId, $day]);
    $reward = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reward) {
        $pdo->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'No reward found for this day'
        ]);
        exit;
    }
    
    // Get user progress
    $stmt = $pdo->prepare("SELECT * FROM user_battlepass_progress WHERE uid = ? AND season_id = ? LIMIT 1");
    $stmt->execute([$uid, $seasonId]);
    $progress = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$progress) {
        $pdo->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'User not registered for battle pass'
        ]);
        exit;
    }
    
    // Parse claimed days
    $claimedDays = json_decode($progress['claimed_days'], true) ?: [];
    
    // Validate claim
    if (in_array($day, $claimedDays)) {
        $pdo->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Reward already claimed for this day'
        ]);
        exit;
    }
    
    if ($day > $progress['current_day']) {
        $pdo->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Cannot claim future rewards'
        ]);
        exit;
    }
    
    // Add day to claimed days
    $claimedDays[] = $day;
    $newCurrentDay = $progress['current_day'];
    
    // If claiming current day, advance to next day
    if ($day == $progress['current_day'] && $progress['current_day'] < 30) {
        $newCurrentDay = $progress['current_day'] + 1;
    }
    
    // Update user progress
    $stmt = $pdo->prepare("UPDATE user_battlepass_progress SET current_day = ?, claimed_days = ?, last_claim_date = NOW() WHERE uid = ? AND season_id = ?");
    $stmt->execute([$newCurrentDay, json_encode($claimedDays), $uid, $seasonId]);
    
    // Log the claim (optional - for tracking)
    $stmt = $pdo->prepare("INSERT INTO battlepass_claims (uid, season_id, day, reward_type, reward_value, claimed_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$uid, $seasonId, $day, $reward['item_type'], $reward['reward_value']]);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully claimed {$reward['item_name']}!",
        'reward' => [
            'day' => $reward['day'],
            'item_name' => $reward['item_name'],
            'item_type' => $reward['item_type'],
            'reward_value' => $reward['reward_value'],
            'icon' => $reward['icon']
        ],
        'new_day' => $newCurrentDay
    ]);
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollback();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>