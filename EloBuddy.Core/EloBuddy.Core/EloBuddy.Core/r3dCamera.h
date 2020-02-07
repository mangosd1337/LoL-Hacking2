#pragma once
#include "Utils.h"

#define CAMERA_GET(NAME, OFFSET) float*## Get##NAME##() { return (float*)(this + static_cast<__int32>(OFFSET)); } \
void Set##NAME##(float value) { *Get##NAME() = value; }


namespace EloBuddy
{
	namespace Native
	{
		class 
			DLLEXPORT r3dCamera
		{
		public:
			static bool ApplyHooks();
			static r3dCamera* GetInstance();

			CAMERA_GET( CameraX, 0x128 );
			CAMERA_GET( CameraY,  0x130 );

			CAMERA_GET( Pitch, 0x144 ); //56
			CAMERA_GET( YawX, 0x148 ); //180
			CAMERA_GET( YawY, 0x14C ); //180

			CAMERA_GET( ViewportDistance, 0x150 ); //56

			MAKE_GET( ZoomDistance, float, 0x220 );

			void SetSafeZoomDistance(float distance);
		};
	}
}