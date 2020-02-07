#pragma once

#include "StaticEnums.h"
#include "Macros.hpp"

using namespace SharpDX;

namespace EloBuddy
{
	[Obsolete("Please use EloBuddy.SDK.ItemData")]
	public ref class ItemData
	{
	private:
		uint m_itemId;
	public:
		ItemData::ItemData( uint itemId )
		{
			this->m_itemId = itemId;
		}
	};
}