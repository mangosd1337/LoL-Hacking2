#include "Stdafx.h"
#include "SpellDataInst.hpp"
#include "SpellData.hpp"

namespace EloBuddy
{
	SpellDataInst::SpellDataInst(Native::SpellDataInst* spellDataInst, SpellSlot slot, Native::Spellbook* spellbook)
	{
		this->m_spellDataInst = spellDataInst;
		this->m_slot = slot;
		this->m_spellbook = spellbook;
	}

	SpellData^ SpellDataInst::SData::get()
	{
		return gcnew SpellData(this->m_spellDataInst->GetSData());
	}

	SpellSlot SpellDataInst::Slot::get( )
	{
		return this->m_slot;
	}

	SpellState SpellDataInst::State::get()
	{
		if (this->m_spellbook != NULL && static_cast<int>(this->m_slot) < 0x3C)
		{
			return static_cast<SpellState>(this->m_spellbook->CanUseSpell( static_cast<Native::SpellSlot>(this->m_slot) ));
		}
		return SpellState::Unknown;
	}
}