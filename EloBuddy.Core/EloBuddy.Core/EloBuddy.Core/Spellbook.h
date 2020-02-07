#pragma once
#include "GameObject.h"
#include "Detour.hpp"
#include "Vector3f.h"
#include "SpellCaster_Client.h"

namespace EloBuddy
{
	namespace Native
	{
		class SpellDataInst;
		class SpellData;
		class AIHeroClient;

		class
			DLLEXPORT Spellbook
		{
			static Spellbook* m_spellBook;

		public:
			Spellbook();
			static bool ApplyHooks();

			SpellState CanUseSpell( SpellSlot );

			bool CastSpell( SpellSlot slot, Vector3f srcVector, Vector3f dstVector, DWORD target, bool triggerEvent );
			bool CastSpell( SpellSlot slot, bool triggerEvent = false );
			bool CastSpell( SpellSlot slot, GameObject* target, bool triggerEvent = false );
			bool CastSpell( SpellSlot slot, Vector3f pos, bool triggerEvent = false );
			bool CastSpell( SpellSlot slot, Vector3f srcPosition, Vector3f dstPosition, bool triggerEvent = false );
			bool UpdateChargeableSpell( SpellSlot slot, Vector3f pos, bool releaseCast, bool triggerEvent = false );

			bool EvolveSpell( SpellSlot slot );
			bool LevelSpell( SpellSlot slot );
			bool SpellSlotCanBeUpgraded( SpellSlot slot ) const;

			SpellDataInst** GetSpells();
			SpellDataInst* GetSpell( SpellSlot slot );

			static GameObject* GetOwner( Spellbook* spellbook );
	
			MAKE_GET( ActiveSpellSlot, SpellSlot, Offsets::SpellbookStruct::ActiveSpellSlot );
			MAKE_GET( SelectedSpellSlot, SpellSlot, Offsets::SpellbookStruct::TargetType );
			MAKE_GET( SpellCaster, SpellCaster_Client*, Offsets::SpellbookStruct::SpellCaster_Client );
		};
	}
}
