#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/SpellDataInst.h"
#include "../../EloBuddy.Core/EloBuddy.Core/Spellbook.h"
#include "Macros.hpp"

#include "StaticEnums.h"
#include "Exceptions.hpp"

namespace EloBuddy
{
	ref class SpellData;

	public ref class SpellDataInst
	{
	private:
		Native::SpellDataInst* m_spellDataInst;
		Native::Spellbook* m_spellbook;
		SpellSlot m_slot;
	public:
		SpellDataInst(Native::SpellDataInst* spellDataInst, SpellSlot slot, Native::Spellbook* spellbook);

		Native::SpellDataInst* GetSpellDataInst()
		{
			if (this->m_spellDataInst != nullptr)
			{
				return this->m_spellDataInst;
			}

			//gcnew SpellDataInstNotFoundException( );
			return nullptr;
		}

		MAKE_PROPERTY_INLINE( Ammo, int, GetSpellDataInst( ) );
		MAKE_PROPERTY_INLINE( AmmoRechargeStart, float, GetSpellDataInst( ) );
		MAKE_PROPERTY_INLINE( Cooldown, float, GetSpellDataInst( ) );
		MAKE_PROPERTY_INLINE( CooldownExpires, float, GetSpellDataInst( ) );
		MAKE_PROPERTY_INLINE( ToggleState, int, GetSpellDataInst( ) );
		MAKE_PROPERTY_INLINE( Level, int, GetSpellDataInst( ) );

		property bool IsUpgradable
		{
			bool get()
			{
				auto sb = this->m_spellbook;
				if (sb != nullptr)
				{
					return sb->SpellSlotCanBeUpgraded( static_cast<Native::SpellSlot>(m_slot) );
				}
				return false;
			}
		}

		property System::String^ Name
		{
			System::String^ get()
			{
				if (this->GetSpellDataInst() != NULL)
				{
					Native::SpellData* SData = this->GetSpellDataInst()->GetSData();
					if (SData != nullptr)
					{
						return gcnew System::String( this->GetSpellDataInst()->GetName()->c_str() );
					}
				}

				return "Unknown";
			}
		}

		property bool IsReady
		{
			bool get() { return this->State == SpellState::Ready; }
		}

		property bool IsLearned
		{
			bool get() { return this->Level > 0; }
		}

		property bool IsOnCooldown
		{
			bool get( ) { return this->State == SpellState::Cooldown; }
		}

		MAKE_PROPERTY( Slot, SpellSlot );
		MAKE_PROPERTY( State, SpellState );

		property SpellData^ SData
		{
			SpellData^ get();
		}
	};
}