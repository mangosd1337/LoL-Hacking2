#pragma once
#include "Utils.h"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT BuildInfo
		{
		public:
			static char* GetBuildDate();
			static char* GetBuildTime();
			static char* GetBuildVersion();
			static char* GetBuildType();
		};
	}
}