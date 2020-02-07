#include "stdafx.h"
#include "TeemoClient.h"
#include <stdio.h>
#include <TlHelp32.h> 
#include "Utils.h"
#include "Detour.hpp"

namespace EloBuddy
{
	namespace Native
	{
		MAKE_HOOK<convention_type::winapi_t, bool, HANDLE, LPCVOID, LPVOID, SIZE_T, SIZE_T*> ReadProcessMemoryHook;
		MAKE_HOOK<convention_type::winapi_t, bool, HANDLE, LPMODULEENTRY32> Module32NextHook;
		MAKE_HOOK<convention_type::fastcall_t, unsigned int, int*, int*> VerifyDllChecksum;
		MAKE_HOOK<convention_type::stdcall_t, HANDLE, LPCSTR> GetModuleHandleHook;
		MAKE_HOOK<convention_type::winapi_t, FARPROC, HMODULE, LPCSTR> GetProcAddressHook;

		TeemoClient* TeemoClient::GetInstance()
		{
			static auto inst = new TeemoClient();
			return inst;
		}

		bool TeemoClient::Load() const
		{
			std::vector<unsigned int> dllChecksums =
			{
				0x1B20D164, 0xBCD592C6, 0x56185A2B, 0xC5834C08,
				0x8009525, 0xDC3BA5E1, 0x73C063D7, 0x4FF75681,
				0x4D3C4D03, 0x793C5678
			};

			GetModuleHandleHook.Apply(GetModuleHandleA, [] (LPCSTR lpModuleName) -> HANDLE
			{
				if (lpModuleName == nullptr)
				{
					return reinterpret_cast<HANDLE>(Core::mainModule);
				}

				return GetModuleHandleHook.CallOriginal(lpModuleName);
			});

			ReadProcessMemoryHook.Apply(ReadProcessMemory, [] (HANDLE hProcess, LPCVOID lpBaseAddress, LPVOID lpBuffer, SIZE_T nSize, SIZE_T* lpNumberOfBytesRead) -> bool
			{
				return nullptr;
			});

			Module32NextHook.Apply(Module32Next, [] (HANDLE hSnapshot, LPMODULEENTRY32 module) -> bool
			{
				ZeroMemory(module->szModule, MAX_MODULE_NAME32);

				module->modBaseAddr = nullptr;
				module->modBaseSize = 0;
				module->hModule = nullptr;
				module->th32ModuleID = 0;
				module->th32ProcessID = 0;

				SetLastError(ERROR_NO_MORE_FILES);
				return Module32NextHook.CallOriginal(hSnapshot, module);
			});

			VerifyDllChecksum.Apply(MAKE_RVA(Offsets::TeemoClient::ComputeChecksum), [] (int* bIsBlackListed, int* checksum) -> unsigned int
			{
				return 0;
			});

			/*GetProcAddressHook.Apply(GetProcAddress, [] (HMODULE hModule, LPCSTR lProcName) -> FARPROC
			{
				if (_strcmpi(lProcName, "GetNativeSystemInfo") == 1)
				{
					return nullptr;
				}

				return GetProcAddressHook.CallOriginal(hModule, lProcName);
			});*/

			return GetModuleHandleHook.IsApplied()
				&& ReadProcessMemoryHook.IsApplied()
				&& Module32NextHook.IsApplied()
				&& VerifyDllChecksum.IsApplied();
		}

		bool TeemoClient::IsLoaded() const
		{
			return m_isLoaded;
		}
	}
}

