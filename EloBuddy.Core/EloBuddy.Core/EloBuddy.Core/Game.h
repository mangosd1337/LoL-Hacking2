#pragma once
#include "Macros.h"
#include "Detour.hpp"
#include "GameObject.h"
#include "ClientFacade.h"
#include "StaticEnums.h"
#include "EventHandler.h"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT Game
		{
			int m_lastTick;
			int m_ticksPerSecond;
		public:
			static Game* GetInstance();

			bool ApplyHooks() const;
			static void FromBehind_LeagueSharp_Sucks();
			
			void SetTPS( int ticks );
			int GetTPS() const;
			void TickHandler();

			static void ExportFunctions();

			bool QuitGame();

			float GetFPS() const;
		};
	}
}
