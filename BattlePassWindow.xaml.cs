using System;
using System.Collections.Generic;
using System.Linq;
using System.Net.Http;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Media;
using System.Windows.Threading;
using Newtonsoft.Json.Linq;

namespace RYLWebshopApp
{
    public partial class BattlePassWindow : Window
    {
        private readonly HttpClient client = new HttpClient();
        private int currentUID;
        private List<BattlePassReward> allRewards = new List<BattlePassReward>();
        private List<int> claimedDays = new List<int>();
        private int currentDay = 1;
        private int currentWeek = 1;
        private DispatcherTimer refreshTimer;

        // API configuration - adjust to your backend
        private readonly string apiBaseUrl = "https://selamat-pagi.preview.emergentagent.com/api";

        public BattlePassWindow(int uid)
        {
            InitializeComponent();
            currentUID = uid;
            
            InitializeBattlePass();
            SetupAutoRefresh();
        }

        private async void InitializeBattlePass()
        {
            await LoadBattlePassData();
            await LoadUserProgress();
            UpdateUI();
        }

        private async Task LoadBattlePassData()
        {
            try
            {
                // Load season data from your API
                string url = $"{apiBaseUrl}/battlepass/current-season";
                var response = await client.GetStringAsync(url);
                var seasonData = JObject.Parse(response);

                if (seasonData["rewards"] != null)
                {
                    allRewards.Clear();
                    foreach (var reward in seasonData["rewards"])
                    {
                        allRewards.Add(new BattlePassReward
                        {
                            Day = (int)reward["day"],
                            ItemName = reward["item_name"]?.ToString() ?? $"Day {(int)reward["day"]} Reward",
                            ItemType = reward["item_type"]?.ToString() ?? "item",
                            RewardValue = (int)(reward["reward_value"] ?? 1),
                            Icon = reward["icon"]?.ToString() ?? "🎁",
                            Description = reward["description"]?.ToString() ?? ""
                        });
                    }

                    // Update season info
                    if (seasonData["name"] != null)
                    {
                        this.Title = $"RYL Point - {seasonData["name"]}";
                    }
                    
                    if (seasonData["end_date"] != null)
                    {
                        DateTime endDate = DateTime.Parse(seasonData["end_date"].ToString());
                        lblSeasonEnd.Text = $"Ends: {endDate.ToString("MMM dd, yyyy")}";
                    }
                }
            }
            catch (Exception ex)
            {
                ShowStatus($"Error loading battle pass: {ex.Message}", false);
            }
        }

        private async Task LoadUserProgress()
        {
            try
            {
                string url = $"{apiBaseUrl}/battlepass/user-progress/{currentUID}";
                var response = await client.GetStringAsync(url);
                var progressData = JObject.Parse(response);

                currentDay = (int)(progressData["current_day"] ?? 1);
                
                claimedDays.Clear();
                if (progressData["claimed_days"] != null)
                {
                    foreach (var day in progressData["claimed_days"])
                    {
                        claimedDays.Add((int)day);
                    }
                }

                UpdateSeasonProgress();
            }
            catch (Exception ex)
            {
                ShowStatus($"Error loading progress: {ex.Message}", false);
            }
        }

        private void UpdateUI()
        {
            UpdateWeekDisplay();
            UpdateStats();
        }

        private void UpdateWeekDisplay()
        {
            lblWeekInfo.Text = $"Week {currentWeek}";
            
            // Get rewards for current week (7 days)
            int startDay = (currentWeek - 1) * 7 + 1;
            int endDay = Math.Min(startDay + 6, 30);
            
            var weekRewards = allRewards.Where(r => r.Day >= startDay && r.Day <= endDay).ToList();
            
            // Update each reward's display properties
            foreach (var reward in weekRewards)
            {
                reward.UpdateDisplayProperties(currentDay, claimedDays);
            }
            
            rewardsContainer.ItemsSource = weekRewards;
            
            // Update navigation buttons
            btnPrevWeek.IsEnabled = currentWeek > 1;
            btnNextWeek.IsEnabled = currentWeek < 5; // 30 days = ~4.3 weeks, so max 5
        }

        private void UpdateStats()
        {
            lblClaimedCount.Text = claimedDays.Count.ToString();
            lblAvailableToday.Text = (currentDay > 0 && !claimedDays.Contains(currentDay) && currentDay <= 30) ? "1" : "0";
            lblStreak.Text = GetCurrentStreak().ToString();
            
            UpdateSeasonProgress();
        }

        private void UpdateSeasonProgress()
        {
            int progressPercent = (int)Math.Round((currentDay / 30.0) * 100);
            lblProgress.Text = $"{progressPercent}%";
            lblSeasonProgress.Text = $"{progressPercent}%";
            lblSeasonInfo.Text = $"Day {currentDay} of 30";
            
            // Update progress bar width
            double progressWidth = (currentDay / 30.0) * 300; // 300 is max width
            progressBar.Width = Math.Max(5, progressWidth); // Minimum 5 width for visibility
        }

        private int GetCurrentStreak()
        {
            if (claimedDays.Count == 0) return 0;
            
            var sortedDays = claimedDays.OrderByDescending(d => d).ToList();
            int streak = 0;
            int expectedDay = currentDay - 1;
            
            foreach (int day in sortedDays)
            {
                if (day == expectedDay)
                {
                    streak++;
                    expectedDay--;
                }
                else
                {
                    break;
                }
            }
            
            return streak;
        }

        private async void ClaimReward_Click(object sender, RoutedEventArgs e)
        {
            if (!(sender is Button button) || !(button.Tag is int day))
                return;

            // Check if already claiming
            if (!button.IsEnabled) return;
            
            button.IsEnabled = false;
            button.Content = "...";

            try
            {
                var claimData = new
                {
                    uid = currentUID,
                    day = day
                };

                string json = Newtonsoft.Json.JsonConvert.SerializeObject(claimData);
                var content = new StringContent(json, System.Text.Encoding.UTF8, "application/json");
                
                string url = $"{apiBaseUrl}/battlepass/claim-reward";
                var response = await client.PostAsync(url, content);
                var responseText = await response.Content.ReadAsStringAsync();
                var result = JObject.Parse(responseText);

                bool success = (bool)(result["success"] ?? false);
                string message = result["message"]?.ToString() ?? "Unknown response";

                if (success)
                {
                    claimedDays.Add(day);
                    if (result["new_day"] != null)
                    {
                        currentDay = (int)result["new_day"];
                    }
                    
                    ShowStatus($"🎉 {message}", true);
                    UpdateUI();
                }
                else
                {
                    ShowStatus($"❌ {message}", false);
                }
            }
            catch (Exception ex)
            {
                ShowStatus($"Error claiming reward: {ex.Message}", false);
            }
            finally
            {
                // Refresh the display to update button state
                UpdateWeekDisplay();
            }
        }

        private void ShowStatus(string message, bool isSuccess)
        {
            lblStatus.Text = message;
            statusBorder.Background = new SolidColorBrush(isSuccess ? 
                (Color)ColorConverter.ConvertFromString("#F0FDF4") : 
                (Color)ColorConverter.ConvertFromString("#FEF2F2"));
            statusBorder.BorderBrush = new SolidColorBrush(isSuccess ? 
                (Color)ColorConverter.ConvertFromString("#BBF7D0") : 
                (Color)ColorConverter.ConvertFromString("#FECACA"));
            lblStatus.Foreground = new SolidColorBrush(isSuccess ? 
                (Color)ColorConverter.ConvertFromString("#166534") : 
                (Color)ColorConverter.ConvertFromString("#DC2626"));
            
            statusBorder.Visibility = Visibility.Visible;

            // Hide status after 5 seconds
            var timer = new DispatcherTimer();
            timer.Interval = TimeSpan.FromSeconds(5);
            timer.Tick += (s, e) =>
            {
                statusBorder.Visibility = Visibility.Collapsed;
                timer.Stop();
            };
            timer.Start();
        }

        private void btnPrevWeek_Click(object sender, RoutedEventArgs e)
        {
            if (currentWeek > 1)
            {
                currentWeek--;
                UpdateWeekDisplay();
            }
        }

        private void btnNextWeek_Click(object sender, RoutedEventArgs e)
        {
            if (currentWeek < 5)
            {
                currentWeek++;
                UpdateWeekDisplay();
            }
        }

        private void SetupAutoRefresh()
        {
            refreshTimer = new DispatcherTimer();
            refreshTimer.Interval = TimeSpan.FromSeconds(30);
            refreshTimer.Tick += async (s, e) =>
            {
                await LoadUserProgress();
                UpdateStats();
            };
            refreshTimer.Start();
        }

        protected override void OnClosed(EventArgs e)
        {
            refreshTimer?.Stop();
            client?.Dispose();
            base.OnClosed(e);
        }
    }
}