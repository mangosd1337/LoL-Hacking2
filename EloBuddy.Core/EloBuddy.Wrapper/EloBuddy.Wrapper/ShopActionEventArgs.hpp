#include "stdafx.h"
#include "StaticEnums.h"

using namespace System;

namespace EloBuddy
{
	ref class AIHeroClient;

	public ref class ShopActionEventArgs : public System::EventArgs
	{
	private:
		AIHeroClient^ m_sender;
		int m_itemId;
		String^ m_name;
		int m_price;
		int m_maxStacks;
		array<int>^ m_recipeItemIds;
		bool m_process;
		EloBuddy::ItemId m_ItemId;
	public:
		delegate void ShopActionEvent( AIHeroClient^ sender, ShopActionEventArgs^ args );

		ShopActionEventArgs( AIHeroClient^ sender, int managedItemId, int price, int maxStacks, String^ name, array<int>^ recipeItemIds )
		{
			this->m_sender = sender;
			this->m_itemId = managedItemId;
			this->m_process = true;
			this->m_price = price;
			this->m_maxStacks = maxStacks;
			this->m_name = name;
			this->m_recipeItemIds = recipeItemIds;

			if (this->m_name->Length == 0 || this->m_name == "Unknown")
			{
				m_name = Enum::GetName( ItemId::typeid, (Object^)(ItemId)this->m_ItemId );
			}
		}

		property int Id
		{
			int get()
			{
				return this->m_itemId;
			}
		}

		property int Price
		{
			int get()
			{
				return this->m_price;
			}
		}

		property int MaxStacks
		{
			int get()
			{
				return this->m_maxStacks;
			}
		}

		property String^ Name
		{
			String^ get()
			{
				return this->m_name;
			}
		}

		property array<int>^ RecipeItemIds
		{
			array<int>^ get()
			{
				return this->m_recipeItemIds;
			}
		}

		property bool Process
		{
			bool get()
			{
				return this->m_process;
			}
			void set( bool process )
			{
				this->m_process = process;
			}
		}
	};
}