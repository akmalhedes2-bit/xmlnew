-- Database setup untuk Battle Pass system
-- Run script ni dalam MySQL untuk create tables

-- Create database
CREATE DATABASE IF NOT EXISTS ryl_battlepass CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ryl_battlepass;

-- Table untuk seasons
CREATE TABLE IF NOT EXISTS battlepass_seasons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    season_number INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_active (is_active),
    INDEX idx_season_number (season_number)
);

-- Table untuk rewards
CREATE TABLE IF NOT EXISTS battlepass_rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    season_id INT NOT NULL,
    day INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    item_type ENUM('points', 'cash', 'item') NOT NULL,
    reward_value INT NOT NULL DEFAULT 1,
    icon VARCHAR(10) NOT NULL DEFAULT '🎁',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (season_id) REFERENCES battlepass_seasons(id) ON DELETE CASCADE,
    INDEX idx_season_day (season_id, day),
    UNIQUE KEY unique_season_day (season_id, day)
);

-- Table untuk user progress
CREATE TABLE IF NOT EXISTS user_battlepass_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uid INT NOT NULL,
    season_id INT NOT NULL,
    current_day INT NOT NULL DEFAULT 1,
    claimed_days JSON, -- Array of claimed day numbers
    last_claim_date DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (season_id) REFERENCES battlepass_seasons(id) ON DELETE CASCADE,
    INDEX idx_uid_season (uid, season_id),
    UNIQUE KEY unique_user_season (uid, season_id)
);

-- Table untuk tracking claims (optional - for analytics)
CREATE TABLE IF NOT EXISTS battlepass_claims (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uid INT NOT NULL,
    season_id INT NOT NULL,
    day INT NOT NULL,
    reward_type ENUM('points', 'cash', 'item') NOT NULL,
    reward_value INT NOT NULL,
    claimed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (season_id) REFERENCES battlepass_seasons(id) ON DELETE CASCADE,
    INDEX idx_uid (uid),
    INDEX idx_season (season_id),
    INDEX idx_claimed_date (claimed_at)
);

-- Insert default season (optional)
INSERT INTO battlepass_seasons (season_number, name, start_date, end_date, is_active) 
VALUES (1, 'Season 1 - Genesis', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1)
ON DUPLICATE KEY UPDATE name = name; -- Prevent duplicate if already exists

-- Show tables created
SHOW TABLES;