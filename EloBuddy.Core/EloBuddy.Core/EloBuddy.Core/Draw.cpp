#include "stdafx.h"
#include "Draw.h"
#include "Units.h"
#include "Utils.h"
#include <d3d9.h>
#include <D3dx9core.h>
#undef DrawText

namespace EloBuddy
{
	namespace Native
	{
		void Draw::FloatingText(char* Text, GameObject* GameObject, int Type)
		{
			//DrawFloatingText(MAKE_RELATIVE(ADDRESS_DRAW_FLOATINGTEXT), (__int32)GameObject, Type, Text, 0.0f, 0);
		}
	}
}