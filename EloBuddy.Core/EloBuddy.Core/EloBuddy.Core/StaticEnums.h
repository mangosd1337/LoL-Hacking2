#pragma once

namespace EloBuddy
{
	namespace Native
	{
		enum class ENETCHANNEL
		{
			Handshake = 0,
			C2S,
			GamePlay,
			S2C,
			LowPriority,
			Communication,
			LoadingScreen
		};

		enum class ESENDPROTOCOL
		{
			Reliable = 0,
			NoFlags,
			Unsequenced
		};

		enum class GameObjectOrder
		{
			HoldPosition = 1,
			MoveTo,
			AttackUnit,
			AutoAttackPet,
			AutoAttack,
			MovePet,
			AttackTo,
			Stop = 10
		};

		enum class GameObjectTeam
		{
			Unknown = 0,
			Order = 100,
			Chaos = 200,
			Neutral = 300
		};

		enum class GameState
		{
			Connecting = 1,
			Running = 2,
			Paused = 3,
			Finished = 4,
			Exiting = 5
		};

		enum class SpellSlot
		{
			Unknown = -1,
			Q = 0,
			W = 1,
			E = 2,
			R = 3,
			Summoner1 = 4,
			Summoner2 = 5,
			Item1 = 6,
			Item2 = 7,
			Item3 = 8,
			Item4 = 9,
			Item5 = 10,
			Item6 = 11,
			Trinket = 12,
			Recall = 13
		};

		enum class SpellState
		{
			//Possible flags

			Ready = 0,
			NotAvailable = 4,
			Surpressed = 8,
			NotLearned = 12,
			Cooldown = 32,
			NoMana = 64,
			Unknown
		};

		enum class GameMode
		{
			Connecting = 1,
			Running = 2,
			Paused = 3,
			Finished = 4,
			Exiting = 5
		};

		enum class GameMap
		{
			CrystalScar = 8,
			TwistedTreeline = 10,
			SummonersRift = 11,
			HowlingAbyss = 12
		};

		enum class CollisionFlags
		{
			None = 0,
			Grass = 1,
			Wall = 2,
			Building = 64,
			Prop = 128,
			GlobalVision = 256
		};

		enum class UnitType
		{
			NeutralMinionCamp,
			obj_AI_Base,
			FollowerObject,
			FollowerObjectWithLerpMovement,
			AIHeroClient,
			obj_AI_Marker,
			obj_AI_Minion,
			LevelPropAI,
			obj_AI_Turret,
			obj_GeneralParticleEmitter,
			MissileClient,
			DrawFX,
			UnrevealedTarget,
			obj_LampBulb,
			obj_Barracks,
			obj_BarracksDampener,
			obj_AnimatedBuilding,
			obj_Building,
			obj_Levelsizer,
			obj_NavPoint,
			obj_SpawnPoint,	
			obj_Lake, 
			obj_HQ,
			obj_InfoPoint,
			LevelPropGameObject,
			LevelPropSpawnerPoint,
			obj_Shop,
			obj_Turret,
			GrassObject,
			obj_Ward,
			GameObject,
			Unknown
		};
	}
}
