#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/FoundryItemShop.h"
#include "../../EloBuddy.Core/EloBuddy.Core/AIHeroClient.h"
#include "../../EloBuddy.Core/EloBuddy.Core/HeroInventory.h"
#include "../../EloBuddy.Core/EloBuddy.Core/InventorySlot.h"
#include "../../EloBuddy.Core/EloBuddy.Core/ItemNode.h"
#include "../../EloBuddy.Core/EloBuddy.Core/EventHandler.h"

#include "Macros.hpp"
#include "StaticEnums.h"
#include "ShopActionEventArgs.hpp"
#include "ShopOpenEventArgs.h"
#include "ShopCloseEventArgs.h"
#include "ShopUndoPurchase.h"

using namespace System;

namespace EloBuddy
{
	MAKE_EVENT_GLOBAL( ShopBuyItem, AIHeroClient^ sender, ShopActionEventArgs^ args );
	MAKE_EVENT_GLOBAL( ShopSellItem, AIHeroClient^ sender, ShopActionEventArgs^ args );
	MAKE_EVENT_GLOBAL( ShopOpen, ShopOpenEventArgs^ args );
	MAKE_EVENT_GLOBAL( ShopClose, ShopCloseEventArgs^ args );
	MAKE_EVENT_GLOBAL( ShopUndo, ShopUndoPurchaseEventArgs^ args );

	public ref class Shop
	{
	internal:
		MAKE_EVENT_INTERNAL_PROCESS( ShopBuyItem, (Native::AIHeroClient*, int, Native::ItemNode*) );
		MAKE_EVENT_INTERNAL_PROCESS( ShopSellItem, (Native::AIHeroClient*, int, Native::ItemNode*) );
		MAKE_EVENT_INTERNAL_PROCESS( ShopOpen, () );
		MAKE_EVENT_INTERNAL_PROCESS( ShopClose, () );
		MAKE_EVENT_INTERNAL_PROCESS( ShopUndo, () );
	public:
		MAKE_EVENT_PUBLIC( OnBuyItem, ShopBuyItem );
		MAKE_EVENT_PUBLIC( OnSellItem, ShopSellItem );
		MAKE_EVENT_PUBLIC( OnOpen, ShopOpen );
		MAKE_EVENT_PUBLIC( OnClose, ShopClose );
		MAKE_EVENT_PUBLIC( OnUndo, ShopUndo );

		static Shop();
		Shop() {}
		static void DomainUnloadEventHandler( System::Object^, System::EventArgs^ );

		static bool BuyItem( int itemId );
		static bool BuyItem( ItemId item );
		static bool SellItem( int slot );
		static bool SellItem( SpellSlot slot );
		static void UndoPurchase();

		static void Open();
		static void Close();

		MAKE_STATIC_PROPERTY( IsOpen, bool );
		MAKE_STATIC_PROPERTY( CanShop, bool );
	};
}