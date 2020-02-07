#pragma once
#include "Utils.h"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT RiotClock
		{
		public:
			static RiotClock* GetInstance();

			float GetTime()
			{
				return (*reinterpret_cast<float( ** )(void)>(*reinterpret_cast<DWORD *>(this) + static_cast<int>(Offsets::RiotClockStruct::GameTime)))();
			}

			MAKE_GET( Delta, float, Offsets::RiotClockStruct::Delta );
			MAKE_GET( ClockTime, float, Offsets::RiotClockStruct::Time );
		};
	}
}