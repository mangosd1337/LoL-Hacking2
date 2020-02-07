#pragma once
#include "Offsets.h"
#include "Macros.h"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT HeroStats
		{
		public:
			template<typename T>
			T GetValue(int offset)
			{
				return
					(*reinterpret_cast<T( __thiscall ** )(void*)>
					(*reinterpret_cast<DWORD *>(this) + offset))
					(this);
			}
		};

		class
			DLLEXPORT HeroStatsCollection
		{
		public:
			HeroStats* GetHeroStat( char* name );
		};
	}
}
