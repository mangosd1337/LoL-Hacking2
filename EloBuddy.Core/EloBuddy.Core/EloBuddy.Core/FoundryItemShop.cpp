#include "stdafx.h"
#include "FoundryItemShop.h"
#include "Detour.hpp"
#include "pwHud.h"
#include "EventHandler.h"

namespace EloBuddy
{
	namespace Native
	{
		MAKE_HOOK<convention_type::stdcall_t, int, void*> FoundryToggleShop;
		MAKE_HOOK<convention_type::stdcall_t, void, void*> FoundryOnUndo;

		bool FoundryItemShop::ApplyHooks()
		{
			FoundryToggleShop.Apply(MAKE_RVA(Offsets::FoundryItemShop::ToggleShop), [] (void* hudShop) -> int
			{
				__asm pushad;
					if (!*GetInstance()->GetIsOpen())
						EventHandler<52, OnShopOpen>::GetInstance()->Trigger();
					else
						EventHandler<53, OnCloseShop>::GetInstance()->Trigger();
				__asm popad;

				return FoundryToggleShop.CallOriginal(hudShop);
			});

			FoundryOnUndo.Apply(MAKE_RVA(Offsets::FoundryItemShop::OnUndo), [] (void* shopHud) -> void
			{
				EventHandler<54, OnUndoPurchase>::GetInstance()->Trigger();
				FoundryOnUndo.CallOriginal(shopHud);
			});

			return FoundryToggleShop.IsApplied()
				&& FoundryOnUndo.IsApplied();
		}

		FoundryItemShop* FoundryItemShop::GetInstance()
		{
			return *reinterpret_cast<FoundryItemShop**>(*reinterpret_cast<DWORD**>(MAKE_RVA(Offsets::HudManager::HudManagerInst)) + 0x2C); // 0xC4 / 0x4
		}

		bool FoundryItemShop::OpenShop()
		{
			auto hudManagerInst = *reinterpret_cast<DWORD**>(MAKE_RVA(Offsets::HudManager::HudManagerInst));

			__asm
			{
				mov ecx, hudManagerInst
			}

			return FoundryToggleShop.CallOriginal(hudManagerInst);
		}

		bool FoundryItemShop::CloseShop()
		{
			auto hudManagerInst = *reinterpret_cast<int**>(MAKE_RVA(Offsets::HudManager::HudManagerInst));

			__asm
			{
				mov ecx, hudManagerInst
			}

			return FoundryToggleShop.CallOriginal(hudManagerInst);
		}

		void FoundryItemShop::UndoPurchase()
		{
			FoundryOnUndo.CallOriginal(nullptr);
		}
	}
}