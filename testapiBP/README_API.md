# 🎮 Battle Pass PHP API Documentation

## 📋 Overview
PHP API untuk Battle Pass system yang compatible dengan existing RYL Point shop pattern.

## 📁 File Structure
```
/testapiBP/
├── get_season.php          # Get current season & rewards
├── get_user_progress.php   # Get user progress
├── claim_reward.php        # Claim daily reward
├── database_setup.sql      # MySQL table creation
└── README_API.md           # Documentation
```

## 🔧 Setup Instructions

### Step 1: Database Setup
1. **Login ke MySQL** dalam VPS anda
2. **Run** `database_setup.sql` script:
```sql
mysql -u root -p < database_setup.sql
```

### Step 2: Configure Database Connection
Update credentials dalam semua PHP files:
```php
$host = 'localhost';        // MySQL host
$dbname = 'ryl_battlepass'; // Database name
$username = 'root';         // MySQL username  
$password = 'your_password'; // MySQL password
```

### Step 3: Upload Files
Upload semua files ke folder `/testapiBP/` dalam VPS:
```
/var/www/html/testapiBP/
├── get_season.php
├── get_user_progress.php
├── claim_reward.php
└── database_setup.sql
```

### Step 4: Set Permissions
```bash
chmod 644 /var/www/html/testapiBP/*.php
chown www-data:www-data /var/www/html/testapiBP/
```

## 🔗 API Endpoints

### 1. Get Current Season
**URL**: `http://31.58.143.7/testapiBP/get_season.php`
**Method**: GET
**Response**:
```json
{
    "success": true,
    "season": {
        "id": 1,
        "season_number": 1,
        "name": "Season 1 - Genesis",
        "start_date": "2025-08-29 00:00:00",
        "end_date": "2025-09-28 00:00:00",
        "is_active": true,
        "rewards": [
            {
                "day": 1,
                "item_name": "Day 1 Reward",
                "item_type": "item",
                "reward_value": 1,
                "icon": "🎁",
                "description": "Daily Reward Item"
            }
        ]
    }
}
```

### 2. Get User Progress
**URL**: `http://31.58.143.7/testapiBP/get_user_progress.php?uid=12345`
**Method**: GET
**Parameters**: `uid` (integer)
**Response**:
```json
{
    "success": true,
    "progress": {
        "uid": 12345,
        "season_id": 1,
        "current_day": 5,
        "claimed_days": [1, 2, 3, 4],
        "last_claim_date": "2025-08-29 10:00:00",
        "created_at": "2025-08-29 08:00:00"
    }
}
```

### 3. Claim Reward
**URL**: `http://31.58.143.7/testapiBP/claim_reward.php`
**Method**: POST
**Content-Type**: application/json
**Body**:
```json
{
    "uid": 12345,
    "day": 5
}
```
**Response**:
```json
{
    "success": true,
    "message": "Successfully claimed Day 5 Reward!",
    "reward": {
        "day": 5,
        "item_name": "Day 5 Reward",
        "item_type": "points",
        "reward_value": 75,
        "icon": "⭐"
    },
    "new_day": 6
}
```

## 🎯 Integration dengan WPF

### Update WPF API URL:
Dalam `BattlePassWindow.xaml.cs`:
```csharp
// Update ke VPS anda
private readonly string apiBaseUrl = "http://31.58.143.7/testapiBP";

// Update methods untuk use PHP endpoints
private async Task LoadBattlePassData()
{
    string url = $"{apiBaseUrl}/get_season.php";
    // rest of code...
}

private async Task LoadUserProgress()  
{
    string url = $"{apiBaseUrl}/get_user_progress.php?uid={currentUID}";
    // rest of code...
}
```

## 🗄️ Database Schema

### Tables Created:
- **battlepass_seasons**: Season management
- **battlepass_rewards**: Daily rewards data  
- **user_battlepass_progress**: User progress tracking
- **battlepass_claims**: Claim history (analytics)

### Key Features:
- ✅ Auto-create default season
- ✅ JSON storage untuk claimed days
- ✅ Transaction safety
- ✅ Foreign key constraints
- ✅ Proper indexing

## 🔒 Security Features
- SQL injection prevention (prepared statements)
- Input validation
- Transaction rollback on errors
- CORS headers for web access

## 🚀 Ready for Production
API siap untuk integrate dengan:
- ✅ WPF C# application
- ✅ Web frontend (React/etc)
- ✅ Mobile apps
- ✅ Existing RYL Point system

---
*Compatible dengan RYL Point Shop API Pattern*