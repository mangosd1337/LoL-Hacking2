#pragma once
#include "Macros.h"
#include "Offsets.h"
#include "Detour.hpp"
#include "EventHandler.h"
#include <map>

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT ClientNode
		{
		public:
			static bool ApplyHooks();
			static ClientNode* GetInstance();

			static void ProcessClientPacket( byte* packet, DWORD hashAlgorithm, int size, ENETCHANNEL channel, bool triggerEvent );
		};
	}
}
