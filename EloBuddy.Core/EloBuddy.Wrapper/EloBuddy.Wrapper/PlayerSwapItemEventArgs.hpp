#include "stdafx.h"
#include "StaticEnums.h"

using namespace System;

namespace EloBuddy
{
	ref class AIHeroClient;

	public ref class PlayerSwapItemEventArgs : public System::EventArgs
	{
	private:
		AIHeroClient^ m_sender;
		int m_sourceSlotId;
		int m_targetSlotId;
		bool m_process;
	public:
		delegate void PlayerSwapItemEvent( AIHeroClient^ sender, PlayerSwapItemEventArgs^ args );

		PlayerSwapItemEventArgs( AIHeroClient^ sender, int sourceSlotId, int targetSlotId )
		{
			this->m_sender = sender;
			this->m_sourceSlotId = sourceSlotId;
			this->m_targetSlotId = targetSlotId;
			this->m_process = true;
		}

		property int From
		{
			int get()
			{
				return m_sourceSlotId;
			}
		}

		property int To
		{
			int get()
			{
				return m_targetSlotId;
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