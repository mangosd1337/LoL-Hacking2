#include "stdafx.h"
#include "Console.h"

#define COLOR_AQUA	3
#define COLOR_WHITE 7

namespace EloBuddy
{
	namespace Native
	{
		void Console::Create()
		{
			char title [56];
			sprintf(title, "Debug Window - %d", GetCurrentProcessId());

			AllocConsole();
			SetConsoleTitle(title);
			freopen("CON", "w", stdout);
		}

		void Console::PrintLn(const char* fmt, ...)
		{
			auto hConsole = GetHandle();
			char buffer [512];

			if (hConsole != nullptr)
			{
				//Process message
				va_list argList;
				va_start(argList, fmt);
				vsprintf_s(buffer, fmt, argList);
				va_end(argList);

				printf("[EloBuddy] %s\r\n", buffer);
			}
		}

		void Console::Show()
		{
			auto hConsole = GetHandle();

			if (hConsole != nullptr)
			{
				ShowWindow(hConsole, SW_SHOW);
				SetForegroundWindow(hConsole);
			}
		}

		void Console::Hide()
		{
			auto hConsole = GetHandle();

			if (hConsole != nullptr)
			{
				ShowWindow(hConsole, SW_HIDE);
			}
		}

		HWND Console::GetHandle()
		{
			return GetConsoleWindow();
		}
	}
}