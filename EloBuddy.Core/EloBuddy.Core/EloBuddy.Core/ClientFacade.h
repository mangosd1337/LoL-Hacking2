#pragma once
#include "Macros.h"
#include "Offsets.h"
#include "Console.h"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT ClientFacade
		{
		public:
			static ClientFacade* GetInstance();

			MAKE_GET( Region, std::string, Offsets::ClientFacadeStruct::Region );
			MAKE_GET( Port, int, Offsets::ClientFacadeStruct::Port );
			MAKE_GET( GameId, uint, Offsets::ClientFacadeStruct::GameId );
			MAKE_GET( IP, std::string, Offsets::ClientFacadeStruct::IP );

			int GetGameState() const;
		};
	}
}
