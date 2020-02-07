#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/GameObject.h"
#include "../../EloBuddy.Core/EloBuddy.Core/Obj_AI_Base.h"
#include "../../EloBuddy.Core/EloBuddy.Core/Spellbook.h"

#include "StaticEnums.h"
#include "GameObject.hpp"

#include "CastSpellEventArgs.hpp"
#include "StopCastEventArgs.hpp"
#include "UpdateChargedSpellEventArgs.hpp"

#define SPELLBOOK_SIZE 0x40

using namespace SharpDX;

namespace EloBuddy
{
	ref class SpellDataInst;

	MAKE_EVENT_GLOBAL( SpellbookCastSpell, Spellbook^ sender, SpellbookCastSpellEventArgs^ args );
	MAKE_EVENT_GLOBAL( SpellbookStopCast, Obj_AI_Base^ sender, SpellbookStopCastEventArgs^ args );
	MAKE_EVENT_GLOBAL( SpellbookUpdateChargeableSpell, Spellbook^ sender, SpellbookUpdateChargeableSpellEventArgs^ args );

	public ref class Spellbook
	{
	internal:
		MAKE_EVENT_INTERNAL_PROCESS( SpellbookCastSpell, (Native::Obj_AI_Base*, Native::Spellbook*, Native::Vector3f*, Native::Vector3f*, uint, int) );
		MAKE_EVENT_INTERNAL( SpellbookStopCast, (Native::Obj_AI_Base*, bool, bool, bool, bool, int, int) );
		MAKE_EVENT_INTERNAL_PROCESS( SpellbookUpdateChargeableSpell, (Native::Spellbook*, int, Native::Vector3f*, bool) );
	private:
		Native::Obj_AI_Base* self;
		Native::Spellbook* m_spellbook;
		uint m_networkId;
	public:
		MAKE_EVENT_PUBLIC( OnCastSpell, SpellbookCastSpell );
		MAKE_EVENT_PUBLIC( OnStopCast, SpellbookStopCast );
		MAKE_EVENT_PUBLIC( OnUpdateChargeableSpell, SpellbookUpdateChargeableSpell );

		Spellbook( Native::GameObject* object );

		static Spellbook();
		static void DomainUnloadEventHandler( System::Object^, System::EventArgs^ );

		Native::Spellbook* GetSpellbook();
		SpellState CanUseSpell( SpellSlot slot );

		bool CastSpell( SpellSlot slot );
		bool CastSpell( SpellSlot slot, GameObject^ target );
		bool CastSpell( SpellSlot slot, Vector3 position );
		bool CastSpell( SpellSlot slot, Vector3 startPosition, Vector3 endPosition );

		bool CastSpell( SpellSlot slot, bool triggerEvent );
		bool CastSpell( SpellSlot slot, GameObject^ target, bool triggerEvent );
		bool CastSpell( SpellSlot slot, Vector3 position, bool triggerEvent );
		bool CastSpell( SpellSlot slot, Vector3 startPosition, Vector3 endPosition, bool triggerEvent );

		bool UpdateChargeableSpell( SpellSlot slot, Vector3 position, bool releaseCast );
		bool UpdateChargeableSpell( SpellSlot slot, Vector3 position, bool releaseCast, bool triggerEvent );

		bool CanSpellBeUpgraded( SpellSlot slot );

		void EvolveSpell( SpellSlot slot );

		SpellDataInst^ GetSpell( SpellSlot slot );
		bool LevelSpell( SpellSlot slot );

		/*
		* Member
		*/

		property System::Collections::Generic::List<SpellDataInst^>^ Spells
		{
			System::Collections::Generic::List<SpellDataInst^>^ get();
		}

		property SpellSlot ActiveSpellSlot
		{
			SpellSlot get()
			{
				if (this->GetSpellbook() == NULL)
				{
					return SpellSlot::Unknown;
				}
				return (SpellSlot) (*this->GetSpellbook()->GetActiveSpellSlot());
			}
		}

		property SpellSlot SelectedSpellSlot
		{
			SpellSlot get()
			{
				if (this->GetSpellbook() == NULL)
				{
					return SpellSlot::Unknown;
				}
				return (SpellSlot) (*this->GetSpellbook()->GetSelectedSpellSlot());
			}
		}

		MAKE_PROPERTY( Owner, Obj_AI_Base ^ );

		//SpellCaster_Client
		MAKE_PROPERTY( CastEndTime, float );
		MAKE_PROPERTY( CastTime, float );
		MAKE_PROPERTY( IsAutoAttacking, bool );
		MAKE_PROPERTY( IsChanneling, bool );
		MAKE_PROPERTY( IsCharging, bool );
		MAKE_PROPERTY( IsStopped, bool );
		MAKE_PROPERTY( SpellWasCast, bool );
		MAKE_PROPERTY( IsCastingSpell, bool );
		MAKE_PROPERTY( HasSpellCaster, bool );
	};
}