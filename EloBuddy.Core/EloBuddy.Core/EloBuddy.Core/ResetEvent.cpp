#include "stdafx.h"
#include "ResetEvent.h"

namespace EloBuddy
{
	namespace Native
	{
		Events::OnResetEvent*
		ResetEventHandler::FunctionPointer = NULL;

		void
		ResetEventHandler::Add(Events::OnResetEvent ptr)
		{
			this->FunctionPointer = ptr;
		}

		void
		ResetEventHandler::Trigger()
		{
			try
			{
				if (this->FunctionPointer != NULL)
					this->FunctionPointer();
			}
			catch (int e)
			{
				Console::Log(LOG_LEVEL::ERROR, "[OnResetEvent] Exception: %d", e);
			}
		}
	}
}