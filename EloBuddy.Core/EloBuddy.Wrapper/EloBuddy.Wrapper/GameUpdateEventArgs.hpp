#include "stdafx.h"

namespace EloBuddy
{
	public ref class GameUpdateEventArgs : public System::EventArgs
	{
	private:
	public:
		delegate void GameOnUpdateEvent( System::EventArgs^ args );

		GameUpdateEventArgs( )
		{
		}
	};
}