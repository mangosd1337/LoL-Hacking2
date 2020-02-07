#pragma once

#include <iostream>
#include <windows.h>
#include <string>
#include <vector>
#include <sstream>
#include <utility>
#include "Macros.h"
#include <stdio.h>
#include "Offsets.h"
#include <stdlib.h>
#include <fstream>
#include "StaticEnums.h"
#include "Console.h"
#include "XorString.h"
#include "Patchables.h"

#undef ERROR
#undef GetDllDirectory

EXTERN_C IMAGE_DOS_HEADER __ImageBase;

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT Utils
		{
		public:
			static LPCWSTR GetDllPath( char* dll ); 
			static char* GetDllPathC( char* dll );

			static bool IsProcess(LPCSTR procName);

			static std::string StdReplace( std::string subject, const std::string& search, const std::string& replace );

			static byte SafeRead( DWORD address );
		};
	}
}