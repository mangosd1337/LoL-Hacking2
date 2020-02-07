#pragma once
#include "Utils.h"

namespace EloBuddy
{
	namespace Native
	{
		class Obj_AI_Base;

		class
			DLLEXPORT MissionInfo
		{
		public:
			static bool ApplyHooks();
			static MissionInfo* GetInstance();

			static bool DrawTurret( Obj_AI_Base* turret );

			MAKE_GET( GameType, int, Offsets::MissionInfoStruct::GameType );
			MAKE_GET( MapId, int, Offsets::MissionInfoStruct::MapId );
			MAKE_GET( GameMode, std::string, Offsets::MissionInfoStruct::GameMode );
			MAKE_GET( GameId, int, Offsets::MissionInfoStruct::GameId );
		};
	}
}