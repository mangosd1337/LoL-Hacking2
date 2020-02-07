#include "Stdafx.h"
#include "Shop.hpp"
#include "ObjectManager.hpp"

using namespace System;

namespace EloBuddy
{
	static Shop::Shop()
	{
		ATTACH_DOMAIN();

		ATTACH_EVENT
		(
			ShopBuyItem,
			27, Native::OnPlayerBuyItem, Native::AIHeroClient*, int, Native::ItemNode*
		);
		ATTACH_EVENT
		(
			ShopSellItem,
			28, Native::OnPlayerSellItem, Native::AIHeroClient*, int, Native::ItemNode*
		);
		ATTACH_EVENT
		(
			ShopOpen,
			52, Native::OnShopOpen
		);
		ATTACH_EVENT
		(
			ShopClose,
			53, Native::OnCloseShop
		);
		ATTACH_EVENT
		(
			ShopUndo,
			54, Native::OnUndoPurchase
		);
	}

	void Shop::DomainUnloadEventHandler( System::Object^, System::EventArgs^ )
	{
		DETACH_EVENT
		(
			ShopBuyItem,
			27, Native::OnPlayerBuyItem, Native::AIHeroClient*, int, Native::ItemNode*
		);
		DETACH_EVENT
		(
			ShopSellItem,
			28, Native::OnPlayerSellItem, Native::AIHeroClient*, int, Native::ItemNode*
		);
		DETACH_EVENT
		(
			ShopOpen,
			52, Native::OnShopOpen
		);
		DETACH_EVENT
		(
			ShopClose,
			53, Native::OnCloseShop
		);
		DETACH_EVENT
		(
			ShopUndo,
			54, Native::OnUndoPurchase
		);
	}

	bool Shop::OnShopBuyItemNative( Native::AIHeroClient* sender, int itemId, Native::ItemNode* item )
	{
		bool process = true;

		START_TRACE
			auto recipeItemIds = gcnew List<int>();

			AIHeroClient^ managedSender = (AIHeroClient^) ObjectManager::CreateObjectFromPointer( (Native::GameObject*) sender );
			auto args = gcnew ShopActionEventArgs( managedSender, itemId, *item->GetPrice(), *item->GetMaxStacks(), gcnew System::String( item->GetName() ), recipeItemIds->ToArray() );

			for each (auto eventHandle in ShopBuyItemHandlers->ToArray())
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

	bool Shop::OnShopSellItemNative( Native::AIHeroClient* sender, int itemId, Native::ItemNode* item )
	{
		bool process = true;

		START_TRACE
			auto recipeItemIds = gcnew List<int>();
			auto managedSender = (AIHeroClient^) ObjectManager::CreateObjectFromPointer( (Native::GameObject*) sender );
			auto args = gcnew ShopActionEventArgs( managedSender, itemId, *item->GetPrice(), *item->GetMaxStacks(), gcnew System::String( item->GetName() ), recipeItemIds->ToArray() );

			for each (auto eventHandle in ShopSellItemHandlers->ToArray())
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

	bool Shop::OnShopOpenNative()
	{
		bool process = true;

		START_TRACE
			auto args = gcnew ShopOpenEventArgs();

			for each(auto eventHandle in ShopOpenHandlers->ToArray())
			{
				eventHandle(args);

				if (!args->Process)
					process = false;
			}
		END_TRACE

		return process;
	}

	bool Shop::OnShopCloseNative()
	{
		bool process = true;

		START_TRACE
			auto args = gcnew ShopCloseEventArgs();

			for each(auto eventHandle in ShopCloseHandlers->ToArray())
			{
				eventHandle( args );

				if (!args->Process)
					process = false;
			}
			END_TRACE

		return process;
	}

	bool Shop::OnShopUndoNative()
	{
		bool process = true;

		START_TRACE
			auto args = gcnew ShopUndoPurchaseEventArgs();

			for each(auto eventHandle in ShopUndoHandlers->ToArray())
			{
				eventHandle( args );

				if (!args->Process)
					process = false;
			}
		END_TRACE

		return process;
	}

	bool Shop::BuyItem( int itemId )
	{
		auto player = Native::ObjectManager::GetPlayer();
		if (player != nullptr)
		{
			return player->GetInventory()->BuyItem( itemId );
		}

		return false;
	}

	bool Shop::BuyItem( ItemId item )
	{
		return Shop::BuyItem( static_cast<int>(item) );
	}

	bool Shop::SellItem( int slot )
	{
		auto player = Native::ObjectManager::GetPlayer();

		if (player != nullptr)
		{
			return player->GetInventory()->SellItem( slot );
		}

		return false;
	}

	bool Shop::SellItem( SpellSlot slot )
	{
		if (slot != SpellSlot::Item1
			|| slot != SpellSlot::Item2
			|| slot != SpellSlot::Item3
			|| slot != SpellSlot::Item4
			|| slot != SpellSlot::Item5
			|| slot != SpellSlot::Item6)
		{
			throw gcnew Exception("InvalidSlotException - Items only");
		}

		return Shop::SellItem( static_cast<int>(slot - SpellSlot::Summoner2 ) );
	}

	void Shop::UndoPurchase()
	{
		return Native::FoundryItemShop::UndoPurchase();
	}

	void Shop::Open()
	{
		Native::FoundryItemShop::OpenShop();
	}

	void Shop::Close()
	{
		Native::FoundryItemShop::CloseShop();
	}

	bool Shop::IsOpen::get()
	{
		auto shop = Native::FoundryItemShop::GetInstance();
		if (shop != nullptr)
		{
			return *shop->GetIsOpen();
		}

		return false;
	}

	bool Shop::CanShop::get()
	{
		auto player = Native::ObjectManager::GetPlayer();
		if (player != nullptr)
		{
			return player->Virtual_CanShop();
		}

		return false;
	}
}