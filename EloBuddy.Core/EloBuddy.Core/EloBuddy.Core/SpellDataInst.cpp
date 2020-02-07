#include "stdafx.h"
#include "SpellDataInst.h"

namespace EloBuddy
{
	namespace Native
	{
		SpellData* __stdcall SpellDataInst::GetSData()
		{
			//return *(SpellData**) 
			//	(DWORD*) ((DWORD) *(DWORD**) ((DWORD) this + (int) Offsets::SpellDataInst::SpellData) + 0x34) + 0x10;
			return *reinterpret_cast<SpellData**>(this + static_cast<int>(Offsets::SpellDataInst::SpellData));
		}
	}
}