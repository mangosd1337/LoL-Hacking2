#include "stdafx.h"
#include "Memory.h"

namespace EloBuddy
{
	namespace Native
	{
		DWORD Memory::FindPattern(DWORD dwAddress, DWORD dwLen, byte* bMask, char* szMask)
		{
			for (DWORD i = 0; i < dwLen; i++)
				if (Memory::CompareBytes(reinterpret_cast<BYTE*>(dwAddress + i), bMask, szMask))
					return static_cast<DWORD>(dwAddress + i);

			return 0;
		}

		bool Memory::CompareBytes(const byte* pData, const byte* bMask, const char* szMask)
		{
			for (; *szMask; ++szMask, ++pData, ++bMask)
				if (*szMask == 'x' && *pData != *bMask)
					return false;
			return (*szMask) == NULL;
		}

		void Memory::NOP(uint addr, uint size)
		{
			DWORD lpdOldProtect;

			auto m_location = reinterpret_cast<void*>(addr);

			VirtualProtect(m_location, size, PAGE_EXECUTE_READWRITE, &lpdOldProtect);
			memset(m_location, 0x90, size);
			VirtualProtect(m_location, size, lpdOldProtect, &lpdOldProtect);
		}
	}
}
