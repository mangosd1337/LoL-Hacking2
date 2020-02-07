#include "stdafx.h"
#include "DrawEvent.h"

namespace EloBuddy
{
	namespace Native
	{
		Events::OnDrawEvent*
		DrawEventHandler::FunctionPointer = NULL;

		void
		DrawEventHandler::Add(Events::OnDrawEvent ptr)
		{
			this->FunctionPointer = ptr;
		}

		void
		DrawEventHandler::Trigger()
		{
			try
			{
				if (this->FunctionPointer != NULL)
					this->FunctionPointer();
			}
			catch (int e)
			{
				Console::Log(LOG_LEVEL::ERROR, "[OnDrawEvent] Exception: %d", e);
			}
		}
	}
}