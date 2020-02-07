#pragma once
#include "Macros.h"
#include "Obj_AI_Base.h"
#include "InventorySlot.h"
#include "Detour.hpp"
#include "ObjectManager.h"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT HeroInventory : Obj_AI_Base
		{
		public:
			static bool ApplyHooks();

			bool BuyItem( int itemId ) const;
			bool SellItem( int itemId ) const;
			bool SwapItem( uint sourceSlotId, uint destinationSlotId ) const;
			void UndoBuy() const;

			InventorySlot** GetInventory();
			InventorySlot* GetInventorySlot( int slot );

			InventorySlot* GetItemInfo( int itemId );
		};
	}
}
