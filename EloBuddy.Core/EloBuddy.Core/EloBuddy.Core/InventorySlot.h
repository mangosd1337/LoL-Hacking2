#pragma once
#include "Utils.h"
#include "ItemNode.h"

namespace EloBuddy
{
	namespace Native
	{
		struct InventorySlotNode
		{
			byte unkn[0xC];
			ItemNode* itemInst; 
		};

		class
			DLLEXPORT InventorySlot
		{
		public:
			__forceinline InventorySlotNode* GetItemNode() { return *reinterpret_cast<InventorySlotNode**>(this); }

			MAKE_GET(Stacks, int, Offsets::InventorySlot::Stacks);
			MAKE_GET(Charges, int, Offsets::InventorySlot::Charges);
			MAKE_GET(PurchaseTime, float, Offsets::InventorySlot::PurchaseTime);
		};
	}
}
		