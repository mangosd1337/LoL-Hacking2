#include "Stdafx.h"
#include "TacticalMap.hpp"

#include "../../EloBuddy.Core/EloBuddy.Core/ObjectManager.h"
#include "../../EloBuddy.Core/EloBuddy.Core/MenuGUI.h"
#include "../../EloBuddy.Core/EloBuddy.Core/EventHandler.h"

#include "AudioManager.h"

namespace EloBuddy
{
	static AudioManager::AudioManager()
	{
		ATTACH_DOMAIN();

		ATTACH_EVENT
		(
			AudioManagerPlaySound,
			50, Native::OnAudioManagerPlaySound, std::string
		);
	}

	void AudioManager::DomainUnloadEventHandler( System::Object^, System::EventArgs^ )
	{
		DETACH_EVENT
		(
			AudioManagerPlaySound,
			50, Native::OnAudioManagerPlaySound, std::string
		);
	}

	bool AudioManager::OnAudioManagerPlaySoundNative( std::string soundFile )
	{
		bool process = true;

		START_TRACE
			auto args = gcnew AudioManagerPlaySoundEventArgs( gcnew String(soundFile.c_str()) );

			for each (auto eventHandle in AudioManagerPlaySoundHandlers->ToArray())
			{
				START_TRACE
					eventHandle( args );

					if (!args->Process)
						process = false;
				END_TRACE
			}
		END_TRACE

		return process;
	}
}