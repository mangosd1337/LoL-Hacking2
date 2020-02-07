#include "stdafx.h"
#include "GameClock.h"

namespace EloBuddy
{
	namespace Native
	{
		GameClock* GameClock::GetInstance()
		{
			static auto* instance = reinterpret_cast<GameClock*>(MAKE_RVA(Offsets::GameClockInst::GamePauseController));
			return instance;
		}
	}
}
