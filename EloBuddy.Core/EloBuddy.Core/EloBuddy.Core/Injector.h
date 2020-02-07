#pragma once
#include "Utils.h"
#include "Bootstrapper.h"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT Injector
		{
		public:
			static HANDLE InjectDLL( DWORD dwProcId, LPCWSTR szDLLPath );
		};
	}
}