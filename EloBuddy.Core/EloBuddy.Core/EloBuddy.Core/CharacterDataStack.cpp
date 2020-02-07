#include "stdafx.h"
#include "CharacterDataStack.h"
#include "ObjectManager.h"
#include "AIHeroClient.h"

#ifndef MANAGED_BUILD
#include <boost/format.hpp>
#endif

namespace EloBuddy
{
	namespace Native
	{
		MAKE_HOOK<convention_type::stdcall_t, void, char*, int, int, int, int, int > OnChangeModel;

		bool CharacterDataStack::ApplyHooks()
		{
			/*OnChangeModel.Apply( MAKE_RVA( Offsets::Obj_AIBase::SetSkin ), [] ( char* model, int skinId, int unkn1, int unkn2, int unkn3, int unkn4 ) -> void
			{
			__asm pushad;
			//#ifdef _DEBUG_BUILD
			Console::PrintLn("THIS: %08x Model: %s - SkinId: %d - Unkn1: %08x - %08x - %08x - %08x", model, skinId, unkn1, unkn2, unkn3, unkn4);
			//#endif
			__asm popad;

			OnChangeModel.CallOriginal( model, skinId, unkn1, unkn2, unkn3, unkn4 );
			} );

			return OnChangeModel.IsApplied();*/

			return true;
		}

		void CharacterDataStack::SetBaseSkinId(int skinId)
		{
			if (this != nullptr && this->GetActiveModel() != nullptr)
			{
				reinterpret_cast<void(__thiscall*)(CharacterDataStack*, const char*, int, int, int, int, int)>
					MAKE_RVA(Offsets::GameObjectFunctions::SetSkin)
					(this, this->GetActiveModel()->c_str(), skinId, 0, 0, 0, 0);
			}
		}

		bool CharacterDataStack::SetModel(char* model)
		{
			if (RiotAsset::LoadAsset(model))
			{
				if (this != nullptr && this->GetActiveSkinId() != nullptr)
				{
					reinterpret_cast<void(__thiscall*)(CharacterDataStack*, char*, int, int, int, int, int)>
						MAKE_RVA(Offsets::GameObjectFunctions::SetSkin)
						(this, model, *this->GetActiveSkinId(), 0, 0, 0, 0);
				}
			}
			else {
				pwConsole::GetInstance()->ShowClientSideMessage((boost::format("<font color='#ff0000'>ERROR:</font><font color='#ffff00'> Failed to load model %s</font>") % model).str().c_str());
			}

			return RiotAsset::LoadAsset(model);
		}
	}
}