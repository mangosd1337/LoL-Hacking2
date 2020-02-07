#include "stdafx.h"
#include "HeroStatsCollection.h"
#include "Console.h"

namespace EloBuddy
{
	namespace Native
	{
		HeroStats* HeroStatsCollection::GetHeroStat( char* name )
		{
			auto loadHeroStat = MAKE_RVA( Offsets::HeroStats::LoadHeroStat );
			void* heroStatAllocation;

			__asm
			{
				mov ecx, this
				call [loadHeroStat]
				mov heroStatAllocation, eax
			}

			HeroStats* heroStats = nullptr;

			*reinterpret_cast<HeroStats**>(reinterpret_cast<int( __thiscall* )(void*, int, std::string*)>
				MAKE_RVA( Offsets::HeroStats::GetHeroStats )
				(heroStatAllocation, reinterpret_cast<int>(&heroStats), new std::string( name )));

			return heroStats;
		}
	}
}
