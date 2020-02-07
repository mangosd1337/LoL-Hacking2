#pragma once
#include "../../EloBuddy.Core/EloBuddy.Core/AIHeroClient.h"
#include "../../EloBuddy.Core/EloBuddy.Core/HeroStatsCollection.h"

#include "Obj_AI_Base.hpp"
#include "Experience.h"
#include "CharData.h"

#include "HeroDeathEventArgs.h"
#include "HeroApplyCooldownEventArgs.h"

#define HERO_STAT_INT		0x2C
#define HERO_STAT_FLOAT		0x28

using namespace System;
using namespace System::Collections;

namespace EloBuddy
{
	public ref class Mastery
	{
	public:
		byte Id;
		MasteryPage Page;
		byte Points;

		Mastery::Mastery( byte id, MasteryPage page, byte points )
		{
			Id = id;
			Page = page;
			Points = points;
		}
	};

	MAKE_EVENT_GLOBAL( AIHeroClientDeath, Obj_AI_Base^ sender, OnHeroDeathEventArgs^ args );
	MAKE_EVENT_GLOBAL( AIHeroClientSpawn, Obj_AI_Base^ sender );
	MAKE_EVENT_GLOBAL( AIHeroApplyCooldown, AIHeroClient^ sender, OnHeroApplyCoolDownEventArgs^ args );

	public ref class AIHeroClient : public Obj_AI_Base
	{
	internal:
		MAKE_EVENT_INTERNAL( AIHeroClientDeath, (Native::Obj_AI_Base*, float) );
		MAKE_EVENT_INTERNAL( AIHeroClientSpawn, (Native::AIHeroClient*) );
		MAKE_EVENT_INTERNAL( AIHeroApplyCooldown, (Native::AIHeroClient*, Native::SpellDataInst*, uint) );

		Native::AIHeroClient* GetPtr()
		{
			return reinterpret_cast<Native::AIHeroClient*>(GameObject::GetPtr());
		}
	public:
		MAKE_EVENT_PUBLIC( OnDeath, AIHeroClientDeath );
		MAKE_EVENT_PUBLIC( OnSpawn, AIHeroClientSpawn );
		//MAKE_EVENT_PUBLIC( OnCooldown, AIHeroApplyCooldown );

		AIHeroClient( ushort index, uint networkId, Native::GameObject* unit ) : Obj_AI_Base( index, networkId, unit ) {}
		AIHeroClient() {};
		static AIHeroClient();
		static void DomainUnloadEventHandler( System::Object^, System::EventArgs^ );

		property int Level
		{
			int get()
			{
				return this->Experience->Level;
			}
		}

		property int SpellTrainingPoints
		{
			int get()
			{
				return this->Experience->SpellTrainingPoints;
			}
		}

		property List<int>^ Runes
		{
			List<int>^ get();
		}

		property Champion Hero
		{
			Champion get()
			{
				try
				{
					return static_cast<Champion>(Enum::Parse( Champion::typeid, this->ChampionName ));
				}
				catch (System::Exception^ ex)
				{
					System::Console::WriteLine( "[EB-Core] Exception at Champion::Hero. Hero not found: {0}, please report to finndev", this->ChampionName );
					System::Console::WriteLine("[EB-Core] Exception: {0}", ex->Message);
				}

				return Champion::Unknown;
			}
		}

		MAKE_STRING( ChampionName );
		CREATE_GET( NeutralMinionsKilled, int );
		CREATE_GET( Gold, float );
		CREATE_GET( GoldTotal, float );
		MAKE_PROPERTY( Masteries, array<Mastery^> ^ );

		property EloBuddy::Experience^ Experience
		{
			EloBuddy::Experience^ get()
			{
				return gcnew EloBuddy::Experience( this->GetPtr() );
			}
		}

		//HeroStats

		MAKE_PROPERTY( Assists, int );
		MAKE_PROPERTY( BarracksKilled, int );
		MAKE_PROPERTY( ChampionsKilled, int );
		MAKE_PROPERTY( CombatPlayerScore, int );
		MAKE_PROPERTY( Deaths, int );
		MAKE_PROPERTY( DoubleKills, int );
		MAKE_PROPERTY( HQKilled, int );
		MAKE_PROPERTY( LargestCriticalStrike, float );
		MAKE_PROPERTY( LargestKillingSpree, int );
		MAKE_PROPERTY( LongestTimeSpentLiving, float );
		MAKE_PROPERTY( MagicDamageDealtPlayer, float );
		MAKE_PROPERTY( MagicDamageTaken, float );
		MAKE_PROPERTY( MinionsKilled, int );
		MAKE_PROPERTY( NodesCaptured, int );
		MAKE_PROPERTY( NodesNeutralized, int );
		MAKE_PROPERTY( ObjectivePlayerScore, float );
		MAKE_PROPERTY( PentaKills, int );
		MAKE_PROPERTY( PhysicalDamageDealtPlayer, float );
		MAKE_PROPERTY( PhysicalDamageTaken, float );
		MAKE_PROPERTY( QuadraKills, int );
		MAKE_PROPERTY( SuperMonsterKilled, int );
		MAKE_PROPERTY( TotalHeal, float );
		MAKE_PROPERTY( TotalTimeCrowdControlDealt, float );
		MAKE_PROPERTY( TripleKills, int );
		MAKE_PROPERTY( TurretsKilled, int );
		MAKE_PROPERTY( UnrealKills, int );
		MAKE_PROPERTY( WardsKilled, int );
		MAKE_PROPERTY( WardsPlaced, int );
	};
}