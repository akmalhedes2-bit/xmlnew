-- Weekly Battle Pass Database Setup
-- Compatible dengan existing JSON structure

CREATE DATABASE IF NOT EXISTS ryl_weekly_battlepass CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ryl_weekly_battlepass;

-- Table untuk weekly seasons
CREATE TABLE IF NOT EXISTS weekly_seasons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    week_number INT NOT NULL,
    name VARCHAR(255) NOT NULL DEFAULT 'Weekly Pass',
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_active (is_active),
    INDEX idx_week_number (week_number)
);

-- Table untuk weekly rewards (based on your JSON)
CREATE TABLE IF NOT EXISTS weekly_rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    season_id INT NOT NULL,
    day INT NOT NULL CHECK (day >= 1 AND day <= 7),
    item_name VARCHAR(255) NOT NULL,
    item_prototype_id VARCHAR(50) NOT NULL,
    amount INT NOT NULL DEFAULT 1,
    point INT NOT NULL DEFAULT 0,
    photo_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (season_id) REFERENCES weekly_seasons(id) ON DELETE CASCADE,
    INDEX idx_season_day (season_id, day),
    UNIQUE KEY unique_season_day (season_id, day)
);

-- Table untuk user weekly progress
CREATE TABLE IF NOT EXISTS user_weekly_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uid INT NOT NULL,
    season_id INT NOT NULL,
    current_day INT NOT NULL DEFAULT 1,
    claimed_days JSON, -- Array of claimed days [1,2,3...]
    last_claim_date DATETIME NULL,
    online_time_minutes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (season_id) REFERENCES weekly_seasons(id) ON DELETE CASCADE,
    INDEX idx_uid_season (uid, season_id),
    UNIQUE KEY unique_user_season (uid, season_id)
);

-- Table untuk weekly claims history
CREATE TABLE IF NOT EXISTS weekly_claims (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uid INT NOT NULL,
    season_id INT NOT NULL,
    day INT NOT NULL,
    item_prototype_id VARCHAR(50) NOT NULL,
    amount INT NOT NULL,
    cid VARCHAR(50), -- Character ID yang receive item
    claimed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (season_id) REFERENCES weekly_seasons(id) ON DELETE CASCADE,
    INDEX idx_uid (uid),
    INDEX idx_season (season_id),
    INDEX idx_claimed_date (claimed_at)
);

-- Insert default current week season
INSERT INTO weekly_seasons (week_number, name, start_date, end_date, is_active) 
VALUES (
    WEEK(NOW()), 
    CONCAT('Week ', WEEK(NOW()), ' - ', YEAR(NOW())), 
    DATE_SUB(NOW(), INTERVAL WEEKDAY(NOW()) DAY),
    DATE_ADD(DATE_SUB(NOW(), INTERVAL WEEKDAY(NOW()) DAY), INTERVAL 6 DAY),
    1
) ON DUPLICATE KEY UPDATE name = name;

-- Get the season ID
SET @season_id = LAST_INSERT_ID();

-- Insert your existing rewards (from JSON)
INSERT INTO weekly_rewards (season_id, day, item_name, item_prototype_id, amount, point, photo_url) VALUES
(@season_id, 1, 'Ruby', '2005', 20, 10, 'files/images/item/ruby2.png'),
(@season_id, 2, 'Sapphire', '2005', 20, 10, 'files/images/item/sapphire.png'),
(@season_id, 3, 'Emerald', '2005', 20, 10, 'files/images/item/emerald.png'),
(@season_id, 4, 'Diamond', '2005', 20, 10, 'files/images/item/diamond.png'),
(@season_id, 5, 'Gold Coin', '2005', 20, 10, 'files/images/item/orbexp.jpg'),
(@season_id, 6, 'Silver Coin', '2005', 20, 10, 'files/images/item/1212.gif'),
(@season_id, 7, 'Mystery Box', '2005', 20, 10, 'files/images/item/pumkin.jpg');

-- Show created tables
SHOW TABLES;