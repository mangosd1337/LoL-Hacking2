#include "stdafx.h"
#include "HeroInventory.h"
#include "AIHeroClient.h"
#include "EventHandler.h"

namespace EloBuddy
{
	namespace Native
	{
		MAKE_HOOK<convention_type::stdcall_t, bool, int> BuyItemEvent;
		MAKE_HOOK<convention_type::stdcall_t, bool, int> SellItemEvent;
		MAKE_HOOK<convention_type::cdecl_t, char, uint, uint> SwapItemEvent;

		bool HeroInventory::ApplyHooks()
		{
			BuyItemEvent.Apply( MAKE_RVA( Offsets::HeroInventory::BuyItem ), []( int itemId ) -> bool
			{
				AIHeroClient* sender = nullptr;
				ItemNode* item = nullptr;
				int* originalECX = nullptr;
				auto purchasedItem = 0;
				auto process = true;

				__asm
				{
					mov originalECX, ecx
					mov sender, eax
					mov item, ebx
					mov purchasedItem, esi
				}

#ifdef _DEBUG_BUILD
				Console::PrintLn( "BuyItem: %d, sender: %08x", purchasedItem, sender );
#endif

				process = EventHandler<27, OnPlayerBuyItem, AIHeroClient*, int, ItemNode*>::GetInstance( )->TriggerProcess( sender, purchasedItem, item );

				__asm
				{
					mov ecx, originalECX
					mov eax, sender
					mov esi, purchasedItem
				}

				return process ? BuyItemEvent.CallOriginal( itemId ) : false;
			} );

			SellItemEvent.Apply( MAKE_RVA( Offsets::HeroInventory::SellItem ), []( int itemId ) -> bool
			{
				ItemNode* item = nullptr;
				int* originalECX = nullptr;
				auto soldItem = 0;
				auto process = true;

				__asm
				{
					mov originalECX, ecx
					mov item, eax
					mov soldItem, edx
				}

#ifdef _DEBUG_BUILD
				Console::PrintLn( "SoldItem: %d, itemNode: %08x - Name: %s", soldItem, item, item->GetName() );
#endif

				process = EventHandler<28, OnPlayerSellItem, AIHeroClient*, int, ItemNode*>::GetInstance( )->TriggerProcess( ObjectManager::GetPlayer(), soldItem, item );

				__asm
				{
					mov ecx, originalECX
					mov eax, item
					mov edx, soldItem
				}

				return process ? SellItemEvent.CallOriginal( itemId ) : false;
			} );

			SwapItemEvent.Apply( MAKE_RVA( Offsets::HeroInventory::SwapItem ), [] ( uint sourceSlotId, uint targetSlotId ) -> char
			{
				__asm pushad;
					EventHandler<48, OnPlayerSwapItem, AIHeroClient*, uint, uint >::GetInstance()->TriggerProcess( ObjectManager::GetPlayer(), sourceSlotId, targetSlotId );
				__asm popad;

				return SwapItemEvent.CallOriginal( sourceSlotId, targetSlotId );
			} );

			return BuyItemEvent.IsApplied()
				&& SellItemEvent.IsApplied()
				&& SwapItemEvent.IsApplied();
		}

		bool HeroInventory::BuyItem( int itemId ) const
		{
			__asm
			{
				mov eax, this
				mov esi, itemId
			}

			return BuyItemEvent.CallOriginal( itemId );
		}

		bool HeroInventory::SellItem( int itemId ) const
		{
			__asm
			{
				mov ecx, this
				mov edx, itemId
			}

			return SellItemEvent.CallOriginal( itemId );
		}

		bool HeroInventory::SwapItem( uint sourceSlotId, uint destinationSlotId ) const
		{
			__asm mov ecx, this;
			return SwapItemEvent.CallOriginal( sourceSlotId, destinationSlotId );
		}

		void HeroInventory::UndoBuy() const
		{
			//reinterpret_cast<int( __stdcall* )()>(MAKE_RVA(Offsets::HeroInventory::UndoPurchase))();
		}

		InventorySlot** HeroInventory::GetInventory()
		{
			return reinterpret_cast<InventorySlot**>(this + static_cast<int>(Offsets::HeroInventory::InventoryPointer));
		}

		InventorySlot* HeroInventory::GetInventorySlot( int slot )
		{
			return this->GetInventory()[slot];
		}

		InventorySlot* HeroInventory::GetItemInfo( int itemId )
		{
			return nullptr;
		}
	}
}
