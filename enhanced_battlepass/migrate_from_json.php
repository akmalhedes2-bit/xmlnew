<?php
// Migration script untuk convert existing JSON data ke MySQL database
// Run script ini sekali sahaja untuk migrate existing data

ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // Paths to existing JSON files - adjust sesuai struktur anda
    $rewardFile = '../data/battlepassreward.json';  // Adjust path
    $logFile = '../data/battlepasslog.json';        // Adjust path
    
    // Database connection
    $host = 'localhost';
    $dbname = 'ryl_weekly_battlepass';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Migration from JSON to MySQL Database</h2>\n";
    
    // Start transaction
    $pdo->beginTransaction();
    
    // 1. Migrate rewards dari JSON
    if (file_exists($rewardFile)) {
        $rewards = json_decode(file_get_contents($rewardFile), true);
        
        if ($rewards && is_array($rewards)) {
            echo "<h3>Migrating Rewards...</h3>\n";
            
            // Get or create current season
            $weekNum = date('W');
            $year = date('Y');
            $startDate = date('Y-m-d 00:00:00', strtotime('monday this week'));
            $endDate = date('Y-m-d 23:59:59', strtotime('sunday this week'));
            
            $stmt = $pdo->prepare("INSERT INTO weekly_seasons (week_number, name, start_date, end_date, is_active) VALUES (?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)");
            $stmt->execute([$weekNum, "Week $weekNum - $year", $startDate, $endDate]);
            $seasonId = $pdo->lastInsertId();
            
            foreach ($rewards as $reward) {
                $stmt = $pdo->prepare("INSERT INTO weekly_rewards (season_id, day, item_name, item_prototype_id, amount, point, photo_url) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE item_name = VALUES(item_name)");
                $stmt->execute([
                    $seasonId,
                    $reward['Day'],
                    $reward['ItemName'],
                    $reward['ItemPrototypeID'],
                    intval($reward['Amount']),
                    intval($reward['Point']),
                    $reward['Photo_URL']
                ]);
                echo "Migrated: Day {$reward['Day']} - {$reward['ItemName']}<br>\n";
            }
        }
    } else {
        echo "<p>Reward file not found: $rewardFile</p>\n";
    }
    
    // 2. Migrate logs (user progress) dari JSON
    if (file_exists($logFile)) {
        $logs = json_decode(file_get_contents($logFile), true);
        
        if ($logs && is_array($logs)) {
            echo "<h3>Migrating User Progress...</h3>\n";
            
            foreach ($logs as $uid => $userLogs) {
                if (is_array($userLogs)) {
                    $claimedDays = [];
                    $lastClaimDate = null;
                    
                    foreach ($userLogs as $log) {
                        if (isset($log['Day'])) {
                            $claimedDays[] = $log['Day'];
                        }
                        if (isset($log['Time']) && ($lastClaimDate === null || $log['Time'] > $lastClaimDate)) {
                            $lastClaimDate = $log['Time'];
                        }
                    }
                    
                    // Insert user progress
                    $stmt = $pdo->prepare("INSERT INTO user_weekly_progress (uid, season_id, current_day, claimed_days, last_claim_date, online_time_minutes) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE claimed_days = VALUES(claimed_days)");
                    $stmt->execute([
                        intval($uid),
                        $seasonId,
                        (int)date('N'), // Current day of week
                        json_encode($claimedDays),
                        $lastClaimDate,
                        0 // Reset online time
                    ]);
                    
                    echo "Migrated user progress for UID: $uid (Claimed: " . implode(',', $claimedDays) . ")<br>\n";
                }
            }
        }
    } else {
        echo "<p>Log file not found: $logFile</p>\n";
    }
    
    // Commit transaction
    $pdo->commit();
    echo "<h3>✅ Migration completed successfully!</h3>\n";
    echo "<p>Database is now ready to use.</p>\n";
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollback();
    }
    echo "<h3>❌ Migration failed!</h3>\n";
    echo "<p>Error: " . $e->getMessage() . "</p>\n";
}
?>