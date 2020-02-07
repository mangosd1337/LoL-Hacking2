#include "stdafx.h"
#include "MissionInfo.h"
#include "Detour.hpp"
#include "GameObject.h"
#include "Obj_AI_Base.h"
#include "ObjectManager.h"
#include "AIHeroClient.h"

#define MIDHOOK_BEGIN __asm pushad; __asm pushfd;
#define MIDHOOK_END   __asm popad; __asm popfd;
#include "Hacks.h"

namespace EloBuddy
{
	namespace Native
	{
		MAKE_HOOK<convention_type::cdecl_t, bool, Obj_AI_Base*> MissionInfo_TurretIndicators;

		bool MissionInfo::ApplyHooks()
		{
			MissionInfo_TurretIndicators.Apply(MAKE_RVA(Offsets::MissionInfo::DrawTurretRange), [] (Obj_AI_Base* turret) -> bool
			{
				Obj_AI_Base* sender = nullptr;
				__asm mov sender, esi;

				__asm pushad;
				if (Hacks::GetTowerRanges() && DrawTurret(sender))
				{
					__asm popad;
					__asm mov sender, esi;
					return true;
				}
				__asm popad;

				__asm mov esi, sender;

				return MissionInfo_TurretIndicators.CallOriginal(turret);
			});

			return MissionInfo_TurretIndicators.IsApplied();
		}

		MissionInfo* MissionInfo::GetInstance()
		{
			return *reinterpret_cast<MissionInfo**>(MAKE_RVA(Offsets::MissionInfo::MissionInfoInst));
		}

		bool MissionInfo::DrawTurret(Obj_AI_Base* turret)
		{
			auto player = ObjectManager::GetPlayer();
			if (player != nullptr && turret != nullptr && !*turret->GetIsDead() && *turret->GetTeam() != *player->GetTeam())
			{
				return turret->GetPosition().DistanceTo(player->GetPosition()) < 1275;
			}

			return false;
		}
	}
}