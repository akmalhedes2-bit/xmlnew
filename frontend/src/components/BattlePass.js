import React, { useState, useEffect } from 'react';
import axios from 'axios';

const BACKEND_URL = process.env.REACT_APP_BACKEND_URL;
const API = `${BACKEND_URL}/api`;

const BattlePass = () => {
  const [season, setSeason] = useState(null);
  const [userProgress, setUserProgress] = useState(null);
  const [loading, setLoading] = useState(true);
  const [claimingDay, setClaimingDay] = useState(null);
  const [statusMessage, setStatusMessage] = useState('');

  // Mock user ID - in real app this would come from auth
  const userId = 12345;

  useEffect(() => {
    fetchBattlePassData();
  }, []);

  const fetchBattlePassData = async () => {
    try {
      setLoading(true);
      
      // Fetch current season
      const seasonResponse = await axios.get(`${API}/battlepass/current-season`);
      setSeason(seasonResponse.data);
      
      // Fetch user progress
      const progressResponse = await axios.get(`${API}/battlepass/user-progress/${userId}`);
      setUserProgress(progressResponse.data);
      
    } catch (error) {
      console.error('Error fetching battle pass data:', error);
      setStatusMessage('Error loading battle pass data');
    } finally {
      setLoading(false);
    }
  };

  const claimReward = async (day) => {
    if (claimingDay === day) return;
    
    try {
      setClaimingDay(day);
      setStatusMessage('');
      
      const response = await axios.post(`${API}/battlepass/claim-reward`, {
        uid: userId,
        day: day
      });
      
      if (response.data.success) {
        setStatusMessage(`🎉 ${response.data.message}`);
        // Refresh user progress
        await fetchBattlePassData();
      } else {
        setStatusMessage(`❌ ${response.data.message}`);
      }
      
    } catch (error) {
      console.error('Error claiming reward:', error);
      setStatusMessage('❌ Error claiming reward');
    } finally {
      setClaimingDay(null);
    }
  };

  const isRewardClaimable = (day) => {
    if (!userProgress) return false;
    return day <= userProgress.current_day && !userProgress.claimed_days.includes(day);
  };

  const isRewardClaimed = (day) => {
    if (!userProgress) return false;
    return userProgress.claimed_days.includes(day);
  };

  const getRewardStatus = (day) => {
    if (isRewardClaimed(day)) return 'claimed';
    if (isRewardClaimable(day)) return 'claimable';
    return 'locked';
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-900 flex items-center justify-center">
        <div className="text-white text-xl">Loading Battle Pass...</div>
      </div>
    );
  }

  if (!season) {
    return (
      <div className="min-h-screen bg-gray-900 flex items-center justify-center">
        <div className="text-white text-xl">No active Battle Pass season</div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-900 text-white">
      {/* Header */}
      <div className="bg-gray-800 border-b border-gray-700 p-6">
        <div className="max-w-7xl mx-auto">
          <h1 className="text-3xl font-bold text-center mb-2">{season.name}</h1>
          <div className="text-center text-gray-400">
            <p>Season {season.season_number} • Day {userProgress?.current_day || 1} of 30</p>
            <p className="text-sm mt-1">
              Ends: {new Date(season.end_date).toLocaleDateString()}
            </p>
          </div>
          
          {/* Progress Bar */}
          <div className="mt-4 max-w-md mx-auto">
            <div className="bg-gray-700 rounded-full h-3">
              <div 
                className="bg-blue-500 h-3 rounded-full transition-all duration-500"
                style={{width: `${((userProgress?.current_day || 1) / 30) * 100}%`}}
              />
            </div>
            <p className="text-center text-sm text-gray-400 mt-2">
              Progress: {userProgress?.current_day || 1}/30 days
            </p>
          </div>

          {/* Status Message */}
          {statusMessage && (
            <div className="mt-4 max-w-md mx-auto">
              <div className={`p-3 rounded-lg text-center ${
                statusMessage.includes('🎉') ? 'bg-green-900/50 border border-green-700' : 'bg-red-900/50 border border-red-700'
              }`}>
                {statusMessage}
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Rewards Grid */}
      <div className="max-w-7xl mx-auto p-6">
        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-6 gap-4">
          {season.rewards.map((reward) => {
            const status = getRewardStatus(reward.day);
            
            return (
              <div
                key={reward.id}
                className={`
                  relative bg-gray-800 rounded-xl p-4 border-2 transition-all duration-300
                  ${status === 'claimed' ? 'border-green-500 bg-green-900/20' : ''}
                  ${status === 'claimable' ? 'border-blue-500 bg-blue-900/20 hover:border-blue-400 cursor-pointer' : ''}
                  ${status === 'locked' ? 'border-gray-600 opacity-60' : ''}
                `}
              >
                {/* Day Number */}
                <div className="text-center mb-2">
                  <span className="text-xs text-gray-400">Day {reward.day}</span>
                </div>

                {/* Reward Icon */}
                <div className="text-center mb-2">
                  <div className="text-4xl mb-1">{reward.icon}</div>
                  <p className="text-sm font-semibold text-gray-200 leading-tight">
                    {reward.item_name}
                  </p>
                </div>

                {/* Reward Value */}
                <div className="text-center mb-3">
                  {reward.item_type === 'points' && (
                    <span className="text-yellow-400 text-sm">⭐ {reward.reward_value}</span>
                  )}
                  {reward.item_type === 'cash' && (
                    <span className="text-green-400 text-sm">💰 {reward.reward_value}</span>
                  )}
                  {reward.item_type === 'item' && (
                    <span className="text-blue-400 text-sm">🎁 Item</span>
                  )}
                </div>

                {/* Status/Button */}
                <div className="text-center">
                  {status === 'claimed' && (
                    <div className="bg-green-600 text-white text-xs px-3 py-1 rounded-full">
                      ✓ Claimed
                    </div>
                  )}
                  
                  {status === 'claimable' && (
                    <button
                      onClick={() => claimReward(reward.day)}
                      disabled={claimingDay === reward.day}
                      className={`
                        bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1 rounded-full
                        transition-colors duration-200 disabled:opacity-50
                        ${claimingDay === reward.day ? 'cursor-not-allowed' : 'cursor-pointer'}
                      `}
                    >
                      {claimingDay === reward.day ? '...' : 'Claim'}
                    </button>
                  )}
                  
                  {status === 'locked' && (
                    <div className="bg-gray-600 text-gray-400 text-xs px-3 py-1 rounded-full">
                      🔒 Locked
                    </div>
                  )}
                </div>

                {/* Current Day Indicator */}
                {userProgress?.current_day === reward.day && status === 'claimable' && (
                  <div className="absolute -top-2 -right-2 bg-yellow-500 text-black text-xs px-2 py-1 rounded-full font-bold animate-pulse">
                    NOW
                  </div>
                )}
              </div>
            );
          })}
        </div>
      </div>

      {/* Footer Stats */}
      <div className="bg-gray-800 border-t border-gray-700 p-6 mt-8">
        <div className="max-w-4xl mx-auto text-center">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
            <div>
              <p className="text-gray-400">Claimed Rewards</p>
              <p className="text-xl font-bold text-green-400">
                {userProgress?.claimed_days.length || 0}
              </p>
            </div>
            <div>
              <p className="text-gray-400">Available Today</p>
              <p className="text-xl font-bold text-blue-400">
                {userProgress?.current_day && !userProgress.claimed_days.includes(userProgress.current_day) ? '1' : '0'}
              </p>
            </div>
            <div>
              <p className="text-gray-400">Season Progress</p>
              <p className="text-xl font-bold text-yellow-400">
                {Math.round(((userProgress?.current_day || 1) / 30) * 100)}%
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default BattlePass;