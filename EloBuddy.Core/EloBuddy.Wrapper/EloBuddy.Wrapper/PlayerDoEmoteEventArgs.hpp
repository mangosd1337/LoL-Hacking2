#include "stdafx.h"
#include "StaticEnums.h"

using namespace System;

namespace EloBuddy
{
	ref class AIHeroClient;

	public ref class PlayerDoEmoteEventArgs : public System::EventArgs
	{
	private:
		short m_emoteId;
		bool m_process;
	public:
		delegate void PlayerDoEmoteEvent( AIHeroClient^ sender, PlayerDoEmoteEventArgs^ args );

		PlayerDoEmoteEventArgs( AIHeroClient^ sender, short emoteId)
		{
			this->m_emoteId = emoteId;
			this->m_process = true;
		}

		property Emote EmoteId
		{
			Emote get()
			{
				return static_cast<Emote>(m_emoteId);
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