#pragma once
#include "Utils.h"
#include "Detour.hpp"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT pwConsole
		{
		public:
			static bool ApplyHooks();
			static pwConsole* GetInstance( );

			void ShowClientSideMessage( const char* msg );
			
			void ProcessCommand();
			void Show();
			void Close();

			static void ExportFunctions();
		};
	}
}
