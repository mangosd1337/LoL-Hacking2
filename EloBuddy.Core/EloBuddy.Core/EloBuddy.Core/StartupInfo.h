#pragma once
#include "Utils.h"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT StartupInfo
		{
		public:
			static StartupInfo* GetInstance();

			MAKE_GET(GameId, int, 0x0);
			MAKE_GET(Region, std::string, 0x0);
		};
	}
}
