#include "stdafx.h"
#include "Spellbook.h"
#include "SpellDataInst.h"
#include "EventHandler.h"
#include "AIHeroClient.h"
#include "ObjectManager.h"
#include "Humanizer.h"
#include "pwHud.h"

namespace EloBuddy
{
	namespace Native
	{
		MAKE_HOOK<convention_type::stdcall_t, void, SpellDataInst*, uint, Vector3f*, Vector3f*, uint> Spellbook_CastSpell;
		MAKE_HOOK<convention_type::stdcall_t, int, bool, bool, bool, bool, int, int, int> Spellbook_ForceStop;
		MAKE_HOOK<convention_type::stdcall_t, bool, SpellDataInst*, byte, Vector3f*, bool> Spellbook_UpdateChargeableSpell;

		Spellbook::Spellbook() { }

		bool Spellbook::ApplyHooks()
		{
			Spellbook_CastSpell.Apply(MAKE_RVA(Offsets::Spellbook::Client_DoCastSpell), [] (SpellDataInst* sdataInst, uint spellSlot, Vector3f* dstVector, Vector3f* srcVector, uint targetNetId) -> void
			{
				auto spellbook = nullptr;
				Obj_AI_Base* sender = nullptr;

				__asm mov spellbook, ecx;
				__asm mov sender, eax;

#ifdef _DEBUG_BUILD
				Console::PrintLn("CastSpell () - SDataInst: %08x Spellslot: %d, Source: %g %g %g, Dst: %g %g %g, TargetNetId: %d, Spellbook: %08x",
					sdataInst,
					spellSlot,
					srcVector->GetX(), srcVector->GetY(), srcVector->GetZ(),
					dstVector->GetX(), dstVector->GetY(), dstVector->GetZ(),
					targetNetId,
					spellbook
					);
#endif

				if (EventHandler<21, OnSpellbookCastSpell, Obj_AI_Base*, Spellbook*, Vector3f*, Vector3f*, uint, int>::GetInstance()->TriggerProcess(static_cast<Obj_AI_Base*>(GetOwner(spellbook)), spellbook, srcVector, dstVector, targetNetId, spellSlot))
				{
					__asm mov ecx, spellbook;
					__asm mov eax, sender;

					Spellbook_CastSpell.CallOriginal(sdataInst, spellSlot, dstVector, srcVector, targetNetId);
				}
			});

			Spellbook_ForceStop.Apply(MAKE_RVA(Offsets::Spellbook::Client_ForceStop), [] (bool stopAnimation, bool executeCastFrame, bool forceStop, bool destroyMissile, int unkn, int missileNetworkId, int counterForceStoppedAttacks) -> int
			{
				Obj_AI_Base* sender = nullptr;

				__asm mov sender, eax;

				sender = *reinterpret_cast<Obj_AI_Base**>(sender + 0x1);

				__asm pushad;
					EventHandler<22, OnSpellbookStopCast, Obj_AI_Base*, bool, bool, bool, bool, int, int>::GetInstance()->Trigger(sender, stopAnimation, executeCastFrame, forceStop, destroyMissile, missileNetworkId, counterForceStoppedAttacks);
				__asm popad;

				__asm mov eax, sender;

				return Spellbook_ForceStop.CallOriginal(stopAnimation, executeCastFrame, forceStop, destroyMissile, unkn, missileNetworkId, counterForceStoppedAttacks);
			});

			Spellbook_UpdateChargeableSpell.Apply(MAKE_RVA(Offsets::Spellbook::Client_UpdateChargeableSpell), [] (SpellDataInst* sdata, byte slot, Vector3f* position, bool releaseCast) -> bool
			{
				__asm pushad;
					EventHandler<23, OnSpellbookUpdateChargeableSpell, Spellbook*, int, Vector3f*, bool>::GetInstance()->Trigger(nullptr, slot, position, releaseCast);
				__asm popad;

				return Spellbook_UpdateChargeableSpell.CallOriginal(sdata, slot, position, releaseCast);
			});

			return Spellbook_CastSpell.IsApplied()
				&& Spellbook_UpdateChargeableSpell.IsApplied();
		}

		bool Spellbook::CastSpell(SpellSlot slot, Vector3f srcVector, Vector3f dstVector, DWORD targetNetworkId, bool triggerEvent)
		{
			static auto humanizer = Humanizer(100, 250);

			if (!humanizer.CanExecute(static_cast<byte>(slot)))
			{
				return false;
			}

			if (this->CanUseSpell(slot) == SpellState::Ready)
			{
				auto finalDstVector = new Vector3f(dstVector.GetX(), dstVector.GetZ(), dstVector.GetY());
				auto finalSrcVector = new Vector3f(srcVector.GetX(), srcVector.GetZ(), srcVector.GetY());

				/*{
					auto pwHud = pwHud::GetInstance();
					if (pwHud != nullptr)
					{
						auto cursorPosition = pwHud->GetHudManager()->GetVirtualCursorPos();
						if (cursorPosition->DistanceTo(*finalDstVector) > 70)
						{
							*pwHud->GetHudManager()->GetVirtualCursorPos() = cursorPosition->Randomize();
						}
					}
				}*/

				__asm
				{
					mov ecx, this
				}

				if (triggerEvent)
				{
					Spellbook_CastSpell.CallDetour(this->GetSpell(slot), static_cast<DWORD>(slot), finalDstVector, finalSrcVector, targetNetworkId);
				}
				else
				{
					Spellbook_CastSpell.CallOriginal(this->GetSpell(slot), static_cast<DWORD>(slot), finalDstVector, finalSrcVector, targetNetworkId);
				}

				return true;
			}

			return false;
		}

		bool Spellbook::CastSpell(SpellSlot slot, bool triggerEvent)
		{
			return this->CastSpell(slot, Vector3f(0, 0, 0), Vector3f(0, 0, 0), ObjectManager::GetPlayer()->GetNetworkId());
		}

		bool Spellbook::CastSpell(SpellSlot slot, GameObject* target, bool triggerEvent)
		{
			return this->CastSpell(slot, Vector3f(0, 0, 0), target->GetPosition(), *target->GetNetworkId(), triggerEvent);
		}

		bool Spellbook::CastSpell(SpellSlot slot, Vector3f position, bool triggerEvent)
		{
			return this->CastSpell(slot, Vector3f(0, 0, 0), position, 0, triggerEvent);
		}

		bool Spellbook::CastSpell(SpellSlot slot, Vector3f srcPosition, Vector3f dstPosition, bool triggerEvent)
		{
			return this->CastSpell(slot, srcPosition, dstPosition, 0, triggerEvent);
		}

		bool Spellbook::UpdateChargeableSpell(SpellSlot slot, Vector3f dstVec, bool releaseCast, bool triggerEvent)
		{
			if (this == nullptr)
			{
				return false;
			}

			__asm mov ecx, this;

			if (triggerEvent)
			{
				Spellbook_UpdateChargeableSpell.CallDetour(GetSpell(slot), static_cast<byte>(slot), &dstVec, releaseCast);
			}
			else
			{
				Spellbook_UpdateChargeableSpell.CallOriginal(GetSpell(slot), static_cast<byte>(slot), &dstVec, releaseCast);
			}

			return true;
		}

		bool Spellbook::EvolveSpell(SpellSlot slot)
		{
			return this->LevelSpell(slot);
		}

		bool Spellbook::LevelSpell(SpellSlot slot)
		{
			return
				reinterpret_cast<int(__thiscall*)(void*, const SpellSlot)>
				MAKE_RVA(Offsets::Spellbook::Client_LevelSpell)
				(this, slot);
		}

		bool Spellbook::SpellSlotCanBeUpgraded(SpellSlot slot) const
		{
			auto pExperience = ObjectManager::GetPlayer()->GetExperience();
			auto fSpellSlotCanBeUpgraded = MAKE_RVA(Offsets::Spellbook::Client_SpellSlotCanBeUpgraded);

			__asm
			{
				mov edx, pExperience
				mov ecx, slot
				push this

				call [fSpellSlotCanBeUpgraded]
			}
		}

		SpellState Spellbook::CanUseSpell(SpellSlot slot)
		{
			return
				reinterpret_cast<SpellState(__thiscall*)(void*, const SpellSlot, const DWORD &)>
				MAKE_RVA(Offsets::Spellbook::Client_GetSpellstate)
				(this, slot, NULL);
		}

		SpellDataInst** Spellbook::GetSpells()
		{
			return reinterpret_cast<SpellDataInst**>(this + static_cast<int>(Offsets::SpellbookStruct::GetSpell));
		}

		SpellDataInst* Spellbook::GetSpell(SpellSlot slot)
		{
			return this->GetSpells() [static_cast<int>(slot)];
		}

		GameObject* Spellbook::GetOwner(Spellbook* spellbook)
		{
			return ObjectManager::GetPlayer();
			//for (auto i = 0; i < 10000; i++)
			//{
			//	auto obj = ObjectManager::GetUnitByIndex( i );
			//	if (obj != nullptr)
			//	{
			//		if (obj->GetType() == UnitType::AIHeroClient)
			//		{
			//			auto hero = static_cast<AIHeroClient*>(obj);
			//			if (hero->GetSpellbook() == spellbook)
			//				return hero;
			//		}
			//	}
			//}

			//return nullptr;
		}
	}
}
