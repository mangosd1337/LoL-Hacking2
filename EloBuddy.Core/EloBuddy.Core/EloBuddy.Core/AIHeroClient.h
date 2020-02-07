#pragma once

#include "Obj_AI_Base.h"
#include "AvatarPimpl.h"
#include "HeroStatsCollection.h"
#include "MissileClient.h"
#include "Detour.hpp"
#include "Experience.h"

namespace EloBuddy
{
	namespace Native
	{
		class Spellbook;

		class
			DLLEXPORT AIHeroClient : public Obj_AI_Base
		{
		public:
			static bool ApplyHooks();

			bool DoEmote( short emoteId ) const;

			MAKE_GET( ChampionName, std::string, Offsets::Obj_AIHero::ChampionName );
			MAKE_GET( Experience, Experience, Offsets::Obj_AIHero::Experience );
			MAKE_GET( Level, int, Offsets::Obj_AIHero::Level );
			MAKE_GET( Avatar, AvatarPimpl, Offsets::Obj_AIHero::Avatar );
			MAKE_GET( NeutralMinionsKilled, int, Offsets::Obj_AIHero::NumNeutralMinionsKilled );
			MAKE_GET( HeroStatsCollection, HeroStatsCollection, Offsets::HeroStats::HeroStatsInst );

			bool Virtual_CanShop();

			static void ExportFunctions();
		};
	}
}
