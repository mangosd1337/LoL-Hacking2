#pragma once
#include "Utils.h"

namespace EloBuddy
{
	namespace Native
	{
		struct VoteStruct
		{
			MAKE_GET( NetworkId, uint, 0x18 );
			MAKE_GET( Vote, bool, 0x1C );
		};

		class
			DLLEXPORT HudVote
		{
		public:
			static bool ApplyHooks();
		};
	}
}