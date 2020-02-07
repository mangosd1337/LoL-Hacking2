#include "stdafx.h"
#include "HudGameObjectChat.h"
#include "Detour.hpp"
#include "Obj_AI_Base.h"
#include "Hacks.h"
#include "EventHandler.h"

#ifndef MANAGED_BUILD
	#include <boost-151/format.hpp>
#endif

namespace EloBuddy
{
	namespace Native
	{
		MAKE_HOOK<convention_type::stdcall_t, void, const char*, byte> HudGameObjectChat_DisplayChat;

		bool HudGameObjectChat::ApplyHooks()
		{
#ifdef _CN_BUILD
			return true;
#endif
			HudGameObjectChat_DisplayChat.Apply(MAKE_RVA(Offsets::HudGameChatObject::DisplayChat), [] (const char* message, byte bitType) -> void
			{
				__asm pushad;
				auto process = true;

				if (bitType & 64)
				{
					process = EventHandler<42, OnChatClientSideMessage, char**>::GetInstance()->TriggerProcess(const_cast<char**>(&message));
				}
				else
				{
					AIHeroClient* sender = nullptr;

					__asm
					{
						mov sender, esi
					}

					//Console::PrintLn( "allmsg: %s - sender: %s", message , sender->GetName()->c_str());
					process = EventHandler<41, OnChatMessage, AIHeroClient*, char**>::GetInstance()->Trigger(sender, const_cast<char**>(&message));
				}

				if (Hacks::GetIsStreamingMode() && process && bitType != 64)
				{
					auto anonymizedMessage = AnonymizeMessage(std::string(message));
					if (anonymizedMessage.length() > 0)
					{
						message = anonymizedMessage.c_str();
					}
				}

				__asm popad;

				if (process)
					HudGameObjectChat_DisplayChat.CallOriginal(message, bitType);
			});

			return HudGameObjectChat_DisplayChat.IsApplied();
		}

		std::string HudGameObjectChat::AnonymizeMessage(std::string message)
		{
			if (message.find("</font> loaded") != std::string::npos)
				return std::string("");

			auto champNameBegin = message.find("(") + 1;
			auto champNameEnd = message.find(")");
			auto isAllChatMsg = message.find("\">[All]") != std::string::npos;

			if (champNameBegin == std::string::npos || champNameEnd == std::string::npos)
				return std::string("");

			auto champName = message.substr(champNameBegin, champNameEnd - champNameBegin);

			auto begin = message.find(">") + 1;
			auto end = message.find("</fon") - begin;

			auto newMsg = isAllChatMsg
				? (boost::format("[All] Player (%s): ") % champName.c_str()).str()
				: (boost::format("Player (%s): ") % champName.c_str()).str();

			message.replace(begin, end, newMsg.c_str());
			return message;
		}

		bool HudGameObjectChat::SetChatScale(float x, float y)
		{
			return
				reinterpret_cast<int(__stdcall*)(float, float)>
				MAKE_RVA(Offsets::HudGameChatObject::SetScale)
				(x, y);
		}
	}
}