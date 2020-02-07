#include "stdafx.h"
#include "HudVote.h"
#include "Detour.hpp"
#include "ObjectManager.h"
#include "AIHeroClient.h"

namespace EloBuddy
{
	namespace Native
	{
		MAKE_HOOK<convention_type::stdcall_t, bool, uint, int, char> Hud_OnSurrenderVote;

		bool HudVote::ApplyHooks()
		{
			Hud_OnSurrenderVote.Apply(MAKE_RVA(Offsets::HudVote::OnSurrenderVote), [] (uint networkId, int surrenderType, char unkn2) -> bool
			{
				__asm pushad;
					auto pPlayer = reinterpret_cast<AIHeroClient*>(ObjectManager::GetUnitByNetworkId(networkId));
					EventHandler<74, OnObjAIBaseSurrenderVote, Obj_AI_Base*, byte>::GetInstance()->Trigger(pPlayer, surrenderType);
				__asm popad;

				Hud_OnSurrenderVote.CallOriginal(networkId, surrenderType, unkn2);
			});

			return Hud_OnSurrenderVote.IsApplied();
		}
	}
}