#include "stdafx.h"
#include "RiotRadsIO.h"
#include "Detour.hpp"

namespace EloBuddy
{
	namespace Native
	{
		MAKE_HOOK<convention_type::stdcall_t, int, int, int, int> RadsLoadLibrary;

		bool RiotRadsIO::ApplyHooks()
		{
			return true;
		}
	}
}