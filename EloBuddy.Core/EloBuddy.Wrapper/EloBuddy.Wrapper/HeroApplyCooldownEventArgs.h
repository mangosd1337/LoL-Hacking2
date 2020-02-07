#include "stdafx.h"
#include "SpellDataInst.hpp"

namespace EloBuddy
{
	ref class AIHeroClient;

	public ref class OnHeroApplyCoolDownEventArgs : public System::EventArgs
	{
	private:
		SpellDataInst^ m_sdata;
		AIHeroClient^ m_sender;
		SpellSlot m_slot;
	public:
		delegate void OnHeroApplyCoolDownEvent( OnHeroApplyCoolDownEventArgs^ args );

		OnHeroApplyCoolDownEventArgs( AIHeroClient^ sender, SpellDataInst^ sdata, SpellSlot slot)
		{
			this->m_sender = sender;
			this->m_sdata = sdata;
			this->m_slot = slot;
		}

		property AIHeroClient^ Sender
		{
			AIHeroClient^ get()
			{
				return this->m_sender;
			}
		}

		property SpellDataInst^ SData
		{
			SpellDataInst^ get()
			{
				return this->m_sdata;
			}
		}

		property SpellSlot Slot
		{
			SpellSlot get()
			{
				return m_slot;
			}
		}

		property float Start
		{
			float get()
			{
				return this->m_sdata->Cooldown;
			}
		}

		property float End
		{
			float get()
			{
				return this->m_sdata->CooldownExpires;
			}
		}
	};
}