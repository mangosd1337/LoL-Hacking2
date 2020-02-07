#include "stdafx.h"

namespace EloBuddy
{
	public ref class GameStartEventArgs : public System::EventArgs
	{
	private:
	public:
		delegate void GameStartEvent( System::EventArgs^ args );

		GameStartEventArgs( )
		{
		}
	};
}