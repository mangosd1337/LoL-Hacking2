#include "stdafx.h"
#include "Detour.hpp"
#include "includes.h"
#include "Utils.h"
#include <process.h>
#include <thread>

namespace EloBuddy
{
	namespace Native
	{
		void* Detour::Install(void* target, void* detour, char* fncName)
		{
			VMProtectBeginUltra(__FUNCTION__);

			/*if (Utils::SafeRead(reinterpret_cast<DWORD>(target)) == 0xE8)
			{
				auto len = 0x5;
				auto stub = (BYTE*) malloc(len);
				DWORD oldProt;

				stub [0] = 0x8B; //mov edi, edi
				stub [1] = 0xFF;
				stub [2] = 0x55; //push ebp
				stub [3] = 0x90; //nop
				stub [4] = 0x90; //nop


				VirtualProtect(target, len, PAGE_EXECUTE_READWRITE, &oldProt);
				memcpy(target, stub, len);
				VirtualProtect(target, len, oldProt, &oldProt);

				Console::PrintLn("rpc.dll: %p - %s", target, fncName);
			}*/

			auto origFunc = DetourFunction((PBYTE) target, (PBYTE) detour);
			m_detourList.push_back(DetourEntry(origFunc, target, detour, fncName));

			VMProtectEnd();

			return origFunc;
		}

		bool Detour::RemoveHooks() const
		{
			VMProtectBeginUltra(__FUNCTION__);

			for (auto hookEntry : m_detourList)
			{
				if (!DetourRemove((PBYTE) hookEntry.orig, (PBYTE) hookEntry.detour))
				{
					//Console::PrintLn("Failed to remove Hook: %p -> %p (%s) - %08x", hookEntry.detour, hookEntry.target, hookEntry.fncName, GetLastError());
				}
			}

			VMProtectEnd();

			return true;
		}
	}
}