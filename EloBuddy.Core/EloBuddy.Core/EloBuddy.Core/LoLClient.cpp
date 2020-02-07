#include "stdafx.h"
#include "LolClient.h"
#include <stdio.h>
#include <TlHelp32.h> 
#include "Utils.h"
#include "Detour.hpp"
#include <Psapi.h>

namespace EloBuddy
{
	namespace Native
	{
		MAKE_HOOK <convention_type::winapi_t, bool, HANDLE, LPCVOID, LPVOID, SIZE_T, SIZE_T*> LCReadProcessMemory;
		MAKE_HOOK <convention_type::winapi_t, bool, HANDLE, LPMODULEENTRY32> LCModule32Next;
		MAKE_HOOK <convention_type::winapi_t, bool, HANDLE, HMODULE*, DWORD, LPDWORD> LCEnumProcessModules;

		LolClient* LolClient::Get()
		{
			static auto inst = new LolClient();
			return inst;
		}

		bool LolClient::Load()
		{
			LCReadProcessMemory.Apply(ReadProcessMemory, [] (HANDLE hProcess, LPCVOID lpBaseAddress, LPVOID lpBuffer, SIZE_T nSize, SIZE_T* lpNumberOfBytesRead) -> bool
			{
				return nullptr;
			});

			LCModule32Next.Apply(Module32Next, [] (HANDLE hSnapshot, LPMODULEENTRY32 module) -> bool
			{
				ZeroMemory(module->szModule, MAX_MODULE_NAME32);

				module->modBaseAddr = nullptr;
				module->modBaseSize = 0;
				module->hModule = nullptr;
				module->th32ModuleID = 0;
				module->th32ProcessID = 0;

				SetLastError(ERROR_NO_MORE_FILES);
				return LCModule32Next.CallOriginal(hSnapshot, module);
			});

			LCEnumProcessModules.Apply(K32EnumProcessModules, [] (HANDLE hProcess, HMODULE* lphModule, DWORD cb, LPDWORD lpcbNeeded) -> bool
			{
				*lphModule = nullptr;
				return false;
			});

			this->m_isLoaded = true;

			return LCReadProcessMemory.IsApplied()
				&& LCModule32Next.IsApplied()
				&& LCEnumProcessModules.IsApplied();
		}

		bool LolClient::IsLoaded() const
		{
			return m_isLoaded;
		}
	}
}

