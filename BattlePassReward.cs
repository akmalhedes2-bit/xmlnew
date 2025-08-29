using System.Collections.Generic;
using System.ComponentModel;
using System.Windows.Media;

namespace RYLWebshopApp
{
    public class BattlePassReward : INotifyPropertyChanged
    {
        public int Day { get; set; }
        public string ItemName { get; set; }
        public string ItemType { get; set; } // "points", "cash", "item"
        public int RewardValue { get; set; }
        public string Icon { get; set; }
        public string Description { get; set; }

        // Display properties
        private string _dayText;
        private string _valueText;
        private string _buttonText;
        private SolidColorBrush _buttonColor;
        private object _buttonStyle;
        private bool _isClaimable;
        private bool _isClaimed;
        private bool _isLocked;

        public string DayText
        {
            get => _dayText;
            set { _dayText = value; OnPropertyChanged(nameof(DayText)); }
        }

        public string ValueText
        {
            get => _valueText;
            set { _valueText = value; OnPropertyChanged(nameof(ValueText)); }
        }

        public string ButtonText
        {
            get => _buttonText;
            set { _buttonText = value; OnPropertyChanged(nameof(ButtonText)); }
        }

        public SolidColorBrush ButtonColor
        {
            get => _buttonColor;
            set { _buttonColor = value; OnPropertyChanged(nameof(ButtonColor)); }
        }

        public object ButtonStyle
        {
            get => _buttonStyle;
            set { _buttonStyle = value; OnPropertyChanged(nameof(ButtonStyle)); }
        }

        public bool IsClaimable
        {
            get => _isClaimable;
            set { _isClaimable = value; OnPropertyChanged(nameof(IsClaimable)); }
        }

        public bool IsClaimed
        {
            get => _isClaimed;
            set { _isClaimed = value; OnPropertyChanged(nameof(IsClaimed)); }
        }

        public bool IsLocked
        {
            get => _isLocked;
            set { _isLocked = value; OnPropertyChanged(nameof(IsLocked)); }
        }

        public void UpdateDisplayProperties(int currentDay, List<int> claimedDays)
        {
            // Update day text
            DayText = $"Day {Day}";

            // Update value text based on type
            switch (ItemType.ToLower())
            {
                case "points":
                    ValueText = $"⭐ {RewardValue} pts";
                    break;
                case "cash":
                    ValueText = $"💰 {RewardValue} cash";
                    break;
                default:
                    ValueText = "🎁 Item";
                    break;
            }

            // Update status
            IsClaimed = claimedDays.Contains(Day);
            IsClaimable = Day <= currentDay && !IsClaimed;
            IsLocked = Day > currentDay;

            // Update button appearance
            if (IsClaimed)
            {
                ButtonText = "✓ Claimed";
                ButtonColor = new SolidColorBrush((Color)ColorConverter.ConvertFromString("#6B7280"));
            }
            else if (IsClaimable)
            {
                ButtonText = "Claim";
                ButtonColor = new SolidColorBrush((Color)ColorConverter.ConvertFromString("#10B981"));
            }
            else
            {
                ButtonText = "🔒 Locked";
                ButtonColor = new SolidColorBrush((Color)ColorConverter.ConvertFromString("#9CA3AF"));
            }
        }

        public event PropertyChangedEventHandler PropertyChanged;

        protected virtual void OnPropertyChanged(string propertyName)
        {
            PropertyChanged?.Invoke(this, new PropertyChangedEventArgs(propertyName));
        }
    }
}