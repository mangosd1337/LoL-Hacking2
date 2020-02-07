#include "stdafx.h"
#include "BuffHost.h"
#include "Detour.hpp"
#include "BuffInstance.h"
#include "AIHeroClient.h"
#include "EventHandler.h"

#define BUFF_ADD	1
#define BUFF_REMOVE 0

namespace EloBuddy
{
	namespace Native
	{
		struct BuffHostInst
		{
			void* unkn;
			void* unkn1;
			void* unkn2;
			BuffInstance* buffInst;
		};

		MAKE_HOOK<convention_type::stdcall_t, int, BuffHostInst*, byte> OnBuffAddRemoveEvent;

		bool BuffHost::ApplyHooks()
		{
			//TODO: Seperate Add/Remove to avoid stack failures
			//TODO: Add OnUpdate event

			OnBuffAddRemoveEvent.Apply(MAKE_RVA(Offsets::BuffHost::BuffAddRemove), [] (BuffHostInst* buffHostInst, byte flag) -> int
			{
				__asm pushad;
				Obj_AI_Base* sender = nullptr;

				__asm
				{
					mov sender, ecx
				}

				sender = static_cast<Obj_AI_Base*>(sender - static_cast<int>(Offsets::Spellbook::SpellbookInst)); //???? 5.18HF

				if (buffHostInst != nullptr
					&& buffHostInst->buffInst != nullptr
					&& buffHostInst->buffInst->GetScriptBaseBuff()
					&& sender != nullptr)
				{
					if (flag == BUFF_ADD || flag == BUFF_REMOVE)
					{
#ifdef _DEBUG_BUILD
						/*Console::PrintLn( "BuffHost: %08x - Sender: %08x", buffHostInst->buffInst, sender );

						Console::PrintLn( "[%s - %d] -> Buff: %s - Start: %g - End: %g -> IsActive: %d - IsPositive: %d - IsValid: %d - %s",
						sender->GetName().c_str(),
						flag,
						buffHostInst->buffInst->GetScriptBaseBuff()->GetName(), *buffHostInst->buffInst->GetStartTime(), *buffHostInst->buffInst->GetEndTime(),
						buffHostInst->buffInst->IsActive(), buffHostInst->buffInst->IsPositive(), buffHostInst->buffInst->IsValid(),
						buffHostInst->buffInst->GetScriptBaseBuff()->GetVirtual()->GetDisplayName()*/
						);
#endif

			if (flag == BUFF_ADD)
			{
				EventHandler<37, OnObjAIBaseAddBuff, Obj_AI_Base*, BuffInstance*>::GetInstance()->Trigger(sender, buffHostInst->buffInst);
			}

			if (flag == BUFF_REMOVE)
			{
				EventHandler<38, OnObjAIBaseRemoveBuff, Obj_AI_Base*, BuffInstance*>::GetInstance()->Trigger(sender, buffHostInst->buffInst);
			}
					}
				}

				__asm popad;

				return OnBuffAddRemoveEvent.CallOriginal(buffHostInst, flag);
			} );

			return OnBuffAddRemoveEvent.IsApplied();
		}
	}
}