#include "stdafx.h"

using namespace System;
using namespace SharpDX;


namespace EloBuddy
{
	public ref class CameraUpdateEventArgs : public System::EventArgs
	{
	private:
		bool m_process;
		float m_mouseX;
		float m_mouseY;
	public:
		delegate void CameraUpdateEvent( CameraUpdateEventArgs^ args );

		CameraUpdateEventArgs(float mouseX, float mouseY)
		{
			this->m_process = true;
		}

		property Vector2 Mouse2D
		{
			Vector2 get()
			{
				return Vector2( m_mouseX, m_mouseY );
			}
		}

		property bool Process
		{
			bool get()
			{
				return this->m_process;
			}
			void set( bool process )
			{
				this->m_process = process;
			}
		}
	};
}