#pragma once
#include <d3d9.h>
#include <d3dx9.h>
#pragma comment(lib,"d3d9.lib")
#pragma comment(lib, "d3dx9.lib")

#include "Units.h"
#include "RiotX3D.h"
#include "r3dRenderer.h"

#undef DrawText
#define PI 3.14159265

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT Drawing
		{
		public:
			static ID3DXLine* g_pLine;
			static ID3DXFont* g_pFont;

			static Drawing* GetInstance();

			static void DrawFontText( float x, float y, D3DCOLOR color, char* text, int size = 25 );
			static void DrawLine( float x, float y, float x2, float y2, float thickness, D3DCOLOR color );
			static SIZE GetTextEntent( char* text, int size );

			static void DrawLogo();
		};
	}
}