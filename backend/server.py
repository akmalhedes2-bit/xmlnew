from fastapi import FastAPI, APIRouter
from dotenv import load_dotenv
from starlette.middleware.cors import CORSMiddleware
from motor.motor_asyncio import AsyncIOMotorClient
import os
import logging
from pathlib import Path
from pydantic import BaseModel, Field
from typing import List, Optional
import uuid
from datetime import datetime, timedelta
from fastapi import HTTPException


ROOT_DIR = Path(__file__).parent
load_dotenv(ROOT_DIR / '.env')

# MongoDB connection
mongo_url = os.environ['MONGO_URL']
client = AsyncIOMotorClient(mongo_url)
db = client[os.environ['DB_NAME']]

# Create the main app without a prefix
app = FastAPI()

# Create a router with the /api prefix
api_router = APIRouter(prefix="/api")


# Define Models
class StatusCheck(BaseModel):
    id: str = Field(default_factory=lambda: str(uuid.uuid4()))
    client_name: str
    timestamp: datetime = Field(default_factory=datetime.utcnow)

class StatusCheckCreate(BaseModel):
    client_name: str

# Battle Pass Models
class BattlePassReward(BaseModel):
    id: str = Field(default_factory=lambda: str(uuid.uuid4()))
    day: int
    item_name: str
    item_type: str  # "points", "cash", "item"
    reward_value: int
    icon: str
    description: str

class BattlePassSeason(BaseModel):
    id: str = Field(default_factory=lambda: str(uuid.uuid4()))
    season_number: int
    name: str
    start_date: datetime
    end_date: datetime
    is_active: bool = True
    rewards: List[BattlePassReward] = []

class UserBattlePassProgress(BaseModel):
    id: str = Field(default_factory=lambda: str(uuid.uuid4()))
    uid: int
    season_id: str
    current_day: int = 1
    claimed_days: List[int] = []
    last_claim_date: Optional[datetime] = None
    created_at: datetime = Field(default_factory=datetime.utcnow)

class ClaimRewardRequest(BaseModel):
    uid: int
    day: int

class ClaimRewardResponse(BaseModel):
    success: bool
    message: str
    reward: Optional[BattlePassReward] = None
    new_day: Optional[int] = None

# Add your routes to the router instead of directly to app
@api_router.get("/")
async def root():
    return {"message": "Hello World"}

@api_router.post("/status", response_model=StatusCheck)
async def create_status_check(input: StatusCheckCreate):
    status_dict = input.dict()
    status_obj = StatusCheck(**status_dict)
    _ = await db.status_checks.insert_one(status_obj.dict())
    return status_obj

@api_router.get("/status", response_model=List[StatusCheck])
async def get_status_checks():
    status_checks = await db.status_checks.find().to_list(1000)
    return [StatusCheck(**status_check) for status_check in status_checks]

# Battle Pass API Endpoints

@api_router.get("/battlepass/current-season")
async def get_current_battle_pass_season():
    """Get current active battle pass season with rewards"""
    season = await db.battlepass_seasons.find_one({"is_active": True})
    
    if not season:
        # Create default season if none exists
        default_rewards = []
        for day in range(1, 31):  # 30 days battle pass
            if day % 7 == 0:  # Weekly bonus
                reward_type = "cash"
                value = day * 10
                icon = "💰"
                description = f"Weekly Bonus: {value} Cash"
            elif day % 5 == 0:  # Every 5th day points
                reward_type = "points"
                value = day * 15
                icon = "⭐"
                description = f"Bonus Points: {value} Points"
            else:
                reward_type = "item"
                value = 1
                icon = "🎁"
                description = f"Daily Reward Item"
            
            default_rewards.append(BattlePassReward(
                day=day,
                item_name=f"Day {day} Reward",
                item_type=reward_type,
                reward_value=value,
                icon=icon,
                description=description
            ))
        
        new_season = BattlePassSeason(
            season_number=1,
            name="Season 1 - Genesis",
            start_date=datetime.utcnow(),
            end_date=datetime.utcnow() + timedelta(days=30),
            rewards=default_rewards
        )
        
        await db.battlepass_seasons.insert_one(new_season.dict())
        return new_season
    
    return BattlePassSeason(**season)

@api_router.get("/battlepass/user-progress/{uid}")
async def get_user_battle_pass_progress(uid: int):
    """Get user's battle pass progress"""
    current_season = await db.battlepass_seasons.find_one({"is_active": True})
    if not current_season:
        raise HTTPException(status_code=404, detail="No active battle pass season")
    
    progress = await db.user_battlepass_progress.find_one({
        "uid": uid,
        "season_id": current_season["id"]
    })
    
    if not progress:
        # Create new progress for user
        new_progress = UserBattlePassProgress(
            uid=uid,
            season_id=current_season["id"]
        )
        await db.user_battlepass_progress.insert_one(new_progress.dict())
        return new_progress
    
    return UserBattlePassProgress(**progress)

@api_router.post("/battlepass/claim-reward", response_model=ClaimRewardResponse)
async def claim_battle_pass_reward(request: ClaimRewardRequest):
    """Claim daily battle pass reward"""
    current_season = await db.battlepass_seasons.find_one({"is_active": True})
    if not current_season:
        return ClaimRewardResponse(success=False, message="No active battle pass season")
    
    season = BattlePassSeason(**current_season)
    
    # Get user progress
    progress = await db.user_battlepass_progress.find_one({
        "uid": request.uid,
        "season_id": season.id
    })
    
    if not progress:
        return ClaimRewardResponse(success=False, message="User not registered for battle pass")
    
    user_progress = UserBattlePassProgress(**progress)
    
    # Validate claim
    if request.day in user_progress.claimed_days:
        return ClaimRewardResponse(success=False, message="Reward already claimed for this day")
    
    if request.day > user_progress.current_day:
        return ClaimRewardResponse(success=False, message="Cannot claim future rewards")
    
    # Find reward for the day
    reward = None
    for r in season.rewards:
        if r.day == request.day:
            reward = r
            break
    
    if not reward:
        return ClaimRewardResponse(success=False, message="No reward found for this day")
    
    # Update user progress
    user_progress.claimed_days.append(request.day)
    user_progress.last_claim_date = datetime.utcnow()
    
    # If claiming current day, advance to next day
    if request.day == user_progress.current_day and user_progress.current_day < 30:
        user_progress.current_day += 1
    
    # Save progress
    await db.user_battlepass_progress.update_one(
        {"uid": request.uid, "season_id": season.id},
        {"$set": user_progress.dict()}
    )
    
    return ClaimRewardResponse(
        success=True,
        message=f"Successfully claimed {reward.item_name}!",
        reward=reward,
        new_day=user_progress.current_day
    )

# Include the router in the main app
app.include_router(api_router)

app.add_middleware(
    CORSMiddleware,
    allow_credentials=True,
    allow_origins=os.environ.get('CORS_ORIGINS', '*').split(','),
    allow_methods=["*"],
    allow_headers=["*"],
)

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

@app.on_event("shutdown")
async def shutdown_db_client():
    client.close()