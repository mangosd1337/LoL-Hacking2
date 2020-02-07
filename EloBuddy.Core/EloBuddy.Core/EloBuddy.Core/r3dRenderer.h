#pragma once
#include "Macros.h"
#include "Offsets.h"
#include <d3d9.h>
#include <D3dx9math.h>
#include "Vector3f.h"

namespace EloBuddy
{
	namespace Native
	{
		class r3dTexture;

		class
			DLLEXPORT r3dRenderer
		{
		public:
			static r3dRenderer* GetInstance();
			static LPDIRECT3DDEVICE9 GetDevice();

			MAKE_GET( Projection, D3DXMATRIX, Offsets::r3dRendererStruct::Projection );
			MAKE_GET( View, D3DXMATRIX, Offsets::r3dRendererStruct::View );
			MAKE_GET( ClientWidth, int, Offsets::r3dRendererStruct::ClientWidth );
			MAKE_GET( ClientHeight, int, Offsets::r3dRendererStruct::ClientHeight );

			static void __stdcall DrawCircularRangeIndicator( Vector3f* dstVector, float radius, int color, r3dTexture* texture );

			static void DrawTexture( std::string texture, Vector3f* dstVec, float radius, int color );
			static void DrawTexture( r3dTexture* texture, Vector3f* dstVec, float radius, int color );

			static void r3dScreenTo3D( float x, float y, Vector3f* vecOut, Vector3f* vecOut2);
			static bool r3dProjectToScreen( Vector3f* vecIn, Vector3f* vecOut );
		};
	}
}
