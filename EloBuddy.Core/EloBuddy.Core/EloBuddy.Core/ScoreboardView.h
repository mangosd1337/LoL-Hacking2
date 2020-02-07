#pragma once
#include "Utils.h"
#include "Detour.hpp"
#include "Obj_AI_Base.h"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT ScoreboardView
		{
		public:
			static bool ApplyHooks();

			static AIHeroClient** ScoreboardArray();
			static AIHeroClient* ScoreboardArrayLast();
		};
	}
}