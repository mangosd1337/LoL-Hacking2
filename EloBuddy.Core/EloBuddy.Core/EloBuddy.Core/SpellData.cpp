#include "stdafx.h"
#include "SpellData.h"

namespace EloBuddy
{
	namespace Native
	{
		SpellData* SpellData::FindSpell(char* spellName)
		{
			return FindSpell(HashSpell(spellName));
		}

		SpellData* SpellData::FindSpell(uint hash)
		{
			//int SpellDataIO_Load = *reinterpret_cast<DWORD*>(MAKE_RVA( Offsets::SpellData::SpellManagerInst ) + 0x10);

			//return
			//	reinterpret_cast<SpellData*( __thiscall* )(void*, int)>
			//	(SpellDataIO_Load)
			//	(reinterpret_cast<void*>(MAKE_RVA(Offsets::SpellData::SpellManagerInst)), hash);
			return nullptr;
		}

		int SpellData::HashSpell(char* spellName)
		{
			return NULL;
			//return
			//	reinterpret_cast<int( __stdcall* )(char*)>
			//	MAKE_RVA( Offsets::SpellData::RiotHashString )
			//	(spellName);
		}
	}
}