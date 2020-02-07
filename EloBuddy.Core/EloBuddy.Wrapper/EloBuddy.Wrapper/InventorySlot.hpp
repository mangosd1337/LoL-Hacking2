#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/InventorySlot.h"
#include "../../EloBuddy.Core/EloBuddy.Core/AIHeroClient.h"
#include "../../EloBuddy.Core/EloBuddy.Core/ObjectManager.h"
#include "../../EloBuddy.Core/EloBuddy.Core/HeroInventory.h"
#include "../../EloBuddy.Core/EloBuddy.Core/RiotString.h"

#include "Macros.hpp"
#include "Exceptions.hpp"
#include "StaticEnums.h"
#include "ItemData.h"

using namespace System;
using namespace SharpDX;
using namespace System::Collections::Generic;

namespace EloBuddy
{
	ref class Obj_AI_Base;

	public ref class InventorySlot
	{
	internal:
		Native::InventorySlot* GetPtr();
		Native::ItemNode* GetItemNode();
		Native::ItemNode* m_itemNode;
	private:
		uint m_networkId;
		int m_slot;
	public:
		InventorySlot( uint networkId, int slot );

		MAKE_PROPERTY_INLINE( Charges, int, GetPtr() );
		MAKE_PROPERTY_INLINE( Stacks, int, GetPtr() );

		property EloBuddy::ItemId Id
		{
			EloBuddy::ItemId get();
		}

		property int Price
		{
			int get();
		}

		property int Slot
		{
			int get();
		}

		property String^ Name
		{
			String^ get();
		}

		property String^ DisplayName
		{
			String^ get();
		}

		property String^ Tooltip
		{
			String^ get();
		}

		property String^ Description
		{
			String^ get();
		}

		property EloBuddy::SpellSlot SpellSlot
		{
			EloBuddy::SpellSlot get();
		}

		property bool IsWard
		{
			bool get()
			{
				auto wardList = gcnew array<int>
				{
					2044, 2045, 2049, 2050, 3154, 3340, 3350, 3351
				};

				for each(auto itemId in wardList)
				{
					if (itemId == (int) this->Id) return true;
				}

				return false;
			}
		}

		bool Cast();
		bool Cast( Obj_AI_Base^ target );
		bool Cast( Vector3 position );
		bool Sell();
		bool CanUseItem();
	};
}