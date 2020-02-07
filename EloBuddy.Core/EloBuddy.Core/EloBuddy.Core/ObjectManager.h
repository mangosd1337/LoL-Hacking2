#pragma once
#include "Utils.h"
#include "StaticEnums.h"
#include <vector>
#include "Detour.hpp"
#include "EventHandler.h"

namespace EloBuddy
{
	namespace Native
	{
		class AIHeroClient;
		class GameObject;

		class
			DLLEXPORT ObjectManager
		{
		public:
			static bool ApplyHooks();
			static uint GetMaxSize();
			static uint GetUsedIndexes();
			static uint GetHighestObjectId();
			static uint GetHighestPlayerId();

			static GameObject** GetUnitArray();
			static AIHeroClient* GetPlayer();

			static GameObject* GetUnitByIndex( ushort index );
			static GameObject* GetUnitByNetworkId( uint networkId );
		};
	}
}
