#include "Stdafx.h"
#include "InventorySlot.hpp"

#include "Player.hpp"
#include "Spellbook.hpp"
#include "SpellDataInst.hpp"
#include "Shop.hpp"

namespace EloBuddy
{
	InventorySlot::InventorySlot( uint networkId, int slot )
	{
		this->m_networkId = networkId;
		this->m_slot = slot;
	}

	Native::InventorySlot* InventorySlot::GetPtr()
	{
		auto ptr = static_cast<Native::Obj_AI_Base*>(Native::ObjectManager::GetUnitByNetworkId( this->m_networkId ));

		if (ptr != nullptr)
		{
			auto inventory = ptr->GetInventory();
			if (inventory != nullptr)
			{
				return inventory->GetInventorySlot( this->m_slot );
			}
		}

		throw gcnew InventorySlotNotFoundException();
	}

	Native::ItemNode* InventorySlot::GetItemNode()
	{
		if (this->m_itemNode != nullptr)
		{
			return this->m_itemNode;
		}

		auto ptr = this->GetPtr();
		if (ptr != nullptr)
		{
			auto itemNode = ptr->GetItemNode();
			if (itemNode != nullptr)
			{
				auto itemInst = itemNode->itemInst;
				if (itemInst != nullptr)
				{
					this->m_itemNode = itemInst;
				}
			}
		}

		return this->m_itemNode;
	}

	int InventorySlot::Slot::get()
	{
		return this->m_slot;
	}

	EloBuddy::ItemId InventorySlot::Id::get()
	{
		auto itemNode = this->GetItemNode();
		if (itemNode != nullptr)
		{
			return static_cast<EloBuddy::ItemId>(*itemNode->GetItemId());
		}

		return EloBuddy::ItemId::Unknown;
	}

	int InventorySlot::Price::get()
	{
		auto itemNode = this->GetItemNode();
		if (itemNode != nullptr)
		{
			return *itemNode->GetPrice();
		}

		return 0;
	}

	String^ InventorySlot::Name::get()
	{
		auto itemNode = this->GetItemNode();
		String^ str = "Unknown";

		if (itemNode != nullptr)
		{
			str = gcnew String( itemNode->GetName() );

			if (String::IsNullOrEmpty(str) || str == "Unknown")
			{
				str = Enum::GetName( ItemId::typeid, static_cast<Object^>(this->Id) );
			}
		}

		return str;
	}

	String^ InventorySlot::DisplayName::get()
	{
		auto itemNode = this->GetItemNode();
		if (itemNode != nullptr)
		{
			auto itemScript = *itemNode->GetItemScript();
			if (itemScript != nullptr)
			{
				auto displayNameHash = itemScript->GetDisplayName( static_cast<int>(this->Id) );
				if (displayNameHash != nullptr)
				{
					auto translated = Native::RiotString::TranslateString( displayNameHash );
					if (translated != nullptr)
					{
						return gcnew String( translated );
					}
				}
			}
		}
		return "Unknown";
	}

	String^ InventorySlot::Description::get()
	{
		auto itemNode = this->GetItemNode();
		if (itemNode != nullptr)
		{
			auto itemScript = *itemNode->GetItemScript();
			if (itemScript != nullptr)
			{
				auto descriptionHash = itemScript->GetDescription( static_cast<int>(this->Id) );
				if (descriptionHash != nullptr)
				{
					auto translated = Native::RiotString::TranslateString( descriptionHash );
					if (translated != nullptr)
					{
						return gcnew String( translated );
					}
				}
			}
		}
		return "Unknown";
	}

	String^ InventorySlot::Tooltip::get()
	{
		auto itemNode = this->GetItemNode();
		if (itemNode != nullptr)
		{
			auto itemScript = *itemNode->GetItemScript();
			if (itemScript != nullptr)
			{
				auto tooltipHash = itemScript->GetTooltip( static_cast<int>(this->Id) );
				if (tooltipHash != nullptr)
				{
					auto translated = Native::RiotString::TranslateString( tooltipHash );
					if (translated != nullptr)
					{
						return gcnew String( translated );
					}
				}
			}
		}
		return "Unknown";
	}

	SpellSlot InventorySlot::SpellSlot::get()
	{
		return static_cast<EloBuddy::SpellSlot>(this->m_slot + static_cast<int>(EloBuddy::SpellSlot::Item1));
	}

	bool InventorySlot::Cast()
	{
		return Player::CastSpell( SpellSlot );
	}

	bool InventorySlot::Cast( Obj_AI_Base^ target )
	{
		return Player::CastSpell( SpellSlot, target );
	}

	bool InventorySlot::Cast( Vector3 position )
	{
		return Player::CastSpell( SpellSlot, position );
	}

	bool InventorySlot::CanUseItem()
	{
		for each(auto spell in Player::Spells)
		{
			if (spell->Slot == SpellSlot)
			{
				return spell->State == SpellState::Ready;
			}
		}

		return false;
	}

	bool InventorySlot::Sell()
	{
		return Shop::SellItem( static_cast<int>(this->Id) );
	}
}