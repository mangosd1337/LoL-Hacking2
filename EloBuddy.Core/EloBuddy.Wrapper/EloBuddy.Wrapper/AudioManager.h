#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/AudioManager.h"

#include "Macros.hpp"
#include "AudioManagerEventArgs.h"

using namespace System;

namespace EloBuddy
{
	MAKE_EVENT_GLOBAL( AudioManagerPlaySound, AudioManagerPlaySoundEventArgs^ args );

	public ref class AudioManager
	{
	internal:
		MAKE_EVENT_INTERNAL_PROCESS( AudioManagerPlaySound, (std::string soundFile) );
	public:
		MAKE_EVENT_PUBLIC( OnPlaySound, AudioManagerPlaySound );

		static AudioManager();
		static void DomainUnloadEventHandler( System::Object^, System::EventArgs^ );
	};
}