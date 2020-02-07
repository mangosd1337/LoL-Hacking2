#include "stdafx.h"
#include "WndProcEvent.h"

namespace EloBuddy
{
	namespace Native
	{
		Events::WndProcEvent*
		WndProcEventHandler::FunctionPointer = NULL;

		void
		WndProcEventHandler::Add(Events::WndProcEvent ptr)
		{
			this->FunctionPointer = ptr;
		}

		void
		WndProcEventHandler::Trigger(HWND hwnd, uint msg, WPARAM wParam, LPARAM lParam)
		{
			try
			{
				if (this->FunctionPointer != NULL)
					this->FunctionPointer(hwnd, msg, wParam, lParam);
			}
			catch (int e)
			{
				Console::Log(LOG_LEVEL::ERROR, "[WndProcEvent] Exception: %d", e);
			}
		}
	}
}