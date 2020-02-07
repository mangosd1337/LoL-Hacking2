#pragma once 

#include "Macros.h"
#include "Offsets.h"
#include "Console.h"
#include "Vector3f.h"

namespace EloBuddy
{
	namespace Native
	{
		class GameObject;

		enum class pwHudDrawingType
		{
			Healthbar,
			Menu,
			PwHud,
			Minimap,
			Ping
		};

		enum class pwHudDrawingObject
		{
			TacticalMap
		};

		class
			DLLEXPORT HudManager
		{
		public:
			MAKE_GET( ActiveVirtualCursorPos, Vector3f, 0xC );
			MAKE_GET( VirtualCursorPos, Vector3f, 0x18 );
			MAKE_GET( TargetIndexId, uint, 0x34 );

			Vector3f GetCursorPos2D() const
			{
				POINT p;
				if (GetCursorPos( &p ))
				{
					if (ScreenToClient( FindWindow( nullptr, TEXT("League of Legends (TM) Client" )), &p ))
					{
						return Vector3f( p.x, p.y, 0 );
					}
				}

				return Vector3f( 0, 0, 0 );
			}
		};

		class
			DLLEXPORT pwHud
		{
		public:
			static pwHud* GetInstance();
			static bool ApplyHooks();

			MAKE_GET( IsWindowFocused, bool, Offsets::pwHudStruct::IsFocused );

			HudManager* GetHudManager() const;
			DWORD* GetClickManager() const;
			static bool IsWindowFocused();

			void ShowClick( Vector3f* position, byte attackType ) const;

			bool IsDrawing( pwHudDrawingType type );

			void DisableUI();
			void EnableUI();
			void DisableHPBar();
			void EnableHPBar();
			void DisableMenuUI();
			void EnableMenuUI();
			void DisableMinimap();
			void EnableMinimap(); 
			void DisablePing();
			void EnablePing();
		};
	}
}
