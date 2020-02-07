#pragma once
#include "Utils.h"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT HudGameObjectChat
		{
		public:
			static bool ApplyHooks();
			static std::string AnonymizeMessage( std::string message );

			static bool SetChatScale( float x, float y );
		};
	}
}