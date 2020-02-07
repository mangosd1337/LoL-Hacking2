#pragma once

#include "Utils.h"
#include "Offsets.h"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT AvatarPimpl
		{
		public:
			MAKE_GET(Runes, int, 0xC);
		};
	}
}
