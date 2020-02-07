#include "stdafx.h"
#include "pwConsole.h"
#include "ObjectManager.h"
#include "MenuGUI.h"
#include "EventHandler.h"
#include "Hacks.h"

#ifndef MANAGED_BUILD
	#include <boost/format.hpp>
#endif

namespace EloBuddy
{
	namespace Native
	{
		MAKE_HOOK<convention_type::stdcall_t, int, int, const char*> pwConsole_ProcessMessage;
		MAKE_HOOK<convention_type::stdcall_t, void, std::wstring*> pwConsole_ShowClientSideMessage;

		pwConsole* pwConsole::GetInstance()
		{
			static auto instance = new pwConsole();
			return instance;
		}

		bool pwConsole::ApplyHooks()
		{
			pwConsole_ProcessMessage.Apply(MAKE_RVA(Offsets::pwConsole::OnInput), [] (int a1, const char* msg) -> int
			{
				if (EventHandler<40, OnChatInput, char**>::GetInstance()->TriggerProcess(const_cast<char**>(&msg)))
				{
					return pwConsole_ProcessMessage.CallOriginal(a1, msg);
				}
				else {
					pwConsole::GetInstance()->Close();
					return 0;
				}
			});

			return pwConsole_ProcessMessage.IsApplied();
		}

		void pwConsole::ShowClientSideMessage(const char* msg)
		{
			if (Hacks::GetPwConsole())
			{
				reinterpret_cast<void(__thiscall*)(void*, const char*, int)>
					(MAKE_RVA(Offsets::pwConsole::ShowClientSideMessage))
					(reinterpret_cast<void*>(MAKE_RVA(Offsets::MenuGUI::MenuGUI_Instance)), msg, 0);
			}
			else
			{
				Console::PrintLn(
					(boost::format("GameMessage: %s") % msg).str().c_str()
					);
			}
		}

		void pwConsole::ProcessCommand()
		{
			reinterpret_cast<void(__thiscall*)(void*)>
				(MAKE_RVA(Offsets::pwConsole::ProcessCommand))
				(reinterpret_cast<void*>(MAKE_RVA(Offsets::MenuGUI::MenuGUI_Instance)));
		}

		void pwConsole::Show()
		{
			*MenuGUI::GetInstance()->GetIsActive() = false;
			*MenuGUI::GetInstance()->GetIsChatOpen() = false;

			reinterpret_cast<void(__thiscall*)(void*)>
				(MAKE_RVA(Offsets::pwConsole::pwOpen))
				(*reinterpret_cast<int**>(MAKE_RVA(Offsets::pwConsole::pwOpen)));
		}

		void pwConsole::Close()
		{
			*MenuGUI::GetInstance()->GetIsActive() = false;
			*MenuGUI::GetInstance()->GetIsChatOpen() = false;

			reinterpret_cast<void(__thiscall*)(void*)>
				(MAKE_RVA(Offsets::pwConsole::pwClose))
				(*reinterpret_cast<int**>(MAKE_RVA(Offsets::HudManager::HudManagerInst)));
		}

		void pwConsole::ExportFunctions()
		{
			/*module(LuaEz::GetMainState(), "Chat")
				[
					def<void(const char*)>("Print", [] (const char* msg)
					{
						//pwConsole::GetInstance()->ShowClientSideMessage( msg );
					}),

						def<void(const char*)>("Say", [] (const char* msg)
					{
						//pwConsole::GetInstance()->ProcessCommand( msg );
					}),

						def<void()>("Show", []
					{
						//pwConsole::GetInstance()->Show();
					}),

						def<void()>("Close", []
					{
						//pwConsole::GetInstance()->Close();
					}),

						//Properties
						def<bool()>("IsOpen", []
					{
						return *MenuGUI::GetInstance()->GetIsChatOpen();
					}),

						def<bool()>("IsClosed", []
					{
						return !*MenuGUI::GetInstance()->GetIsChatOpen();
					})
				];

			DPRINT("pwConsole::ExportFunctions() exported");*/
		}
	}
}