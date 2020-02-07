#include "stdafx.h"
#include "PresentEvent.h"

namespace EloBuddy
{
	namespace Native
	{
		Events::OnPresentEvent*
		PresentEventHandler::FunctionPointer = NULL;

		void
		PresentEventHandler::Add(Events::OnPresentEvent ptr)
		{
			this->FunctionPointer = ptr;
		}

		void
		PresentEventHandler::Trigger()
		{
			try
			{
				if (this->FunctionPointer != NULL)
					this->FunctionPointer();
			}
			catch (int e)
			{
				Console::Log(LOG_LEVEL::ERROR, "[OnPresentEvent] Exception: %d", e);
			}
		}
	}
}