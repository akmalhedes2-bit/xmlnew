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
    // Database connection - adjust credentials sesuai VPS anda
    $host = 'localhost';
    $dbname = 'ryl_battlepass';  // Create database ni dalam MySQL
    $username = 'root';          // Adjust username
    $password = '';              // Adjust password
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get current active season
    $stmt = $pdo->prepare("SELECT * FROM battlepass_seasons WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $season = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$season) {
        // Create default season if none exists
        $startDate = date('Y-m-d H:i:s');
        $endDate = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        // Insert new season
        $stmt = $pdo->prepare("INSERT INTO battlepass_seasons (season_number, name, start_date, end_date, is_active, created_at) VALUES (1, 'Season 1 - Genesis', ?, ?, 1, NOW())");
        $stmt->execute([$startDate, $endDate]);
        $seasonId = $pdo->lastInsertId();
        
        // Create 30 days rewards
        $rewards = [];
        for ($day = 1; $day <= 30; $day++) {
            if ($day % 7 == 0) { // Weekly bonus
                $itemType = 'cash';
                $value = $day * 10;
                $icon = '💰';
                $description = "Weekly Bonus: $value Cash";
            } elseif ($day % 5 == 0) { // Every 5th day points
                $itemType = 'points';
                $value = $day * 15;
                $icon = '⭐';
                $description = "Bonus Points: $value Points";
            } else {
                $itemType = 'item';
                $value = 1;
                $icon = '🎁';
                $description = 'Daily Reward Item';
            }
            
            $stmt = $pdo->prepare("INSERT INTO battlepass_rewards (season_id, day, item_name, item_type, reward_value, icon, description, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$seasonId, $day, "Day $day Reward", $itemType, $value, $icon, $description]);
            
            $rewards[] = [
                'day' => $day,
                'item_name' => "Day $day Reward",
                'item_type' => $itemType,
                'reward_value' => $value,
                'icon' => $icon,
                'description' => $description
            ];
        }
        
        // Return new season data
        echo json_encode([
            'success' => true,
            'season' => [
                'id' => $seasonId,
                'season_number' => 1,
                'name' => 'Season 1 - Genesis',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_active' => true,
                'rewards' => $rewards
            ]
        ]);
    } else {
        // Get existing season rewards
        $stmt = $pdo->prepare("SELECT * FROM battlepass_rewards WHERE season_id = ? ORDER BY day ASC");
        $stmt->execute([$season['id']]);
        $rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'season' => [
                'id' => $season['id'],
                'season_number' => $season['season_number'],
                'name' => $season['name'],
                'start_date' => $season['start_date'],
                'end_date' => $season['end_date'],
                'is_active' => (bool)$season['is_active'],
                'rewards' => $rewards
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