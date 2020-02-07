#pragma once

#include <d3d9.h>
#pragma comment(lib,"d3d9.lib")
#pragma comment(lib, "d3dx9.lib")
#include "Detour.hpp"
#include "Utils.h"
#include "Memory.h"
#include "EventHandler.h"
#include "Drawing.h"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT RiotX3D
		{
			static LPDIRECT3DDEVICE9 Direct3DDevice9;
		public:
			static LPDIRECT3DDEVICE9 GetDirect3DDevice();
			static bool ApplyHooks();

			static void SetRenderStates( IDirect3DDevice9* device );
			static void FinishRenderStates( IDirect3DDevice9* device );
		};
	}
}