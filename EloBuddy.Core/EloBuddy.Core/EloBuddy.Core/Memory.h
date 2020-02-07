#pragma once

#include "Utils.h"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT Memory
		{
		public:
			static DWORD FindPattern(DWORD dwAddress, DWORD dwLen, byte* bMask, char* szMask);
			static bool CompareBytes(const byte* pData, const byte* bMask, const char* szMask);
			
			static void NOP(uint addr, uint size);
		};
	}
}
