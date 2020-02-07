#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/TacticalMap.h"
#include "../../EloBuddy.Core/EloBuddy.Core/GameObject.h"
#include "../../EloBuddy.Core/EloBuddy.Core/Vector3f.h"

#include "StaticEnums.h"
#include "Macros.hpp"
#include "TacticalMapPingEventArgs.hpp"

using namespace SharpDX;
using namespace System;
using namespace SharpDX;
using namespace System::Runtime::InteropServices;
using namespace System::Collections::Generic;

namespace EloBuddy
{
	ref class GameObject;

	MAKE_EVENT_GLOBAL( TacticalMapPing, TacticalMapPingEventArgs^ args );

	public ref class TacticalMap
	{
	internal:
		MAKE_EVENT_INTERNAL_PROCESS( TacticalMapPing, (Native::Vector3f*, Native::GameObject*, Native::GameObject*, uint) );
	public:
		MAKE_EVENT_PUBLIC( OnPing, TacticalMapPing );

		static TacticalMap();
		static void DomainUnloadEventHandler( System::Object^, System::EventArgs^ );

		static Vector2 WorldToMinimap( Vector3 worldCoord );
		static bool WorldToMinimap( Vector3 worldCoord, [Out] Vector2% mapCoord );
		static Vector3 MinimapToWorld( float x, float y );

		static property float Height
		{
			float get()
			{
				auto minimap = Native::TacticalMap::GetInstance();
				if (minimap != nullptr)
				{
					return *minimap->GetHeight();
				}
				return 0;
			}
			void set(float value)
			{
				auto minimap = Native::TacticalMap::GetInstance();
				if (minimap != nullptr)
				{
					minimap->SetHeight( value );
				}
			}
		}

		static property float Width
		{
			float get()
			{
				auto minimap = Native::TacticalMap::GetInstance();
				if (minimap != nullptr)
				{
					return *minimap->GetWidth();
				}
				return 0;
			}
			void set( float value )
			{
				auto minimap = Native::TacticalMap::GetInstance();
				if (minimap != nullptr)
				{
					minimap->SetWidth( value );
				}
			}
		}

		static property Vector2 Position
		{
			Vector2 get()
			{
				return Vector2( X, Y );
			}
		}

		static property float X
		{
			float get()
			{
				auto minimap = Native::TacticalMap::GetInstance();
				if (minimap != nullptr)
				{
					return *minimap->GetMinimapX();
				}
				return 0;
			}
			void set( float value )
			{
				auto minimap = Native::TacticalMap::GetInstance();
				if (minimap != nullptr)
				{
					minimap->SetMinimapX( value );
				}
			}
		}

		static property float Y
		{
			float get()
			{
				auto minimap = Native::TacticalMap::GetInstance();
				if (minimap != nullptr)
				{
					return *minimap->GetMinimapY();
				}
				return 0;
			}
			void set( float value )
			{
				auto minimap = Native::TacticalMap::GetInstance();
				if (minimap != nullptr)
				{
					minimap->SetMinimapY( value );
				}
			}
		}

		static property float ScaleX
		{
			float get()
			{
				auto minimap = Native::TacticalMap::GetInstance();
				if (minimap != nullptr)
				{
					return *minimap->GetScaleX();
				}
				return 0;
			}
			void set( float value )
			{
				auto minimap = Native::TacticalMap::GetInstance();
				if (minimap != nullptr)
				{
					minimap->SetScaleX( value );
				}
			}
		}

		static property float ScaleY
		{
			float get()
			{
				auto minimap = Native::TacticalMap::GetInstance();
				if (minimap != nullptr)
				{
					return *minimap->GetScaleY();
				}
				return 0;
			}
			void set( float value )
			{
				auto minimap = Native::TacticalMap::GetInstance();
				if (minimap != nullptr)
				{
					minimap->SetScaleY( value );
				}
			}
		}

		static void SendPing( PingCategory type, GameObject^ target );
		static void SendPing( PingCategory type, Vector2 position );
		static void SendPing( PingCategory type, Vector3 position );

		static void ShowPing( PingCategory type, GameObject^ target );
		static void ShowPing( PingCategory type, GameObject^ target, bool playSound );
		static void ShowPing( PingCategory type, GameObject^ source, GameObject^ target );
		static void ShowPing( PingCategory type, GameObject^ source, GameObject^ target, bool playSound );
		static void ShowPing( PingCategory type, Vector2 position );
		static void ShowPing( PingCategory type, Vector2 position, bool playSound );
		static void ShowPing( PingCategory type, Vector3 position );
		static void ShowPing( PingCategory type, Vector3 position, bool playSound );
	};
}