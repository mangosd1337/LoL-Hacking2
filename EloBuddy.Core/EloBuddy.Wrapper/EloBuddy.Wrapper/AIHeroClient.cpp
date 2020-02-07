#include "stdafx.h"
#include "AIHeroClient.hpp"

#include "../../EloBuddy.Core/EloBuddy.Core/AvatarPimpl.h"
#include "../../EloBuddy.Core/EloBuddy.Core/AIHeroClient.h"
#include "../../EloBuddy.Core/EloBuddy.Core/HeroInventory.h"
#include "../../EloBuddy.Core/EloBuddy.Core/ObjectManager.h"

namespace EloBuddy
{
	static AIHeroClient::AIHeroClient()
	{
		ATTACH_DOMAIN();
		ATTACH_EVENT
		(
			AIHeroClientDeath,
			46, Native::OnObjAIHeroDeath, Native::Obj_AI_Base*, float
		);
		ATTACH_EVENT
		(
			AIHeroClientSpawn,
			47, Native::OnObjAIHeroSpawn, Native::AIHeroClient*
		);
	}

	void AIHeroClient::DomainUnloadEventHandler( System::Object^, System::EventArgs^ )
	{
		DETACH_EVENT
		(
			AIHeroClientDeath,
			46, Native::OnObjAIHeroDeath, Native::Obj_AI_Base*, float
		);
		DETACH_EVENT
		(
			AIHeroClientSpawn,
			47, Native::OnObjAIHeroSpawn, Native::AIHeroClient*
		);
	}

	void AIHeroClient::OnAIHeroClientDeathNative( Native::Obj_AI_Base* unit, float deathDuration )
	{
		START_TRACE
			if (unit != nullptr)
			{
				auto sender = static_cast<Obj_AI_Base^>(ObjectManager::CreateObjectFromPointer( unit ));
				auto args = gcnew OnHeroDeathEventArgs( sender, deathDuration );

				for each (auto eventHandle in AIHeroClientDeathHandlers->ToArray())
				{
					START_TRACE
						eventHandle( sender, args );
					END_TRACE
				}
			}
		END_TRACE
	}

	void AIHeroClient::OnAIHeroClientSpawnNative( Native::AIHeroClient* unit )
	{
		START_TRACE
			if (unit != nullptr)
			{
				auto sender = static_cast<Obj_AI_Base^>(ObjectManager::CreateObjectFromPointer( unit ));

				for each (auto eventHandle in AIHeroClientSpawnHandlers->ToArray())
				{
					START_TRACE
						eventHandle( sender );
					END_TRACE
				}
			}
		END_TRACE
	}

	void AIHeroClient::OnAIHeroApplyCooldownNative(Native::AIHeroClient*, Native::SpellDataInst*, uint)
	{
		
	}

	List<int>^ AIHeroClient::Runes::get( )
	{
		auto list = gcnew List<int>();
		/*auto pimplPtr = this->GetPtr()->GetAvatar();

		if (pimplPtr != NULL)
		{
			int* runePtr = (int*) (pimplPtr->GetRunes( ));
			for (int i = 0; i < 30; i++)
			{
				int runeId = runePtr[i];
				list->Add( runeId );
			}
		}*/

		return list;
	}

	array<Mastery^>^ AIHeroClient::Masteries::get()
	{
		auto masteryList = gcnew List<Mastery^>();
		return masteryList->ToArray();
	}

	MAKE_HERO_STAT( Assists, "ASSISTS", int, HERO_STAT_INT );
	MAKE_HERO_STAT( BarracksKilled, "BARRACKS_KILLED", int, HERO_STAT_INT );
	MAKE_HERO_STAT( ChampionsKilled, "CHAMPIONS_KILLED", int, HERO_STAT_INT );
	MAKE_HERO_STAT( CombatPlayerScore, "COMBAT_PLAYER_SCORE", int, HERO_STAT_INT );
	MAKE_HERO_STAT( Deaths, "NUM_DEATHS", int, HERO_STAT_INT );
	MAKE_HERO_STAT( DoubleKills, "DOUBLE_KILLS", int, HERO_STAT_INT );
	MAKE_HERO_STAT( HQKilled, "HQ_KILLED", int, HERO_STAT_INT );
	MAKE_HERO_STAT( LargestCriticalStrike, "LARGEST_CRITICAL_STRIKE", float, HERO_STAT_FLOAT );
	MAKE_HERO_STAT( LargestKillingSpree, "LARGEST_KILLING_SPREE", int, HERO_STAT_FLOAT );
	MAKE_HERO_STAT( LongestTimeSpentLiving, "LONGEST_TIME_SPENT_LIVING", float, HERO_STAT_FLOAT );
	MAKE_HERO_STAT( MagicDamageDealtPlayer, "MAGIC_DAMAGE_DEALT_PLAYER", float, HERO_STAT_FLOAT );
	MAKE_HERO_STAT( MagicDamageTaken, "MAGIC_DAMAGE_TAKEN", float, HERO_STAT_FLOAT );
	MAKE_HERO_STAT( MinionsKilled, "MINIONS_KILLED", int, HERO_STAT_INT );
	MAKE_HERO_STAT( NodesCaptured, "NODE_CAPTURED", int, HERO_STAT_INT );
	MAKE_HERO_STAT( NodesNeutralized, "NODE_NEUTRALIZED", int, HERO_STAT_INT );
	MAKE_HERO_STAT( ObjectivePlayerScore, "OBJECTIVE_PLAYER_SCORE", float, HERO_STAT_FLOAT );
	MAKE_HERO_STAT( PentaKills, "PENTA_KILLS", int, HERO_STAT_INT );
	MAKE_HERO_STAT( PhysicalDamageDealtPlayer, "PHYSICAL_DAMAGE_DEALT_PLAYER", float, HERO_STAT_FLOAT );
	MAKE_HERO_STAT( PhysicalDamageTaken, "PHYSICAL_DAMAGE_TAKEN", float, HERO_STAT_FLOAT );
	MAKE_HERO_STAT( QuadraKills, "QUADRA_KILLS", int, HERO_STAT_INT );
	MAKE_HERO_STAT( SuperMonsterKilled, "SUPER_MONSTER_KILLED", int, HERO_STAT_INT );
	MAKE_HERO_STAT( TotalHeal, "TOTAL_HEAL", float, HERO_STAT_FLOAT );
	MAKE_HERO_STAT( TotalTimeCrowdControlDealt, "TOTAL_TIME_CROWD_CONTROL_DEALT", float, HERO_STAT_FLOAT );
	MAKE_HERO_STAT( TripleKills, "TRIPLE_KILLS", int, HERO_STAT_INT );
	MAKE_HERO_STAT( TurretsKilled, "TURRETS_KILLED", int, HERO_STAT_INT );
	MAKE_HERO_STAT( UnrealKills, "UNREAL_KILLS", int, HERO_STAT_INT );
	MAKE_HERO_STAT( WardsKilled, "WARD_KILLED", int, HERO_STAT_INT );
	MAKE_HERO_STAT( WardsPlaced, "WARD_PLACED", int, HERO_STAT_INT );
}