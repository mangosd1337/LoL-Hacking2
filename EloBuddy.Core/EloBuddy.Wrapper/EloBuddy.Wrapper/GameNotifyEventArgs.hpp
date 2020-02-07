#include "stdafx.h"
#include "StaticEnums.h"

using namespace System;

namespace EloBuddy
{
	public ref class GameNotifyEventArgs : public System::EventArgs
	{
	private:
		uint m_networkId;
		GameEventId m_eventId;
	public:
		delegate void GameNotifyEvent( System::EventArgs^ args );

		GameNotifyEventArgs( GameEventId eventId, uint networkId )
		{
			this->m_eventId = eventId;
			this->m_networkId = networkId;
		}

		property uint NetworkId
		{
			uint get()
			{
				return m_networkId;
			}
		}

		property GameEventId EventId
		{
			GameEventId get()
			{
				return m_eventId;
			}
		}
	};
}