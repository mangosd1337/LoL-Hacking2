#include "stdafx.h"
#include "StaticEnums.h"

namespace EloBuddy
{
	public ref class GameEndEventArgs : public System::EventArgs
	{
	private:
		int m_winningTeam;
	public:
		delegate void GameEndEvent( System::EventArgs^ args );

		GameEndEventArgs(int winningTeam)
		{
			this->m_winningTeam = winningTeam;
		}

		property GameObjectTeam WinningTeam
		{
			GameObjectTeam get()
			{
				return (GameObjectTeam) m_winningTeam;
			}
		}

		property GameObjectTeam LosingTeam
		{
			GameObjectTeam get()
			{
				return m_winningTeam == 100
					? (GameObjectTeam) 200
					: (GameObjectTeam) 100;
			}
		}
	};
}