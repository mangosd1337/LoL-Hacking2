#pragma once

#include "Utils.h"

namespace EloBuddy
{
	namespace Native
	{
		class DLLEXPORT Bootstrapper {
		public:
			static void Initialize();
			static bool HostClr();
			static bool InjectWrapper();
		};
	}
}