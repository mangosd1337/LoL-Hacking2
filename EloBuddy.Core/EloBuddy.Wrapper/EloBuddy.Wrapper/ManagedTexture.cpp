#include "stdafx.h"
#include "ManagedTexture.h"
#include "Drawing.h"

namespace EloBuddy
{
	ManagedTexture::ManagedTexture(String^ texture)
	{
		this->m_textureName = texture;
		Load();
	}

	ManagedTexture::ManagedTexture( String^ texture, Vector3 position, System::Drawing::Color color, float size )
	{
		this->m_textureName = texture;
		this->m_position = position;
		this->m_color = color;
		this->m_size = size;
		Load();
	}

	void ManagedTexture::Load()
	{
		m_texture = Native::r3dRenderLayer::LoadTexture( new std::string( DEF_INLINE_STRING(m_textureName) ) );
		m_texturePtr = (IntPtr) m_texture;
	}

	void ManagedTexture::Draw()
	{
		if (m_texture != nullptr)
		{
			Drawing::DrawTexture( this, m_position, m_size, m_color );
		}
	}

	void ManagedTexture::Draw( Vector3 position )
	{
		if (m_texture != nullptr)
		{
			Drawing::DrawTexture( this, position, 250, System::Drawing::Color::White );
		}
	}

	void ManagedTexture::Draw( Vector3 position, float size )
	{
		if (m_texture != nullptr)
		{
			Drawing::DrawTexture( this, position, size, System::Drawing::Color::White );
		}
	}

	void ManagedTexture::Draw( Vector3 position, float size, System::Drawing::Color color )
	{
		if (m_texture != nullptr)
		{
			Drawing::DrawTexture( this, position, size, color );
		}
	}
}