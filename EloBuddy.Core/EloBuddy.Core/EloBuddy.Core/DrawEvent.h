#pragma once

#include "Utils.h"
#include "Events.hpp"

namespace EloBuddy
{
	namespace Native
	{
		class DLLEXPORT DrawEventHandler
		{
		private:
			static Events::OnDrawEvent* FunctionPointer;
		public:
			void Add(Events::OnDrawEvent ptr);
			void Trigger();
		};
	}
}