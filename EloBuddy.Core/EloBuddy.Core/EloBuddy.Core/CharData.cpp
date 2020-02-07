#include "stdafx.h"
#include "Obj_AI_Base.h"

namespace EloBuddy
{
	namespace Native
	{
		CharDataInfo* CharData::GetCharDataInfo()
		{
			return *reinterpret_cast<CharDataInfo**>(reinterpret_cast<int>(this) + 0x1C);
		}
	}
}