#pragma once

#include "Utils.h"
#include "Events.hpp"

namespace EloBuddy
{
	namespace Native
	{
		class DLLEXPORT PresentEventHandler
		{
		private:
			static Events::OnPresentEvent* FunctionPointer;
		public:
			void Add(Events::OnPresentEvent ptr);
			void Trigger();
		};
	}
}