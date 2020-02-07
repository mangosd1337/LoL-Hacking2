#include "Stdafx.h"

#include "Spellbook.hpp"
#include "SpellDataInst.hpp"
#include "ObjectManager.hpp"

using namespace EloBuddy::Native;

namespace EloBuddy
{
	Spellbook::Spellbook(Native::GameObject* object)
	{
		this->self = (Native::Obj_AI_Base*)object;
		this->m_spellbook = this->GetSpellbook();
		this->m_networkId = *object->GetNetworkId();
	}
	
	static Spellbook::Spellbook()
	{
		ATTACH_DOMAIN();
		ATTACH_EVENT
		(
			SpellbookCastSpell,
			21, Native::OnSpellbookCastSpell, Native::Obj_AI_Base*, Native::Spellbook*, Native::Vector3f*, Native::Vector3f*, uint, int
		);
		ATTACH_EVENT
		(
			SpellbookStopCast,
			22, Native::OnSpellbookStopCast, Native::Obj_AI_Base*, bool, bool, bool, bool, int, int
		);
		ATTACH_EVENT
		(
			SpellbookUpdateChargeableSpell,
			23, Native::OnSpellbookUpdateChargeableSpell, Native::Spellbook*, int, Native::Vector3f*, bool
		);
	}

	void Spellbook::DomainUnloadEventHandler( System::Object^, System::EventArgs^ )
	{
		DETACH_EVENT
		(
			SpellbookCastSpell,
			21, Native::OnSpellbookCastSpell, Native::Obj_AI_Base*, Native::Spellbook*, Native::Vector3f*, Native::Vector3f*, uint, int
		);
		DETACH_EVENT
		(
			SpellbookStopCast,
			22, Native::OnSpellbookStopCast, Native::Obj_AI_Base*, bool, bool, bool, bool, int, int
		);
		DETACH_EVENT
		(
			SpellbookUpdateChargeableSpell,
			23, Native::OnSpellbookUpdateChargeableSpell, Native::Spellbook*, int, Native::Vector3f*, bool
		);
	}

	void Spellbook::OnSpellbookStopCastNative( Native::Obj_AI_Base* caster, bool stopAnimation, bool executeCastFrame, bool forceStop, bool destroyMissile, int missiletNetworkId, int counter )
	{
		START_TRACE
			Obj_AI_Base^ targetManaged;

			if (caster != nullptr)
			{
				targetManaged = (Obj_AI_Base^) ObjectManager::CreateObjectFromPointer( caster );
			}
			
			auto args = gcnew SpellbookStopCastEventArgs( stopAnimation, executeCastFrame, forceStop, destroyMissile, missiletNetworkId, counter );

			for each (auto eventHandle in SpellbookStopCastHandlers->ToArray( ))
			{
				START_TRACE
					eventHandle(
						targetManaged,
						args
					);
				END_TRACE
			}
		END_TRACE
	}

	bool Spellbook::OnSpellbookUpdateChargeableSpellNative( Native::Spellbook* caster, int slot, Native::Vector3f* position, bool releaseCast)
	{
		auto process = true;

		START_TRACE
			auto sb = gcnew Spellbook( Native::Spellbook::GetOwner(caster) );
			auto args = gcnew SpellbookUpdateChargeableSpellEventArgs( (SpellSlot) slot, SharpDX::Vector3( position->GetX(), position->GetZ(), position->GetY() ), releaseCast );

			for each (auto eventHandle in SpellbookUpdateChargeableSpellHandlers->ToArray( ))
			{
				START_TRACE
					eventHandle(
						sb,
						args
					);

				if (!args->Process)
					process = false;

				END_TRACE
			}
		END_TRACE

		return process;
	}

	bool Spellbook::OnSpellbookCastSpellNative( Native::Obj_AI_Base* caster, Native::Spellbook* spellbook, Native::Vector3f* srcVector, Native::Vector3f* dstVector, uint networkId, int slot )
	{
		bool process = true;

		START_TRACE
			auto startPos = Vector3( srcVector->GetX(), srcVector->GetZ(), srcVector->GetY() );
			auto dstPos = Vector3( dstVector->GetX(), dstVector->GetZ(), dstVector->GetY() );

			auto target = Native::ObjectManager::GetUnitByNetworkId( networkId );
			GameObject^ targetManaged;

			if (target != nullptr)
			{
				targetManaged = ObjectManager::CreateObjectFromPointer( target );
			}

			auto sb = gcnew Spellbook( caster );
			auto args = gcnew SpellbookCastSpellEventArgs( startPos, dstPos, targetManaged, (SpellSlot) slot );

			for each (auto eventHandle in SpellbookCastSpellHandlers->ToArray())
			{
				START_TRACE
					eventHandle(
						sb,
						args
					);

					if (!args->Process)
						process = false;
				END_TRACE
			}
		END_TRACE

		return process;
	}

	Native::Spellbook* Spellbook::GetSpellbook()
	{
		if (this->m_spellbook == nullptr)
		{
			this->m_spellbook = this->self->GetSpellbook();
		}

		if (this->m_spellbook == nullptr)
		{
			throw gcnew SpellbookNotFoundException();
		}

		return this->m_spellbook;
	}

	SpellState Spellbook::CanUseSpell(SpellSlot slot)
	{
		auto spellbook = this->GetSpellbook();

		if (spellbook != nullptr && static_cast<int>(slot) < SPELLBOOK_SIZE)
		{
			return static_cast<SpellState>(spellbook->CanUseSpell( static_cast<Native::SpellSlot>(slot) ));
		} 
		return SpellState::Unknown;
	}

	/*
	 * CastSpell overloads 
	*/

	bool Spellbook::CastSpell(SpellSlot slot)
	{
		return Spellbook::CastSpell( slot, true );
	}

	bool Spellbook::CastSpell(SpellSlot slot, GameObject^ target)
	{
		return Spellbook::CastSpell( slot, target, true );
	}

	bool Spellbook::CastSpell(SpellSlot slot, Vector3 startPosition, Vector3 endPosition)
	{
		return Spellbook::CastSpell( slot, startPosition, endPosition, true );
	}

	bool Spellbook::CastSpell(SpellSlot slot, Vector3 position)
	{
		return Spellbook::CastSpell( slot, position, true );
	}

	//TriggerEvent Overloads

	bool Spellbook::CastSpell(SpellSlot slot, bool triggerEvent)
	{
		if (this->GetSpellbook() != nullptr && static_cast<int>(slot) < SPELLBOOK_SIZE)
		{
			return this->GetSpellbook()->CastSpell( static_cast<Native::SpellSlot>(slot), this->self->GetPosition() );
		}
		return false;
	}

	bool Spellbook::CastSpell( SpellSlot slot, Vector3 position, bool triggerEvent )
	{
		if (this->GetSpellbook() != nullptr && static_cast<int>(slot) < SPELLBOOK_SIZE)
		{
			return this->GetSpellbook()->CastSpell( static_cast<Native::SpellSlot>(slot), Vector3f( position.X, position.Y, position.Z ), triggerEvent );
		}
		return false;
	}

	bool Spellbook::CastSpell( SpellSlot slot, GameObject^ target, bool triggerEvent )
	{
		if (this->GetSpellbook() != nullptr && static_cast<int>(slot) < SPELLBOOK_SIZE)
		{
			return this->GetSpellbook()->CastSpell( static_cast<Native::SpellSlot>(slot), target->GetPtr(), triggerEvent );
		}
		return false;
	}


	bool Spellbook::CastSpell( SpellSlot slot, Vector3 startPosition, Vector3 endPosition, bool triggerEvent )
	{
		if (this->GetSpellbook() != nullptr && static_cast<int>(slot) < SPELLBOOK_SIZE)
		{
			auto start = Vector3f( startPosition.X, startPosition.Y, startPosition.Z );
			auto end = Vector3f( endPosition.X, endPosition.Y, endPosition.Z );

			return this->GetSpellbook()->CastSpell( static_cast<Native::SpellSlot>(slot), start, end, triggerEvent );
		}

		return false;
	}

	bool Spellbook::UpdateChargeableSpell( SpellSlot slot, SharpDX::Vector3 position, bool releaseCast, bool triggerEvent )
	{
		if (this->GetSpellbook() != nullptr && static_cast<int>(slot) < SPELLBOOK_SIZE)
		{
			return this->GetSpellbook()->UpdateChargeableSpell( static_cast<Native::SpellSlot>(slot), Vector3f( position.X, position.Z, position.Y ), releaseCast, triggerEvent );
		}

		return false;
	}

	bool Spellbook::CanSpellBeUpgraded(SpellSlot slot)
	{
		auto sb = this->GetSpellbook();
		if (sb != nullptr)
		{
			return sb->SpellSlotCanBeUpgraded( static_cast<Native::SpellSlot>(slot) );
		}
		return false;
	}

	//@@@ TriggerEvent Overloads END

	bool Spellbook::UpdateChargeableSpell(SpellSlot slot, SharpDX::Vector3 position, bool releaseCast)
	{
		if (this->GetSpellbook() != nullptr && static_cast<int>(slot) < SPELLBOOK_SIZE)
		{
			return this->GetSpellbook()->UpdateChargeableSpell( static_cast<Native::SpellSlot>(slot), Vector3f( position.X, position.Z, position.Y ), releaseCast );
		}

		return false;
	}

	void Spellbook::EvolveSpell(SpellSlot slot)
	{
		if (this->GetSpellbook() != nullptr && static_cast<int>(slot) < SPELLBOOK_SIZE)
		{
			this->GetSpellbook()->EvolveSpell( static_cast<Native::SpellSlot>(slot) );
		}
	}

	SpellDataInst^ Spellbook::GetSpell(SpellSlot slot)
	{
		if (this->GetSpellbook() != nullptr && static_cast<int>(slot) < SPELLBOOK_SIZE)
		{
			Native::SpellDataInst* spellDataInst = this->GetSpellbook()->GetSpell( static_cast<Native::SpellSlot>(slot) );
			if (spellDataInst != nullptr)
			{
				if (spellDataInst->GetSData() == nullptr)
					return nullptr;

				return gcnew SpellDataInst( this->GetSpellbook()->GetSpell( static_cast<Native::SpellSlot>(slot) ), slot, this->GetSpellbook() );
			}
		}
		return nullptr;
	}

	bool Spellbook::LevelSpell(SpellSlot slot)
	{
		if (this->GetSpellbook() != nullptr && static_cast<int>(slot) < SPELLBOOK_SIZE)
		{
			return this->GetSpellbook()->LevelSpell( static_cast<Native::SpellSlot>(slot) );
		}
		return false;
	}

	List<SpellDataInst^>^ Spellbook::Spells::get()
	{
		auto list = gcnew List<SpellDataInst^>();
		for (int i = 0; i < SPELLBOOK_SIZE; i += 4)
		{
			Native::SpellDataInst* inst = this->GetSpellbook()->GetSpells()[i / 4];
			if (inst != nullptr)
			{
				list->Add( gcnew SpellDataInst( inst , static_cast<SpellSlot>(i/4), this->GetSpellbook() ) );
			}
		}
		return list;
	}

	Obj_AI_Base^ Spellbook::Owner::get()
	{
		return (Obj_AI_Base^) ObjectManager::Player;
	}

	//SpellCaster_Client
	float Spellbook::CastEndTime::get()
	{
		auto spellbook = this->GetSpellbook();
		if (spellbook != nullptr)
		{
			auto spellCaster = *spellbook->GetSpellCaster();
			if (spellCaster != nullptr)
			{
				return spellCaster->CastEndTime();
			}
		}

		return 0;
	}

	float Spellbook::CastTime::get()
	{
		auto spellbook = this->GetSpellbook();
		if (spellbook != nullptr)
		{
			auto spellCaster = *spellbook->GetSpellCaster();
			if (spellCaster != nullptr)
			{
				return spellCaster->CastEndTime();
			}
		}

		return 0;
	}

	bool Spellbook::IsAutoAttacking::get()
	{
		auto spellbook = this->GetSpellbook();
		if (spellbook != nullptr)
		{
			auto spellCaster = *spellbook->GetSpellCaster();
			if (spellCaster != nullptr)
			{
				return spellCaster->IsAutoAttacking();
			}
		}

		return false;
	}

	bool Spellbook::IsChanneling::get()
	{
		auto spellbook = this->GetSpellbook();
		if (spellbook != nullptr)
		{
			auto spellCaster = *spellbook->GetSpellCaster();
			if (spellCaster != nullptr)
			{
				return spellCaster->IsChanneling();
			}
		}

		return false;
	}

	bool Spellbook::IsCharging::get()
	{
		auto spellbook = this->GetSpellbook();
		if (spellbook != nullptr)
		{
			auto spellCaster = *spellbook->GetSpellCaster();
			if (spellCaster != nullptr)
			{
				return spellCaster->IsCharging();
			}
		}

		return false;
	}

	bool Spellbook::IsStopped::get()
	{
		auto spellbook = this->GetSpellbook();
		if (spellbook != nullptr)
		{
			auto spellCaster = *spellbook->GetSpellCaster();
			if (spellCaster != nullptr)
			{
				return spellCaster->IsStopped();
			}
		}

		return false;
	}

	bool Spellbook::SpellWasCast::get()
	{
		auto spellbook = this->GetSpellbook();
		if (spellbook != nullptr)
		{
			auto spellCaster = *spellbook->GetSpellCaster();
			if (spellCaster != nullptr)
			{
				return spellCaster->SpellWasCast();
			}
		}

		return false;
	}

	bool Spellbook::IsCastingSpell::get()
	{
		return CastEndTime != 0;
	}

	bool Spellbook::HasSpellCaster::get()
	{
		auto spellbook = this->GetSpellbook();
		if (spellbook != nullptr)
		{
			auto spellCaster = *spellbook->GetSpellCaster();
			return spellCaster != nullptr;
		}

		return false;
	}
}