#pragma once
#include <Windows.h>
#include <string>
#include <vector>
#include <sstream>
#include <utility>

#include "Macros.h"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT Console
		{
		public:
			static void Create();
			static void PrintLn( const char* format, ... );
			static void Show();
			static void Hide();

			static HWND GetHandle();
		};
	}
}
