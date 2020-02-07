#include "stdafx.h"
#include "RiotClock.h"

namespace EloBuddy
{
	namespace Native
	{
		RiotClock* RiotClock::GetInstance()
		{
			return *reinterpret_cast<RiotClock**>(MAKE_RVA(Offsets::RiotClock::RiotClockInst));
		}
	}
}