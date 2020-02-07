#include "stdafx.h"
#include "console.h"

void Console::Create()
{
#ifdef _DEBUG_BUILD
	AllocConsole();
	SetConsoleTitle( L"EBInjector" );
	freopen( "CON", "w", stdout );
#endif
}

void Console::Log( const char* fmt, ... )
{
#ifdef _DEBUG_BUILD
	auto hConsole = GetStdHandle( STD_OUTPUT_HANDLE );
	char buffer [512];

	if (hConsole != nullptr)
	{
		//Process message
		va_list argList;
		va_start( argList, fmt );
		vsprintf_s( buffer, fmt, argList );
		va_end( argList );

		printf( "[*] %s\r\n", buffer );
	}
#endif
}