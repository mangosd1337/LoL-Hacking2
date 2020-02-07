#pragma once
#include "Macros.hpp"

#include "OnBeginScene.hpp"
#include "OnEndScene.hpp"
#include "OnReset.hpp"
#include "OnPresent.hpp"
#include "OnDraw.hpp"
#include "TacticalMap.hpp"
#include "ManagedTexture.h"
#include "OnDrawHealthbarsEventArgs.h"

#undef DrawText

using namespace System;
using namespace SharpDX;
using namespace System::Runtime::InteropServices;
using namespace System::Collections::Generic;
using namespace System::Drawing;

namespace EloBuddy
{
	MAKE_EVENT_GLOBAL( DrawingBeginScene, EventArgs^ args );
	MAKE_EVENT_GLOBAL( DrawingDraw, EventArgs^ args );
	MAKE_EVENT_GLOBAL( DrawingEndScene, EventArgs^ args );
	MAKE_EVENT_GLOBAL( DrawingPostReset, EventArgs^ args );
	MAKE_EVENT_GLOBAL( DrawingPreReset, EventArgs^ args );
	MAKE_EVENT_GLOBAL( DrawingPresent, EventArgs^ args );
	MAKE_EVENT_GLOBAL( DrawingSetRenderTarget, EventArgs^ args );
	MAKE_EVENT_GLOBAL( DrawingFlushEndScene, EventArgs^ args );
	//MAKE_EVENT_GLOBAL( DrawingHealthbars, OnDrawHealthbarEventArgs^ args );

	public ref class Drawing
	{
	internal:
		MAKE_EVENT_INTERNAL( DrawingBeginScene, () );
		MAKE_EVENT_INTERNAL( DrawingDraw, () );
		MAKE_EVENT_INTERNAL( DrawingEndScene, () );
		MAKE_EVENT_INTERNAL( DrawingPostReset, () );
		MAKE_EVENT_INTERNAL( DrawingPreReset, () );
		MAKE_EVENT_INTERNAL( DrawingPresent, () );
		MAKE_EVENT_INTERNAL( DrawingSetRenderTarget, () );
		MAKE_EVENT_INTERNAL( DrawingFlushEndScene, () );
		//MAKE_EVENT_INTERNAL_PROCESS( DrawingHealthbars, (Native::UnitInfoComponent*, Native::AttackableUnit*) );

		static SharpDX::Direct3D9::Device^ m_device;
		static void* oldDX;
	public:
		MAKE_EVENT_PUBLIC( OnBeginScene, DrawingBeginScene );
		MAKE_EVENT_PUBLIC( OnDraw, DrawingDraw );
		MAKE_EVENT_PUBLIC( OnEndScene, DrawingEndScene );
		MAKE_EVENT_PUBLIC( OnPostReset, DrawingPostReset );
		MAKE_EVENT_PUBLIC( OnPreReset, DrawingPreReset );
		MAKE_EVENT_PUBLIC( OnPresent, DrawingPresent );
		MAKE_EVENT_PUBLIC( OnSetRenderTarget, DrawingSetRenderTarget );
		MAKE_EVENT_PUBLIC( OnFlushEndScene, DrawingFlushEndScene );
		//MAKE_EVENT_PUBLIC( OnDrawingHealthbars, DrawingHealthbars );

		static Drawing();
		static void DomainUnloadEventHandler( System::Object^, System::EventArgs^ );

		static void DrawText( SharpDX::Vector2 position, System::Drawing::Color color, System::String^ text, int size );
		static void DrawText( float x, float y, System::Drawing::Color color, System::String^ text );
		static void DrawText( float x, float y, System::Drawing::Color color, System::String^ text, int size );

		static void DrawLine( SharpDX::Vector2 start, SharpDX::Vector2 end, float thickness, System::Drawing::Color color );
		static void DrawLine( float x, float y, float x2, float y2, float thickness, System::Drawing::Color color );
		static System::Drawing::Size GetTextEntent( System::String^ text, int size );

		static Vector3 ScreenToWorld( Vector2 pos );
		static Vector3 ScreenToWorld( float x, float y );

		static Vector2 WorldToMinimap( Vector3 worldCoord );

		static Vector2 WorldToScreen( Vector3 worldCoord );
		static bool WorldToScreen( Vector3 worldCoord, [Out] Vector2% screenCoord );

		//DrawCircle
		static void DrawCircle( SharpDX::Vector3 pos, float radius, System::Drawing::Color color );

		static property SharpDX::Direct3D9::Device^ Direct3DDevice
		{
			SharpDX::Direct3D9::Device^ get();
		}

		static void DrawTexture( String^ texture, Vector3 worldCoord, float radius, System::Drawing::Color color);
		static void DrawTexture( ManagedTexture^ texture, Vector3 worldCoord, float radius, System::Drawing::Color color );
		static void DrawTexture( IntPtr* texture, Vector3 worldCoord, float radius, System::Drawing::Color color );

		MAKE_STATIC_PROPERTY( Height, int );
		MAKE_STATIC_PROPERTY( Width, int );
		MAKE_STATIC_PROPERTY( Projection, Matrix );
		MAKE_STATIC_PROPERTY( View, Matrix );
	};
}