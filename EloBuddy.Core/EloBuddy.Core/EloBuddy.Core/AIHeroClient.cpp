#include "stdafx.h"
#include "AIHeroClient.h"
#include "EventHandler.h"
#include "ObjectManager.h"
#include "Lua.h"

namespace EloBuddy
{
	namespace Native
	{
		MAKE_HOOK<convention_type::stdcall_t, bool, short> OnEmote;

		bool AIHeroClient::ApplyHooks()
		{
			OnEmote.Apply( MAKE_RVA( Offsets::MenuGUI::DoEmote ), [] ( short emoteId ) -> bool
			{
				if (EventHandler<72, OnPlayerDoEmote, AIHeroClient*, short>::GetInstance()->TriggerProcess( ObjectManager::GetPlayer(), emoteId ))
				{
					return OnEmote.CallOriginal( emoteId );
				}
				
				return false;
			} );

			return OnEmote.IsApplied();
		}

		bool AIHeroClient::DoEmote( ushort emoteId ) const
		{
			return OnEmote.CallDetour( emoteId );
		}

		bool AIHeroClient::Virtual_CanShop()
		{
			auto foundryShopCheckCanShop = reinterpret_cast<bool( __stdcall* )()>MAKE_RVA( Offsets::FoundryItemShop::ActionCheckVT1 )();
			auto foundryItemCheckCanShop2 = reinterpret_cast<bool( __thiscall * )(AIHeroClient*)>MAKE_RVA( Offsets::FoundryItemShop::ActionCheckVT2 )(this);

			return foundryShopCheckCanShop && foundryItemCheckCanShop2;
		}

		void AIHeroClient::ExportFunctions()
		{
			//Console::PrintLn("lol");
			//Console::PrintLn("nerd: %08x", Lua::GetInstance()->L);
			//Console::PrintLn("mainstate: %08x", Lua::GetMainState());



			//module( Lua::GetMainState() )
			//	[
			//		// ReSharper disable once CppPossiblyUnintendedObjectSlicing
			//		class_<AIHeroClient>("AIHeroClient")
			//			.property("Health", &GetHealth)
			//	];

			//Console::PrintLn("[Lua]: AIHeroClient exported");
		}
	}
}
