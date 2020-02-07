#pragma once

#include "Utils.h"
#include "Events.hpp"

namespace EloBuddy
{
	namespace Native
	{
		class DLLEXPORT WndProcEventHandler
		{
		private:
			static Events::WndProcEvent* FunctionPointer;
		public:
			void Add(Events::WndProcEvent ptr);
			void Trigger(HWND, uint, WPARAM, LPARAM);
		};
	}
}