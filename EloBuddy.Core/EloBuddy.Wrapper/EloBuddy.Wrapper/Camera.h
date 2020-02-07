#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/EventHandler.h"
#include "../../EloBuddy.Core/EloBuddy.Core/r3dCamera.h"

using namespace System::Collections::Generic;
using namespace SharpDX;

#include "Macros.hpp"
#include "CameraSnapEventArgs.h"
#include "CameraLockToggleEventArgs.h"
#include "CameraUpdateEventArgs.h"
#include "CameraZoomEventArgs.h"

#define CAMERA_GET_SET(NAME, TYPE) static property TYPE NAME { TYPE get() { auto camera = Native::r3dCamera::GetInstance(); if(camera != nullptr) { return *camera->Get##NAME(); } return 0;  }  void set( float value ) { auto camera = Native::r3dCamera::GetInstance(); if(camera != nullptr) { camera->Set##NAME(value); }  } }

namespace EloBuddy
{
	MAKE_EVENT_GLOBAL( CameraSnap, CameraSnapEventArgs^ args );
	MAKE_EVENT_GLOBAL( CameraToggleLock, CameraLockToggleEventArgs^ args );
	MAKE_EVENT_GLOBAL( CameraUpdate, CameraUpdateEventArgs^ args );
	MAKE_EVENT_GLOBAL( CameraZoom, CameraZoomEventArgs^ args );

	public ref class Camera
	{
	internal:
		MAKE_EVENT_INTERNAL_PROCESS( CameraSnap, () );
		MAKE_EVENT_INTERNAL_PROCESS( CameraToggleLock, () );
		MAKE_EVENT_INTERNAL_PROCESS( CameraUpdate, (float, float) );
		MAKE_EVENT_INTERNAL_PROCESS( CameraZoom, () );
	public:
		MAKE_EVENT_PUBLIC( OnSnap, CameraSnap );
		MAKE_EVENT_PUBLIC( OnToggleLock, CameraToggleLock );
		MAKE_EVENT_PUBLIC( OnUpdate, CameraUpdate );
		MAKE_EVENT_PUBLIC( OnZoom, CameraZoom );

		static Camera();
		Camera() {}
		static void DomainUnloadEventHandler( System::Object^, System::EventArgs^ );

		CAMERA_GET_SET( CameraX, float );
		CAMERA_GET_SET( CameraY, float );
		CAMERA_GET_SET( Pitch, float );
		CAMERA_GET_SET( YawX, float );
		CAMERA_GET_SET( YawY, float );
		CAMERA_GET_SET( ViewportDistance, float );

		static property Vector2 ScreenPosition
		{
			Vector2 get()
			{
				auto r3dCamera = Native::r3dCamera::GetInstance();
				if (r3dCamera != nullptr)
				{
					return Vector2( *r3dCamera->GetCameraX(), *r3dCamera->GetCameraY() );
				}
				return Vector2::Zero;
			}
			void set( Vector2 loc )
			{
				auto r3dCamera = Native::r3dCamera::GetInstance();
				if (r3dCamera != nullptr)
				{
					r3dCamera->SetCameraX( loc.X );
					r3dCamera->SetCameraY( loc.Y );
				}
			}
		}

		static property Vector2 Yaw
		{
			Vector2 get()
			{
				auto r3dCamera = Native::r3dCamera::GetInstance();
				if (r3dCamera != nullptr)
				{
					return Vector2( *r3dCamera->GetYawX(), *r3dCamera->GetYawY() );
				}
				return Vector2::Zero;
			}
			void set( Vector2 loc )
			{
				auto r3dCamera = Native::r3dCamera::GetInstance();
				if (r3dCamera != nullptr)
				{
					r3dCamera->SetYawX( loc.X );
					r3dCamera->SetYawY( loc.Y );
				}
			}
		}

		static property float ZoomDistance
		{
			float get()
			{
				auto camera = Native::r3dCamera::GetInstance(); 
				if (camera != nullptr)
				{
					return *camera->GetZoomDistance();
				}
				return 0;
			}
		}

		static void SetZoomDistance (float value)
		{
			array<byte>^ opCodes =
			{
				0x51, 0x13, 0x11, 0x9F, 0xFF, 0x41
			};

			auto camera = Native::r3dCamera::GetInstance();
			if (camera != nullptr)
			{
				camera->SetSafeZoomDistance( value );
			}
		}
	};
}