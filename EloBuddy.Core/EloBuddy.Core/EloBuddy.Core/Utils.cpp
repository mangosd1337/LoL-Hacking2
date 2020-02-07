#include "stdafx.h"
#include "Utils.h"
#include <Psapi.h>
#include <sys/stat.h>
#include <comdef.h>

namespace EloBuddy
{
	namespace Native
	{
		LPCWSTR Utils::GetDllPath( char* dll )
		{
			char buffer[MAX_PATH];
			GetModuleFileName( reinterpret_cast<HINSTANCE>(&__ImageBase), buffer, MAX_PATH );

			auto pos = std::string( buffer ).find_last_of( "\\/" );

			// Path to directory
			char dir[MAX_PATH];
			//strncpy_s( dir, buffer, strlen( buffer )   );
			strncpy_s( dir, buffer, pos );

			// Path to directory with dll attached
			char dirDllPath[MAX_PATH];
			sprintf_s( dirDllPath, "%s\\%s", dir, dll );

			// Convert to wchar
			auto wcharDLLPath = new wchar_t[MAX_PATH];
			MultiByteToWideChar( CP_ACP, 0, dirDllPath, -1, wcharDLLPath, 4096 );

			return wcharDLLPath;
		}

		char* Utils::GetDllPathC( char* dll )
		{
			char buffer[MAX_PATH];
			GetModuleFileName( reinterpret_cast<HINSTANCE>(&__ImageBase), buffer, MAX_PATH );

			auto pos = std::string( buffer ).find_last_of( "\\/" );

			// Path to directory
			char dir[MAX_PATH];
			//strncpy_s( dir, buffer, strlen( buffer ) - length );
			strncpy_s( dir, buffer, pos );

			// Path to directory with dll attached
			char dirDllPath[MAX_PATH];
			sprintf_s( dirDllPath, "%s\\%s", dir, dll );

			return dirDllPath;
		}

		bool Utils::IsProcess(LPCSTR procName)
		{
			TCHAR path [MAX_PATH + 1];
			GetModuleFileName( NULL, path, MAX_PATH );
			return std::string( path ).find( procName ) != std::string::npos;
		}

		byte Utils::SafeRead(DWORD address)
		{
			__try
			{
				return *reinterpret_cast<BYTE*>(address);
			} __except (1) {}

			return 0x0;
		}
	}
}