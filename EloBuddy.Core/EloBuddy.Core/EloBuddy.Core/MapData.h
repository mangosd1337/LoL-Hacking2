#pragma once
#include "Utils.h"

namespace EloBuddy
{
	namespace Native
	{
		class DLLEXPORT MapData
		{
		public:
			static MapData* GetInstance();

			MAKE_GET(MapId, int, Offsets::GameMap::MapId);
			MAKE_GET(Map, GameMap, Offsets::GameMap::MapId);
		};
	}
}