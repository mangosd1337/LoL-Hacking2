#include "stdafx.h"

using namespace System;

namespace EloBuddy
{
	public ref class CameraZoomEventArgs : public System::EventArgs
	{
	private:
		bool m_process;
	public:
		delegate void CameraZoomEvent( CameraZoomEventArgs^ args );

		CameraZoomEventArgs()
		{
			this->m_process = true;
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