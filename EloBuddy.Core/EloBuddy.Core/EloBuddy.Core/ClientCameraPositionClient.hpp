#pragma once

#include "Macros.h"
#include "Utils.h"

namespace EloBuddy
{
	namespace Native
	{
		class DLLEXPORT ClientCameraPositionClient
		{
		public:
			static ClientCameraPositionClient* GetInstance();

			//MAKE_GET(GameVisibleWorldArea, D3DVECTOR, Offsets::ClientCamera::GameCamera);
		};
	}
}