#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/MenuGUI.h"
#include "../../EloBuddy.Core/EloBuddy.Core/pwConsole.h"
#include "Macros.hpp"

namespace EloBuddy
{
	public ref class MenuGUI
	{
	public:
		static property bool IsChatOpen
		{
			bool get()
			{
				auto menuGUI = Native::MenuGUI::GetInstance();
				if (menuGUI)
				{
					return *menuGUI->GetIsChatOpen();
				}
				return false;
			}

			void set(bool open)
			{
				if (open)
					Native::pwConsole::GetInstance()->Show();
				else
					Native::pwConsole::GetInstance()->Close();
			}
		}
	};
}