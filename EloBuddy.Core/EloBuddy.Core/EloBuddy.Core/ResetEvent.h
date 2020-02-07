#pragma once

#include "Utils.h"
#include "Events.hpp"

namespace EloBuddy
{
	namespace Native
	{
		class DLLEXPORT ResetEventHandler
		{
		private:
			static Events::OnResetEvent* FunctionPointer;
		public:
			void Add(Events::OnResetEvent ptr);
			void Trigger();
		};
	}
}