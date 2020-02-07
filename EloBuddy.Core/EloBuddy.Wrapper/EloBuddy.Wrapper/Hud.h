#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/pwHud.h"
#include "../../EloBuddy.Core/EloBuddy.Core/EventHandler.h"

#include "StaticEnums.h"
#include "Macros.hpp"

#include "HudChangeTargetEventArgs.hpp"

using namespace SharpDX;

namespace EloBuddy
{
	MAKE_EVENT_GLOBAL( HudChangeTarget, HudChangeTargetEventArgs^ args );

	public ref class Hud
	{
	internal:
		MAKE_EVENT_INTERNAL( HudChangeTarget, (Native::GameObject*) );
	public:
		MAKE_EVENT_PUBLIC( OnTargetChange, HudChangeTarget );

		static Hud();
		static void DomainUnloadEventHandler( System::Object^, System::EventArgs^ );

		MAKE_STATIC_PROPERTY( SelectedTarget, GameObject^ );
		static void ShowClick( ClickType type, Vector3 position );

		static bool IsDrawing( HudDrawingType type );
		static void EnableDrawing( HudDrawingType type );
		static void DisableDrawing( HudDrawingType type );
	};
}