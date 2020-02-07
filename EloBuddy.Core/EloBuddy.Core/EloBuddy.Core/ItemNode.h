#pragma once
#include "Utils.h"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT ItemScript
		{
		public:
			inline char* GetDisplayName(int itemId)
			{
				auto baseString = std::string("game_item_displayname_");
				baseString.append(std::to_string(itemId).c_str());

				return const_cast<char*>(baseString.c_str());
			}


			inline char* GetDescription(int itemId)
			{
				auto baseString = std::string("game_item_description_");
				baseString.append(std::to_string(itemId).c_str());

				return const_cast<char*>(baseString.c_str());
			}

			inline char* GetTooltip(int itemId)
			{
				auto baseString = std::string("game_item_tooltip_");
				baseString.append(std::to_string(itemId).c_str());

				return const_cast<char*>(baseString.c_str());
			}
		};

		class
			DLLEXPORT ItemNode
		{
		public:
			MAKE_GET(Slot, int, Offsets::ItemNode::Slot);
			MAKE_GET(DisplayName, std::string, Offsets::ItemNode::Name);
			MAKE_GET(ItemId, int, Offsets::ItemNode::ItemId);
			MAKE_GET(Price, int, Offsets::ItemNode::ItemCost);
			MAKE_GET(MaxStacks, int, Offsets::ItemNode::MaxStacks);
			MAKE_GET(RecipeItemIds, int, Offsets::ItemNode::RecipeItemIds);
			MAKE_GET(ItemScript, ItemScript*, Offsets::ItemNode::BuffScript);

			inline char* GetName()
			{
				return *reinterpret_cast<char**>(this + static_cast<int>(Offsets::ItemNode::Name));
			}
		};
	}
}
