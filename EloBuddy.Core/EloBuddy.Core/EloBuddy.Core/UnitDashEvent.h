#pragma once

#include "Utils.h"
#include "Events.hpp"

namespace EloBuddy
{
	namespace Native
	{
		class DLLEXPORT UnitDashEventHandler
		{
		private:
			static Events::UnitDashEvent* FunctionPointer;
		public:
			void Add(Events::UnitDashEvent ptr);
			void Trigger(int, int, GameObject*);
		};
	}
}