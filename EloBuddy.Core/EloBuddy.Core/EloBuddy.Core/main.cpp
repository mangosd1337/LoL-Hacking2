#include "stdafx.h"
#include <stdio.h>
#include <Windows.h>

#include "TeemoClient.h"
#include "ObjectManager.h"
#include "Hacks.h"
#include "LolClient.h"

#undef MANAGED_BUILD

namespace EloBuddy
{
	namespace Native
	{
		void Initialize()
		{
			if (Utils::IsProcess("League of Legends"))
			{
				Console::Create();
			}

#ifdef _DEBUG_BUILD
			Hacks::SetConsole( true );
#endif


			Core::mainModule = reinterpret_cast<int>(GetModuleHandle(nullptr));

			if (Utils::IsProcess("League of Legends"))
			{
				if (TeemoClient::GetInstance()->Load())
				{
					auto _core = new Core(GetModuleHandle(nullptr));
				}
				else
				{
					Console::PrintLn("Failed to load TeemoClient.");
					Console::PrintLn("You most likely have another cheating tool open that interferes with EloBuddy.");
				}
			}

			if (Utils::IsProcess("LolClient"))
			{
				LolClient::Get()->Load();
			}
		}

		BOOL APIENTRY DllMain(HMODULE hModule, DWORD reason, LPVOID)
		{
			static HANDLE hThread = nullptr;

			if (Utils::IsProcess("League of Legends") || Utils::IsProcess("LolClient"))
			{
				if (reason == DLL_PROCESS_ATTACH)
				{
					hThread = CreateThread(nullptr, NULL, reinterpret_cast<LPTHREAD_START_ROUTINE>(Initialize), nullptr, NULL, nullptr);
				}

				if (reason == DLL_PROCESS_DETACH)
				{
					SuspendThread(hThread);
				}
			}

			return 1;
		}
	}
}

