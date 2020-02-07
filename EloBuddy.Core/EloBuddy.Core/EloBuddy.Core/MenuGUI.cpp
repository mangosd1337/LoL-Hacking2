#include "stdafx.h"

#include "GameObject.h"
#include "Utils.h"
#include "MenuGUI.h"
#include "EventHandler.h"
#include "ObjectManager.h"

namespace EloBuddy
{
	namespace Native
	{
		MAKE_HOOK<convention_type::stdcall_t, void, Vector3f*, int, int, uint, bool, bool, bool, bool> OnPingMiniMap;

		bool MenuGUI::ApplyHooks()
		{
			OnPingMiniMap.Apply(MAKE_RVA(Offsets::MenuGUI::PingMiniMap), [] (Vector3f* dstVec, int src, int target, uint pingType, bool playSound, bool unkn1, bool unkn2, bool unkn3) -> void
			{
#ifdef _DEBUG
				Console::PrintLn("OnPingMinimap - Dst: %g %g - Src: %08x - Target: %08x - PingType: %d - PlaySound: %d - Unkn1: %d - Unkn2: %d - Unkn3: %d",
					dstVec->GetX(), dstVec->GetY(),
					src, target, pingType, playSound, unkn1, unkn2, unkn3);
#endif

				auto srcIndex = src & 0xFFFF;
				auto dstIndex = target & 0xFFFF;

				if (EventHandler<36, OnTacticalMapPing, Vector3f*, GameObject*, GameObject*, uint>::GetInstance()->TriggerProcess(dstVec, ObjectManager::GetUnitByIndex(srcIndex), ObjectManager::GetUnitByIndex(dstIndex), pingType))
					OnPingMiniMap.CallOriginal(dstVec, src, target, pingType, playSound, unkn1, unkn2, unkn3);
			});

			return OnPingMiniMap.IsApplied();
		}

		MenuGUI* MenuGUI::GetInstance()
		{
			return reinterpret_cast<MenuGUI*>(MAKE_RVA(Offsets::MenuGUI::MenuGUI_Instance));
		}

		void MenuGUI::PingMiniMap(Vector3f* dstVec, int src, int target, int pingType, bool playSound)
		{
#ifdef _DEBUG_BUILD
			Console::PrintLn("OnPingMinimap - Dst: %g %g - Src: %08x - Target: %08x - PingType: %d - PlaySound: %d",
				dstVec->GetX(), dstVec->GetY(),
				src, target, pingType, playSound);
#endif

			OnPingMiniMap.CallOriginal(dstVec, src, target, pingType, playSound, true, false, true);
		}

		void MenuGUI::CallCurrentPing(Vector3f* dstVec, int targetNetId, int pingType)
		{
			reinterpret_cast<void(__stdcall*)(Vector3f*, int, int)>
				MAKE_RVA(Offsets::MenuGUI::CallCurrentPing)
				(dstVec, targetNetId, pingType);
		}

		bool MenuGUI::DoMasteryBadge()
		{
			return reinterpret_cast<bool(__stdcall*)()>
				MAKE_RVA(Offsets::MenuGUI::DoMasteryBadge)
				();
		}
	}
}
