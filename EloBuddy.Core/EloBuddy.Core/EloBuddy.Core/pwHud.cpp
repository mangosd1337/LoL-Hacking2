#include "stdafx.h"
#include "pwHud.h"
#include "Offsets.h"
#include "Detour.hpp"
#include "GameObject.h"
#include "EventHandler.h"
#include "r3dRenderer.h"

namespace EloBuddy
{
	namespace Native
	{
		MAKE_HOOK<convention_type::stdcall_t, int, int*> pwHud_SetUISelectedObjID;
		MAKE_HOOK<convention_type::fastcall_t, void, int, int, int, int, byte, int, byte> pwHudProcessRightClick;
		MAKE_HOOK<convention_type::stdcall_t, bool, int, int, int, int, int, DWORD*, char> pwHudShowClick;

		pwHud* pwHud::GetInstance()
		{
			return *reinterpret_cast<pwHud**>(MAKE_RVA(Offsets::pwHud::pwHud_Instance));
		}

		bool pwHud::ApplyHooks()
		{
			return true;
		}

		void pwHud::ShowClick(Vector3f* position, byte clickType) const
		{

		}

		bool pwHud::IsWindowFocused()
		{
			return *reinterpret_cast<BYTE*>(MAKE_RVA(Offsets::Game::IsWindowFocused));
		}

		bool pwHud::IsDrawing(pwHudDrawingType type)
		{
			return false;
		}

		void pwHud::DisableUI()
		{
			//NOPHandler<static_cast<int>(Offsets::pwHud::NOP_pwHud_DisableDrawing)>::GetInstance()->NOP(5);
		}

		void pwHud::EnableUI()
		{
			//NOPHandler<static_cast<int>(Offsets::pwHud::NOP_pwHud_DisableDrawing)>::GetInstance()->Restore();
		}

		void pwHud::DisableHPBar()
		{
			//NOPHandler<static_cast<int>(Offsets::pwHud::NOP_pwHud_DisableHPBarDrawing)>::GetInstance()->NOP(5);
		}

		void pwHud::EnableHPBar()
		{
			//NOPHandler<static_cast<int>(Offsets::pwHud::NOP_pwHud_DisableHPBarDrawing)>::GetInstance()->Restore();
		}

		void pwHud::DisableMenuUI()
		{
			//NOPHandler<static_cast<int>(Offsets::pwHud::NOP_MenuGUI_DisableDrawing)>::GetInstance()->NOP(5);
		}

		void pwHud::EnableMenuUI()
		{
			//NOPHandler<static_cast<int>(Offsets::pwHud::NOP_MenuGUI_DisableDrawing)>::GetInstance()->Restore();
		}

		void pwHud::DisableMinimap()
		{
			//NOPHandler<static_cast<int>(Offsets::pwHud::NOP_MenuGUI_DisableMiniMap)>::GetInstance()->NOP(5);
		}

		void pwHud::EnableMinimap()
		{
			//NOPHandler<static_cast<int>(Offsets::pwHud::NOP_MenuGUI_DisableMiniMap)>::GetInstance()->Restore();
		}

		void pwHud::DisablePing()
		{
			//*reinterpret_cast<BYTE*>(*reinterpret_cast<DWORD*>(MAKE_RVA(Offsets::HudManager::HudManagerInst)) + static_cast<int>(Offsets::HudManagerStruct::ShowPing)) = 0;
		}

		void pwHud::EnablePing()
		{
			//*reinterpret_cast<BYTE*>(*reinterpret_cast<DWORD*>(MAKE_RVA(Offsets::HudManager::HudManagerInst)) + static_cast<int>(Offsets::HudManagerStruct::ShowPing)) = 1;
		}

		HudManager* pwHud::GetHudManager() const
		{
			__asm
			{
				mov esi, this
				mov eax, [esi + 0x8]
			}
		}

		DWORD* pwHud::GetClickManager() const
		{
			__asm
			{
				mov esi, this
				mov eax, [esi + 0x14]
			}
		}
	}
}