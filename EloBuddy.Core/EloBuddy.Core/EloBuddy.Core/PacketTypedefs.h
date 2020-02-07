#pragma once
#include "Utils.h"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT C2S_ENetPacket
		{
		public:
			MAKE_GET( HashAlgorithm, DWORD, 0x0 );
			MAKE_GET( Size, byte, 0x0 );
			MAKE_GET( Command, short, 0x4 );
			MAKE_GET( NetworkId, uint, 0x6 );
			MAKE_GET( Data, char, 0xA );
		};

		class
			DLLEXPORT S2C_ENetPacket
		{
		public:
			MAKE_GET( HashAlgorithm, DWORD, 0x0 );
			MAKE_GET( Size, byte, 0x0 );
			MAKE_GET( Command, short, 0x4 );
			MAKE_GET( Data, char, 0xA );
		};
	}
}