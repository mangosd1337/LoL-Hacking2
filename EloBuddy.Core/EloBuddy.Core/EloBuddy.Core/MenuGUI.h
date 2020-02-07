#pragma once 

#include "Macros.h"
#include "Offsets.h"

namespace EloBuddy
{
	namespace Native
	{
		class GameObject;
		class Vector3f;

		class
			DLLEXPORT MenuGUI
		{
		public:
			static bool ApplyHooks();
			static MenuGUI* GetInstance();

			MAKE_GET( IsChatOpen, bool, Offsets::MenuGUIStruct::IsChatOpen );
			MAKE_GET( IsActive, bool, Offsets::MenuGUIStruct::IsActive );
			MAKE_GET( Input, char, Offsets::MenuGUIStruct::ActiveMessage );

			static void PingMiniMap( Vector3f* dstVec, int indexSource, int indexTarget, int pingType, bool playSound = false );
			static void CallCurrentPing( Vector3f* dstVec, int targetNetId, int pingType );

			static bool DoMasteryBadge();
		};
	}
}
