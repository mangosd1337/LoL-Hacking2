#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/r3dTexture.h"
#include "../../EloBuddy.Core/EloBuddy.Core/r3dRenderLayer.h"

#include "Macros.hpp"

using namespace System;
using namespace SharpDX;

namespace EloBuddy
{
	public ref class ManagedTexture
	{
	internal:
		Native::r3dTexture* m_texture;
	private:
		String^ m_textureName;
		IntPtr m_texturePtr;

		Vector3 m_position;
		System::Drawing::Color m_color;
		float m_size;
	public:
		ManagedTexture( String^ texture );
		ManagedTexture( String^ texture, Vector3 position, System::Drawing::Color color, float size );

		void Load();

		void Draw();
		void Draw( Vector3 position );
		void Draw( Vector3 position, float size );
		void Draw( Vector3, float size, System::Drawing::Color c );

		property String^ TextureName
		{
			String^ get()
			{
				return m_textureName;
			}
			void set(String^ texture)
			{
				m_textureName = texture;
				Load();
			}
		}

		property IntPtr* NativePointer
		{
			IntPtr* get()
			{
				return (IntPtr*) m_texture;
			}
		}

		property IntPtr ManagedPointer
		{
			IntPtr get()
			{
				return m_texturePtr;
			}
		}
	};
}