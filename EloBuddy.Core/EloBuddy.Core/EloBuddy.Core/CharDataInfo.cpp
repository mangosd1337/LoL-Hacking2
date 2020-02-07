#include "stdafx.h"
#include "CharDataInfo.h"
#include "Offsets.h"

namespace EloBuddy
{
	namespace Native
	{
		float CharDataInfo::GetStatPerLevel(byte stat) const
		{
			/*auto pGetStatPerLevel = MAKE_RVA( 0x766140 );

			__asm
			{
				mov esi, this
				add esi, 0x714
				mov ecx, [esi]
				push stat

				call [pGetStatPerLevel]
			}*/
			//TODO: Figure out 
			return 0.0f;
		}
	}
}