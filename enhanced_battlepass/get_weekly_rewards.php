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
    // Database connection - adjust sesuai VPS anda
    $host = 'localhost';
    $dbname = 'ryl_weekly_battlepass';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get current active week season
    $stmt = $pdo->prepare("SELECT * FROM weekly_seasons WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $season = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$season) {
        // Create new week if none exists
        $weekNum = date('W');
        $year = date('Y');
        $startDate = date('Y-m-d 00:00:00', strtotime('monday this week'));
        $endDate = date('Y-m-d 23:59:59', strtotime('sunday this week'));
        
        $stmt = $pdo->prepare("INSERT INTO weekly_seasons (week_number, name, start_date, end_date, is_active) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$weekNum, "Week $weekNum - $year", $startDate, $endDate]);
        $seasonId = $pdo->lastInsertId();
        
        // Insert default rewards (dari JSON anda)
        $defaultRewards = [
            [1, 'Ruby', '2005', 20, 10, 'files/images/item/ruby2.png'],
            [2, 'Sapphire', '2005', 20, 10, 'files/images/item/sapphire.png'],
            [3, 'Emerald', '2005', 20, 10, 'files/images/item/emerald.png'],
            [4, 'Diamond', '2005', 20, 10, 'files/images/item/diamond.png'],
            [5, 'Gold Coin', '2005', 20, 10, 'files/images/item/orbexp.jpg'],
            [6, 'Silver Coin', '2005', 20, 10, 'files/images/item/1212.gif'],
            [7, 'Mystery Box', '2005', 20, 10, 'files/images/item/pumkin.jpg']
        ];
        
        foreach ($defaultRewards as $reward) {
            $stmt = $pdo->prepare("INSERT INTO weekly_rewards (season_id, day, item_name, item_prototype_id, amount, point, photo_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$seasonId, $reward[0], $reward[1], $reward[2], $reward[3], $reward[4], $reward[5]]);
        }
        
        $season = [
            'id' => $seasonId,
            'week_number' => $weekNum,
            'name' => "Week $weekNum - $year",
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_active' => 1
        ];
    }
    
    // Get rewards untuk season ini
    $stmt = $pdo->prepare("SELECT * FROM weekly_rewards WHERE season_id = ? ORDER BY day ASC");
    $stmt->execute([$season['id']]);
    $rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format response compatible dengan existing JSON structure
    $formattedRewards = [];
    foreach ($rewards as $reward) {
        $formattedRewards[] = [
            'Day' => $reward['day'],
            'ItemName' => $reward['item_name'],
            'ItemPrototypeID' => $reward['item_prototype_id'],
            'Amount' => (string)$reward['amount'],
            'Point' => (string)$reward['point'],
            'Photo_URL' => $reward['photo_url']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'season' => $season,
        'rewards' => $formattedRewards
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>