#include "stdafx.h"
#include "Player.hpp"

#include "../../EloBuddy.Core/EloBuddy.Core/AvatarPimpl.h"
#include "../../EloBuddy.Core/EloBuddy.Core/AIHeroClient.h"
#include "../../EloBuddy.Core/EloBuddy.Core/HeroInventory.h"
#include "../../EloBuddy.Core/EloBuddy.Core/ObjectManager.h"
#include "../../EloBuddy.Core/EloBuddy.Core/MenuGUI.h"

namespace EloBuddy
{
	static Player::Player()
	{
		self = Native::ObjectManager::GetPlayer();
		m_instance = gcnew AIHeroClient( *self->GetIndex(), *self->GetNetworkId(), self );

		ATTACH_DOMAIN();
		ATTACH_EVENT
		(
			Player_ProcessIssueOrder,
			15, Native::OnObjAIBaseIssueOrder, Native::Obj_AI_Base*, uint, Native::Vector3f*, Native::GameObject*, bool
		);
		ATTACH_EVENT
		(
			Player_SwapItem,
			48, Native::OnPlayerSwapItem, Native::AIHeroClient*, uint, uint
		);
		ATTACH_EVENT
		(
			Player_DoEmote,
			72, Native::OnPlayerDoEmote, Native::AIHeroClient*, short
		);
	}

	void Player::DomainUnloadEventHandler( System::Object^, System::EventArgs^ )
	{
		DETACH_EVENT
		(
			Player_ProcessIssueOrder,
			15, Native::OnObjAIBaseIssueOrder, Native::Obj_AI_Base*, uint, Native::Vector3f*, Native::GameObject*, bool
		);
		DETACH_EVENT
		(
			Player_SwapItem,
			48, Native::OnPlayerSwapItem, Native::AIHeroClient*, uint, uint
		);
		DETACH_EVENT
		(
			Player_DoEmote,
			72, Native::OnPlayerDoEmote, Native::AIHeroClient*, short
		);
	}

	bool Player::OnPlayer_ProcessIssueOrderNative( Native::Obj_AI_Base* unit, uint order, Native::Vector3f* pos, Native::GameObject* target, bool isAttackMove )
	{
		bool process = true;

		START_TRACE
			auto targetPos = Vector3( pos->GetX(), pos->GetZ(), pos->GetY() );
			Obj_AI_Base^ sender = (Obj_AI_Base^)ObjectManager::CreateObjectFromPointer( unit );
			GameObject^ targetManaged = nullptr;

			if (target != nullptr)
			{
				targetManaged = (AttackableUnit^)ObjectManager::CreateObjectFromPointer( target );
			}

			auto args = gcnew PlayerIssueOrderEventArgs( (GameObjectOrder)order, targetPos, targetManaged, isAttackMove );

			for each (auto eventHandle in Player_ProcessIssueOrderHandlers->ToArray())
			{
				START_TRACE
					eventHandle(
						sender,
						args
					);
	
					if (!args->Process)
						process = false;
				END_TRACE
			}
			END_TRACE

			return process;
	}

	bool Player::OnPlayer_SwapItemNative( Native::AIHeroClient* sender, uint srcSlot, uint dstSlot )
	{
		bool process = true;

		START_TRACE
			auto managedSender = (AIHeroClient^) ObjectManager::CreateObjectFromPointer( (Native::GameObject*) sender );
			auto args = gcnew PlayerSwapItemEventArgs( managedSender, srcSlot, dstSlot );

			for each (auto eventHandle in Player_SwapItemHandlers->ToArray())
			{
				START_TRACE
					eventHandle(
						managedSender,
						args
					);

				if (!args->Process)
					process = false;
				END_TRACE
			}
		END_TRACE

		return process;
	}

	bool Player::OnPlayer_DoEmoteNative(Native::AIHeroClient* sender, short emoteId)
	{
		bool process = true;

		START_TRACE
			auto managedSender = (AIHeroClient^) ObjectManager::CreateObjectFromPointer( sender );
			auto args = gcnew PlayerDoEmoteEventArgs( managedSender, emoteId );

			for each (auto eventHandle in Player_DoEmoteHandlers->ToArray())
			{
				START_TRACE
					eventHandle(
						managedSender,
						args
					);

					if (!args->Process)
						process = false;
				END_TRACE
			}
		END_TRACE

		return process;
	}

	bool Player::SwapItem( int sourceSlotId, int targetSlotId )
	{
		return self->GetInventory()->SwapItem( sourceSlotId, targetSlotId );
	}

	bool Player::UseObject( GameObject ^ obj )
	{
		return self->UseObject( (Native::Obj_AI_Base*) Native::ObjectManager::GetUnitByNetworkId( obj->NetworkId ) );
	}

	bool Player::IssueOrder( GameObjectOrder order, Vector3 targetPos )
	{
		return Player::IssueOrder( order, targetPos, true );
	}

	bool Player::IssueOrder( GameObjectOrder order, GameObject^ targetUnit )
	{
		return Player::IssueOrder( order, targetUnit, true );
	}

	bool Player::IssueOrder( GameObjectOrder order, Vector3 targetPos, bool triggerEvent )
	{
		if (order == GameObjectOrder::AttackUnit)
			return false;

		return self->IssueOrder( &Native::Vector3f( targetPos.X, targetPos.Z, targetPos.Y ), nullptr, static_cast<Native::GameObjectOrder>(order), triggerEvent );
	}

	bool Player::IssueOrder( GameObjectOrder order, GameObject^ targetUnit, bool triggerEvent )
	{
		if (targetUnit != nullptr)
		{
			auto nativeObj = Native::ObjectManager::GetUnitByNetworkId( targetUnit->NetworkId );

			if (nativeObj != nullptr)
			{
				return self->IssueOrder( &Native::Vector3f( targetUnit->Position.X, targetUnit->Position.Z, targetUnit->Position.Y ), nativeObj, static_cast<Native::GameObjectOrder>(order), triggerEvent );
			}
		}

		return false;
	}

	//Spellbook
	SpellState Player::CanUseSpell( SpellSlot slot )
	{
		return m_instance->Spellbook->CanUseSpell( slot );
	}

	bool Player::CastSpell( SpellSlot slot )
	{
		return m_instance->Spellbook->CastSpell( slot );
	}

	bool Player::CastSpell( SpellSlot slot, GameObject^ target )
	{
		return m_instance->Spellbook->CastSpell( slot, target );
	}

	bool Player::CastSpell( SpellSlot slot, Vector3 position )
	{
		return m_instance->Spellbook->CastSpell( slot, position );
	}

	bool Player::CastSpell( SpellSlot slot, Vector3 startPosition, Vector3 endPosition )
	{
		return m_instance->Spellbook->CastSpell( slot, startPosition, endPosition );
	}

	bool Player::UpdateChargeableSpell( SpellSlot slot, Vector3 position, bool releaseCast )
	{
		return m_instance->Spellbook->UpdateChargeableSpell( slot, position, releaseCast );
	}

	//Spellbook TriggerEvent Overloads
	bool Player::CastSpell( SpellSlot slot, bool triggerEvent )
	{
		return m_instance->Spellbook->CastSpell( slot, triggerEvent );
	}

	bool Player::CastSpell( SpellSlot slot, GameObject^ target, bool triggerEvent )
	{
		return m_instance->Spellbook->CastSpell( slot, target, triggerEvent );
	}

	bool Player::CastSpell( SpellSlot slot, Vector3 position, bool triggerEvent )
	{
		return m_instance->Spellbook->CastSpell( slot, position, triggerEvent );
	}

	bool Player::CastSpell( SpellSlot slot, Vector3 startPosition, Vector3 endPosition, bool triggerEvent )
	{
		return m_instance->Spellbook->CastSpell( slot, startPosition, endPosition, triggerEvent );
	}

	bool Player::UpdateChargeableSpell( SpellSlot slot, Vector3 position, bool releaseCast, bool triggerEvent )
	{
		return m_instance->Spellbook->UpdateChargeableSpell( slot, position, releaseCast, triggerEvent );
	}


	void Player::EvolveSpell( SpellSlot slot )
	{
		m_instance->Spellbook->EvolveSpell( slot );
	}

	SpellDataInst^ Player::GetSpell( SpellSlot slot )
	{
		return m_instance->Spellbook->GetSpell( slot );
	}

	bool Player::LevelSpell( SpellSlot slot )
	{
		return m_instance->Spellbook->LevelSpell( slot );
	}

	List<SpellDataInst^>^ Player::Spells::get()
	{
		return m_instance->Spellbook->Spells;
	}

	array<Vector3>^ Player::GetPath( Vector3 end )
	{
		return m_instance->GetPath( end );
	}

	array<Vector3>^ Player::GetPath( Vector3 start, Vector3 end )
	{
		return m_instance->GetPath( start, end );
	}

	array<Vector3>^ Player::GetPath( Vector3 end, bool smoothPath )
	{
		return m_instance->GetPath( end, smoothPath );
	}

	array<Vector3>^ Player::GetPath( Vector3 start, Vector3 end, bool smoothPath )
	{
		return m_instance->GetPath( start, end, smoothPath );
	}

	bool Player::DoMasteryBadge()
	{
		auto menuGUI = Native::MenuGUI::GetInstance();
		if (menuGUI != nullptr)
		{
			return menuGUI->DoMasteryBadge();
		}
		return false;
	}
}