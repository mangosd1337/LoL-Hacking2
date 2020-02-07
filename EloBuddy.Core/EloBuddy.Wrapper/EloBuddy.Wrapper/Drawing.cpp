#include "Stdafx.h"

#include "../../EloBuddy.Core/EloBuddy.Core/EventHandler.h"
#include "../../EloBuddy.Core/EloBuddy.Core/Drawing.h"
#include "../../EloBuddy.Core/EloBuddy.Core/RiotX3D.h"
#include "../../EloBuddy.Core/EloBuddy.Core/r3dRenderer.h"
#include "../../EloBuddy.Core/EloBuddy.Core/MenuGUI.h"

#include "Drawing.h"
#include "Macros.hpp"
#include "NavMesh.h"

using namespace System::Runtime::InteropServices;
using namespace SharpDX::Direct3D9;

namespace EloBuddy
{
	static Drawing::Drawing()
	{
		ATTACH_DOMAIN();

		ATTACH_EVENT( DrawingBeginScene, 5, Native::OnDrawingBeginScene );
		ATTACH_EVENT( DrawingDraw, 6, Native::OnDrawingDraw );
		ATTACH_EVENT( DrawingEndScene, 7, Native::OnDrawingEndScene );
		ATTACH_EVENT( DrawingPostReset, 8, Native::OnDrawingPostReset );
		ATTACH_EVENT( DrawingPreReset, 9, Native::OnDrawingPreReset );
		ATTACH_EVENT( DrawingPresent, 10, Native::OnDrawingPresent );
		ATTACH_EVENT( DrawingSetRenderTarget, 11, Native::OnDrawingSetRenderTarget );
		ATTACH_EVENT( DrawingFlushEndScene, 45, Native::OnDrawingFlushEndScene );
		//ATTACH_EVENT( DrawingHealthbars, 80, Native::OnDrawingHealthBars, Native::UnitInfoComponent*, Native::AttackableUnit*);
	}

	void Drawing::DomainUnloadEventHandler( System::Object^, System::EventArgs^ )
	{
		DETACH_EVENT( DrawingBeginScene, 5, Native::OnDrawingBeginScene );
		DETACH_EVENT( DrawingDraw, 6, Native::OnDrawingDraw );
		DETACH_EVENT( DrawingEndScene, 7, Native::OnDrawingEndScene );
		DETACH_EVENT( DrawingPostReset, 8, Native::OnDrawingPostReset );
		DETACH_EVENT( DrawingPreReset, 9, Native::OnDrawingPreReset );
		DETACH_EVENT( DrawingPresent, 10, Native::OnDrawingPresent );
		DETACH_EVENT( DrawingSetRenderTarget, 11, Native::OnDrawingSetRenderTarget );
		DETACH_EVENT( DrawingFlushEndScene, 45, Native::OnDrawingFlushEndScene );
		//DETACH_EVENT( DrawingHealthbars, 80, Native::OnDrawingHealthBars, Native::UnitInfoComponent*, Native::AttackableUnit* );
	}

	void Drawing::OnDrawingBeginSceneNative()
	{
		START_TRACE
			for each (auto eventHandle in DrawingBeginSceneHandlers->ToArray( ))
			{
				START_TRACE
					eventHandle( EventArgs::Empty );
				END_TRACE
			}
		END_TRACE
	}

	void Drawing::OnDrawingDrawNative()
	{
		START_TRACE
			for each (auto eventHandle in DrawingDrawHandlers->ToArray( ))
			{
				START_TRACE
					eventHandle( EventArgs::Empty );
				END_TRACE
			}
		END_TRACE
	}

	void Drawing::OnDrawingEndSceneNative()
	{
		START_TRACE
			for each (auto eventHandle in DrawingEndSceneHandlers->ToArray( ))
			{
				START_TRACE
					eventHandle( EventArgs::Empty );
				END_TRACE
			}
		END_TRACE
	}

	void Drawing::OnDrawingPostResetNative()
	{
		START_TRACE
			for each (auto eventHandle in DrawingPostResetHandlers->ToArray( ))
			{
				START_TRACE
					eventHandle( EventArgs::Empty );
				END_TRACE
			}
		END_TRACE
	}

	void Drawing::OnDrawingPreResetNative()
	{
		START_TRACE
			for each (auto eventHandle in DrawingPreResetHandlers->ToArray( ))
			{
				START_TRACE
					eventHandle( EventArgs::Empty );
				END_TRACE
			}
		END_TRACE
	}

	void Drawing::OnDrawingPresentNative()
	{
		START_TRACE
			for each (auto eventHandle in DrawingPresentHandlers->ToArray())
			{
				START_TRACE
					eventHandle( EventArgs::Empty );
				END_TRACE
			}
		END_TRACE
	}

	void Drawing::OnDrawingSetRenderTargetNative()
	{
		START_TRACE
			for each (auto eventHandle in DrawingSetRenderTargetHandlers->ToArray( ))
			{
				START_TRACE
					eventHandle( EventArgs::Empty );
				END_TRACE
			}
		END_TRACE
	}

	void Drawing::OnDrawingFlushEndSceneNative()
	{
		START_TRACE
			for each (auto eventHandle in DrawingFlushEndSceneHandlers->ToArray())
			{
				START_TRACE
					eventHandle( EventArgs::Empty );
				END_TRACE
			}
		END_TRACE
	}

	/*bool Drawing::OnDrawingHealthbarsNative(Native::UnitInfoComponent* infoComponent, Native::AttackableUnit* sender)
	{
		START_TRACE
			if (infoComponent != nullptr)
			{
				auto args = gcnew OnDrawHealthbarEventArgs( infoComponent, sender );

				for each(auto eventHandle in DrawingHealthbarsHandlers->ToArray())
				{
					START_TRACE
						eventHandle( args );
					END_TRACE
				}
			}
		END_TRACE

		return true;
	}*/

	void Drawing::DrawText( SharpDX::Vector2 position, System::Drawing::Color color, System::String^ text, int size )
	{
		auto hText = Marshal::StringToHGlobalAnsi( text );
		Native::Drawing::GetInstance()->DrawFontText( position.X, position.Y, color.ToArgb(), (char*)hText.ToPointer(), size );
		Marshal::FreeHGlobal( hText );
	}

	void Drawing::DrawText( float x, float y, System::Drawing::Color color, System::String^ text )
	{
		auto hText = Marshal::StringToHGlobalAnsi( text );
		Native::Drawing::GetInstance()->DrawFontText( x, y, color.ToArgb(), (char*)hText.ToPointer(), 14 );
		Marshal::FreeHGlobal( hText );
	}

	void Drawing::DrawText( float x, float y, System::Drawing::Color color, System::String^ text, int size )
	{
		auto hText = Marshal::StringToHGlobalAnsi( text );
		Native::Drawing::GetInstance()->DrawFontText( x, y, color.ToArgb(), (char*) hText.ToPointer(), size );
		Marshal::FreeHGlobal( hText );
	}

	System::Drawing::Size Drawing::GetTextEntent( System::String^ text, int fontsize )
	{
		System::Drawing::Size size;
		SIZE tagsize;

		auto hText = Marshal::StringToHGlobalAnsi( text );
		tagsize = Native::Drawing::GetTextEntent( (char*)hText.ToPointer(), fontsize );

		size.Width = tagsize.cx;
		size.Height = tagsize.cy;

		Marshal::FreeHGlobal( hText );

		return size;
	}

	void Drawing::DrawLine( SharpDX::Vector2 start, SharpDX::Vector2 end, float thickness, System::Drawing::Color color )
	{
		Native::Drawing::DrawLine( start.X, start.Y, end.X, end.Y, thickness, color.ToArgb() );
	}

	void Drawing::DrawLine( float x, float y, float x2, float y2, float thickness, System::Drawing::Color color )
	{
		Native::Drawing::DrawLine( x, y, x2, y2, thickness, color.ToArgb() );
	}

	void Drawing::DrawCircle( Vector3 pos, float radius, System::Drawing::Color color )
	{
		Native::r3dRenderer::DrawCircularRangeIndicator( &Native::Vector3f( pos.X, pos.Y, pos.Z ), radius, color.ToArgb(), nullptr );
	}

	SharpDX::Direct3D9::Device^ Drawing::Direct3DDevice::get()
	{
		IDirect3DDevice9* d3d9Device = Native::RiotX3D::GetDirect3DDevice();
		if (d3d9Device != nullptr
			&& d3d9Device != Drawing::oldDX)
		{
			Drawing::oldDX = d3d9Device;
			Drawing::m_device = gcnew SharpDX::Direct3D9::Device((IntPtr)d3d9Device);
		}

		return Drawing::m_device;
	}

	int Drawing::Height::get()
	{
		return *Native::r3dRenderer::GetInstance()->GetClientHeight();
	}

	int Drawing::Width::get()
	{
		return *Native::r3dRenderer::GetInstance()->GetClientWidth();
	}

	Vector2 Drawing::WorldToMinimap( Vector3 worldCoord )
	{
		return TacticalMap::WorldToMinimap( worldCoord );
	}

	Vector3 Drawing::ScreenToWorld( Vector2 pos )
	{
		auto vector3f = Native::Vector3f( 0, 0, 0 );
		auto vector3f2 = Native::Vector3f( 0, 0, 0 );

		Native::r3dRenderer::r3dScreenTo3D( pos.X, pos.Y, &vector3f, &vector3f2 );

		auto num = vector3f2.GetZ() * 1000;
		auto num2 = vector3f2.GetY() * 1000;
		auto num3 = vector3f2.GetX() * 1000;

		return Vector3(vector3f.GetX() + num3, vector3f.GetZ() + num, vector3f.GetY() + num2);
	} 

	Vector3 Drawing::ScreenToWorld( float x, float y )
	{
		return Drawing::ScreenToWorld( Vector2( x, y ) );
	}

	Vector2 Drawing::WorldToScreen( Vector3 worldCoord )
	{
		auto vecIn = Native::Vector3f( worldCoord.X, worldCoord.Y, worldCoord.Z );
		auto vecOut = Native::Vector3f();
		Native::r3dRenderer::r3dProjectToScreen( &vecIn, &vecOut );

		return Vector2( vecOut.GetX(), vecOut.GetY() );
	}

	bool Drawing::WorldToScreen( Vector3 worldCoord, [Out] Vector2% screenCoord )
	{
		auto vecIn = Native::Vector3f( worldCoord.X, worldCoord.Y, worldCoord.Z );
		auto vecOut = Native::Vector3f();
		bool projectToScreen = Native::r3dRenderer::r3dProjectToScreen( &vecIn, &vecOut );
		screenCoord = Vector2( vecOut.GetX(), vecOut.GetY() );
		return projectToScreen;
	}

	Matrix Drawing::Projection::get()
	{
		D3DMATRIX matrix = *Native::r3dRenderer::GetInstance()->GetProjection();
		return Matrix(
			matrix._11, matrix._12, matrix._13, matrix._14,
			matrix._21, matrix._22, matrix._23, matrix._24,
			matrix._31, matrix._32, matrix._33, matrix._34,
			matrix._41, matrix._42, matrix._43, matrix._44 );
	}

	Matrix Drawing::View::get()
	{
		D3DMATRIX matrix = *Native::r3dRenderer::GetInstance()->GetView();
		return Matrix(
			matrix._11, matrix._12, matrix._13, matrix._14,
			matrix._21, matrix._22, matrix._23, matrix._24,
			matrix._31, matrix._32, matrix._33, matrix._34,
			matrix._41, matrix._42, matrix._43, matrix._44 );
	}

	void Drawing::DrawTexture( String^ texture, Vector3 worldCoord, float radius, System::Drawing::Color color )
	{
		auto r3dRenderer = Native::r3dRenderer::GetInstance();
		if (r3dRenderer != nullptr)
		{
			r3dRenderer->DrawTexture( std::string( DEF_INLINE_STRING( texture ) ), &Native::Vector3f( worldCoord.X, worldCoord.Y, worldCoord.Z ), radius, color.ToArgb() );
		}
	}

	void Drawing::DrawTexture( ManagedTexture^ texture, Vector3 worldCoord, float radius, System::Drawing::Color color )
	{
		auto r3dRenderer = Native::r3dRenderer::GetInstance();
		if (r3dRenderer != nullptr)
		{
			r3dRenderer->DrawTexture( static_cast<Native::r3dTexture*>(texture->m_texture), &Native::Vector3f( worldCoord.X, worldCoord.Y, worldCoord.Z ), radius, color.ToArgb() );
		}
	}

	void Drawing::DrawTexture( IntPtr* texture, Vector3 worldCoord, float radius, System::Drawing::Color color )
	{
		auto r3dRenderer = Native::r3dRenderer::GetInstance();
		if (r3dRenderer != nullptr)
		{
			r3dRenderer->DrawTexture( reinterpret_cast<Native::r3dTexture*>(texture), &Native::Vector3f( worldCoord.X, worldCoord.Y, worldCoord.Z ), radius, color.ToArgb() );
		}
	}
}