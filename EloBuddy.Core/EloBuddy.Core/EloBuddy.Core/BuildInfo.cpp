#include "stdafx.h"
#include "BuildInfo.h"

namespace EloBuddy
{
	namespace Native
	{
		char* BuildInfo::GetBuildDate()
		{
			__try
			{
				return reinterpret_cast<char*>(MAKE_RVA(Offsets::Game::BuildDate));
			}
			__except (EXCEPTION_EXECUTE_HANDLER)
			{
				return "Unknown";
			}
		}

		char* BuildInfo::GetBuildTime()
		{
			__try
			{
				return reinterpret_cast<char*>(MAKE_RVA(Offsets::Game::BuildTime));
			}
			__except (EXCEPTION_EXECUTE_HANDLER)
			{
				return "Unknown";
			}
		}

		char* BuildInfo::GetBuildVersion()
		{
			__try
			{
				return reinterpret_cast<char*>(MAKE_RVA(Offsets::Game::BuildVersion));
			}
			__except (EXCEPTION_EXECUTE_HANDLER)
			{
				return "Unknown";
			}
		}

		char* BuildInfo::GetBuildType()
		{

#ifdef _PBE_BUILD
			return "PBE";
#else
			__try
			{
				return reinterpret_cast<char*>(MAKE_RVA(Offsets::Game::BuildType));
			}
			__except (EXCEPTION_EXECUTE_HANDLER)
			{
				return "Unknown";
			}
#endif
		}
	}
}