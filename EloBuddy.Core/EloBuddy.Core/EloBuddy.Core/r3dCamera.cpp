#include "stdafx.h"
#include "r3dCamera.h"
#include "r3dTexture.h"
#include "Detour.hpp"
#include "EventHandler.h"

namespace EloBuddy
{
	namespace Native
	{
		MAKE_HOOK<convention_type::stdcall_t, bool, int> r3dCameraSnap;
		MAKE_HOOK<convention_type::cdecl_t, bool, int> r3dCameraLockToggle;
		MAKE_HOOK<convention_type::fastcall_t, char, int, int, float, float, char, char> r3dCameraUpdate;
		MAKE_HOOK<convention_type::stdcall_t, void, int> r3dCameraZoom;

		r3dCamera* r3dCamera::GetInstance()
		{
			return **reinterpret_cast<r3dCamera***>(MAKE_RVA( Offsets::pwHud::pwHud_Instance ));
		}

		void r3dCamera::SetSafeZoomDistance(float distance)
		{
			VMProtectBeginUltra(__FUNCTION__);
			*GetViewportDistance() = distance / 45;
			VMProtectEnd();
		}

		bool r3dCamera::ApplyHooks()
		{
			/*r3dCameraSnap.Apply( MAKE_RVA( Offsets::r3dCamera::CameraSnap ), [] ( int a1 ) -> bool
			{
				if (!EventHandler<55, OnCameraSnap>::GetInstance()->TriggerProcess())
					return true;

				return r3dCameraSnap.CallOriginal( a1 );
			} );
	
			r3dCameraLockToggle.Apply( MAKE_RVA( Offsets::r3dCamera::CameraLockToggle ), [] ( int a1 ) -> bool
			{
				__asm pushad;
					if (!EventHandler<56, OnCameraToggleLock>::GetInstance()->TriggerProcess())
						return true;
				__asm popad;

				return r3dCameraLockToggle.CallOriginal( a1 );
			} );*/

			return true;

			/*
			r3dCameraUpdate.Apply( MAKE_RVA( Offsets::r3dCamera::CameraUpdate ), [] ( int unkn1, int unkn2, float MouseX, float MouseY, char unkn3, char unk4 ) -> char
			{
				if (!EventHandler<57, OnCameraUpdate, float, float>::GetInstance()->TriggerProcess( MouseX, MouseY ))
					return false;

				return r3dCameraUpdate.CallOriginal( unkn1, unkn2, MouseX, MouseY, unkn3, unk4 );
			} );*/

			/*r3dCameraZoom.Apply( MAKE_RVA( Offsets::r3dCamera::CameraZoom ), [] ( int r3dCamera ) -> void
			{
				if (EventHandler<58, OnCameraZoom>::GetInstance()->TriggerProcess())
				{
					r3dCameraZoom.CallOriginal( r3dCamera );
				}
			} );*/

			/*
			return 
				   r3dCameraSnap.IsApplied()
				&& r3dCameraLockToggle.IsApplied()
				&& r3dCameraUpdate.IsApplied()
				&& r3dCameraZoom.IsApplied();*/
		}
	}
}