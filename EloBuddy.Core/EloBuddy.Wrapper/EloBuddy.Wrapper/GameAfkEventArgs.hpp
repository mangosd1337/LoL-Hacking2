#include "stdafx.h"
#include "StaticEnums.h"

namespace EloBuddy
{
	public ref class GameAfkEventArgs : public System::EventArgs
	{
	private:
		bool m_process;
	public:
		delegate void GameAfkEvent( System::EventArgs^ args );

		GameAfkEventArgs( )
		{
			this->m_process = false;
		}

		property bool Process
		{
			bool get()
			{
				return this->m_process;
			}
			void set( bool value )
			{
				this->m_process = value;
			}
		}
	};
}