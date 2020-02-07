#include "stdafx.h"
#include "Injector.h"

namespace EloBuddy
{
	namespace Native
	{
		HANDLE Injector::InjectDLL(DWORD dwProcId, LPCWSTR szDLLPath)
		{
			int     cszDLL;
			LPVOID  lpAddress;
			HMODULE hMod;
			HANDLE  hThread;
			HANDLE  hProcess = OpenProcess(PROCESS_ALL_ACCESS, FALSE, dwProcId);

			DWORD dwModule;

			if (hProcess == NULL) {
				return NULL;
			}

			cszDLL = (wcslen(szDLLPath) + 1) * sizeof(WCHAR);

			lpAddress = VirtualAllocEx(hProcess, NULL, cszDLL, MEM_COMMIT, PAGE_EXECUTE_READWRITE);
			WriteProcessMemory(hProcess, lpAddress, szDLLPath, cszDLL, NULL);
			hMod = GetModuleHandle("kernel32.dll");

			hThread = CreateRemoteThread(hProcess, NULL, 0,
				(LPTHREAD_START_ROUTINE) (GetProcAddress(hMod,
				"LoadLibraryW")), lpAddress, 0, NULL);

			if (hThread != 0) {
				WaitForSingleObject(hThread, INFINITE);
				GetExitCodeThread(hThread, &dwModule);
				VirtualFreeEx(hProcess, lpAddress, 0, MEM_RELEASE);
				CloseHandle(hThread);
			}

			FreeLibraryAndExitThread(GetModuleHandle(0), 1);

			return hThread != 0 ? hProcess : NULL;
		}
	}
}