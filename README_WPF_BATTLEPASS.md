# 🎮 RYL Point - WPF Battle Pass System

## 📋 Overview
Battle Pass system untuk WPF C# Framework 4.8 yang terintegrasi dengan existing RYL Point shop system.

## 🚀 Features

### UI Components
- **Modern Card-based Layout**: Follow pattern existing ShopWindow
- **Weekly View Navigation**: Navigate through 4+ weeks of rewards
- **Real-time Progress Tracking**: Progress bar dan statistics
- **Fluent Design**: Consistent styling dengan existing app
- **Auto-refresh**: Real-time updates setiap 30 detik

### Functionality
- **Daily Reward Claims**: Integrated dengan web API backend
- **Streak Tracking**: Monitor login streaks
- **Season Progress**: Visual progress indicators
- **Status Messages**: Success/error feedback
- **Week Navigation**: Previous/Next week browsing

## 📁 File Structure

```
RYLWebshopApp/
├── BattlePassWindow.xaml          # UI Layout
├── BattlePassWindow.xaml.cs       # Code behind logic  
├── BattlePassReward.cs            # Reward model class
└── README_WPF_BATTLEPASS.md       # Documentation
```

## 🎯 Integration dengan Existing App

### Cara Buka Battle Pass Window:
```csharp
// Dari ShopWindow atau MainWindow
private void btnBattlePass_Click(object sender, RoutedEventArgs e)
{
    var battlePassWindow = new BattlePassWindow(SessionManager.UID);
    battlePassWindow.ShowDialog();
}
```

### API Integration:
- **Backend URL**: `https://selamat-pagi.preview.emergentagent.com/api`
- **Endpoints Same**: Follow existing FastAPI endpoints
- **HTTP Client**: Uses same pattern as ShopWindow

## 🔧 Setup Instructions

### 1. Add Files ke Project:
- Copy `BattlePassWindow.xaml` ke project
- Copy `BattlePassWindow.xaml.cs` ke project  
- Copy `BattlePassReward.cs` ke project

### 2. Add Dependencies:
```xml
<!-- Pastikan ada di packages.config atau References -->
<PackageReference Include="Newtonsoft.Json" Version="13.0.3" />
<PackageReference Include="System.Net.Http" Version="4.3.4" />
```

### 3. Add Button ke ShopWindow:
```xml
<!-- Tambah button ni dalam existing ShopWindow.xaml -->
<Button x:Name="btnBattlePass" Content="Battle Pass" 
        Width="120" Height="30" Margin="0,0,10,0"
        Style="{StaticResource FluentButton}" 
        Click="btnBattlePass_Click"/>
```

### 4. Add Event Handler:
```csharp
// Dalam ShopWindow.xaml.cs
private void btnBattlePass_Click(object sender, RoutedEventArgs e)
{
    var battlePassWindow = new BattlePassWindow(currentUID);
    battlePassWindow.ShowDialog();
}
```

## 🎨 UI Components

### Reward Card States:
- **🟢 Claimed**: Hijau, "✓ Claimed", disabled
- **🔵 Claimable**: Hijau, "Claim" button, enabled  
- **🔒 Locked**: Abu-abu, "🔒 Locked", disabled

### Week Navigation:
- **Previous Week** / **Next Week** buttons
- **Week 1-5** coverage (30 days total)
- **7 rewards per week** dalam grid layout

### Statistics Footer:
- **Claimed Rewards**: Total claimed count
- **Available Today**: 0 or 1 available  
- **Login Streak**: Consecutive days
- **Season Progress**: Percentage complete

## 🔄 API Integration

### Endpoints Used:
```csharp
// Get current season & rewards
GET /api/battlepass/current-season

// Get user progress
GET /api/battlepass/user-progress/{uid}

// Claim reward  
POST /api/battlepass/claim-reward
{
    "uid": 12345,
    "day": 5
}
```

### Error Handling:
- Connection errors display status messages
- API failures show user-friendly messages
- Auto-retry dengan refresh timer

## 🎯 Reward System

### 30-Day Schedule:
- **Days 1-4, 6, 8-9, 11-13, 16-19, 22-24, 26-27, 29**: Items (🎁)
- **Days 5, 10, 15, 20, 25, 30**: Points bonus (⭐)
- **Days 7, 14, 21, 28**: Cash bonus (💰)

### Weekly Highlights:
- **Week 1**: Introduction rewards
- **Week 2**: Enhanced values  
- **Week 3**: Premium items
- **Week 4**: Finale rewards
- **Week 5**: Day 29-30 special

## ✅ Testing

### Manual Testing Steps:
1. Launch `BattlePassWindow` dengan valid UID
2. Verify season info loads correctly
3. Test claim Day 1 reward
4. Verify progress updates
5. Test week navigation
6. Check statistics accuracy

### Integration Testing:
1. Open dari existing ShopWindow  
2. Verify UID carried over correctly
3. Test API connectivity
4. Verify styling consistency

## 🚀 Production Ready

### Requirements Met:
- ✅ WPF C# Framework 4.8 compatible
- ✅ Consistent dengan existing app design
- ✅ Same API backend integration
- ✅ Error handling & user feedback
- ✅ Auto-refresh functionality
- ✅ Week-based navigation
- ✅ Real-time statistics

### Ready untuk Production:
Battle Pass system siap integrate dengan existing RYL Point application. Follow setup instructions untuk add ke project existing.

---
*Compatible dengan RYL Point Shop System - WPF C# Framework 4.8*