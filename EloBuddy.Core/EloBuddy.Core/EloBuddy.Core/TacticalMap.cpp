#include "stdafx.h"
#include "TacticalMap.h"
#include "Console.h"

namespace EloBuddy
{
	namespace Native
	{
		TacticalMap* TacticalMap::GetInstance()
		{
			return *reinterpret_cast<TacticalMap**>(*reinterpret_cast<DWORD**>(MAKE_RVA(Offsets::HudManager::HudManagerInst)) + 0x2E);
		}

		Vector3f* TacticalMap::ToWorldCoord(float x, float y) const
		{
			auto worldCoord = new Vector3f;

			__asm
			{
				movss xmm1, x
				movss xmm2, y
			}

			reinterpret_cast<void(__thiscall*)(const TacticalMap*, Vector3f*)>
				MAKE_RVA(Offsets::TacticalMap::ToWorldCoord)
				(this, worldCoord);

			return worldCoord;
		}

		bool TacticalMap::ToMapCoord(Vector3f* vecIn, float* xOut, float* yOut) const
		{
			return reinterpret_cast<bool(__thiscall*)(const TacticalMap*, Vector3f*, float*, float*)>
				MAKE_RVA(Offsets::TacticalMap::ToMapCoord)
				(this, vecIn, xOut, yOut);
		}
	}
}