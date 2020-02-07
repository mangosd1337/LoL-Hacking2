#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/Hacks.h"

namespace EloBuddy
{
	public ref class Hacks
	{
	public:
		static property bool Console
		{
			bool get() { return Native::Hacks::GetConsole(); }
			void set( bool value ) { Native::Hacks::SetConsole( value ); }
		}

		static property bool AntiAFK
		{
			bool get() { return Native::Hacks::GetAntiAFK(); }
			void set( bool value ) { Native::Hacks::SetAntiAFK( value ); }
		}

		static property bool ZoomHack
		{
			bool get() { return Native::Hacks::GetZoomHack(); }
			void set( bool value ) { Native::Hacks::SetZoomHack( value ); }
		}

		static property bool RenderWatermark
		{
			bool get() { return Native::Hacks::GetDrawWatermark(); }
			void set( bool value ) { Native::Hacks::SetDrawWatermark( value ); }
		}

		static property bool IngameChat
		{
			bool get() { return Native::Hacks::GetPwConsole(); }
			void set( bool value ) { Native::Hacks::SetPwConsole( value ); }
		}

		static property bool MovementHack
		{
			bool get() { return Native::Hacks::GetMovementHack(); }
			void set( bool value ) { Native::Hacks::SetMovementHack( value ); }
		}

		static property bool TowerRanges
		{
			bool get() { return Native::Hacks::GetTowerRanges(); }
			void set( bool value ) { Native::Hacks::SetTowerRanges( value ); }
		}
	};
}