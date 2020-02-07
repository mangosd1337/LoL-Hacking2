#include "stdafx.h"
#include "EndSceneEvent.h"

namespace EloBuddy
{
	namespace Native
	{
		Events::OnEndSceneEvent*
		EndSceneEventHandler::FunctionPointer = NULL;

		void
		EndSceneEventHandler::Add(Events::OnEndSceneEvent ptr)
		{
			this->FunctionPointer = ptr;
		}

		void
		EndSceneEventHandler::Trigger()
		{
			try
			{
				if (this->FunctionPointer != NULL)
					this->FunctionPointer();
			}
			catch (int e)
			{
				Console::Log(LOG_LEVEL::ERROR, "[OnEndSceneEvent] Exception: %d", e);
			}
		}
	}
}