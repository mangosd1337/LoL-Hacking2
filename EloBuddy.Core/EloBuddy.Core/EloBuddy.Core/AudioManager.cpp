#include "stdafx.h"
#include "AudioManager.h"
#include "Detour.hpp"
#include "Console.h"
#include "EventHandler.h"

namespace EloBuddy
{
	namespace Native
	{
		MAKE_HOOK<convention_type::stdcall_t, int, int, int, std::string const &, int, int, float, int> AudioManager_PlaySound;

		bool AudioManager::ApplyHooks()
		{
			AudioManager_PlaySound.Apply(MAKE_RVA(Offsets::AudioManager::PlaySound), [] (int AudioManager, int unkn1, std::string const & soundFile, int unkn2, int unkn3, float volume, int IEventFinishedCalblack) -> int
			{
				__asm pushad;
					EventHandler<50, OnAudioManagerPlaySound, std::string>::GetInstance()->Trigger(soundFile);
				__asm popad;

				return AudioManager_PlaySound.CallOriginal(AudioManager, unkn1, soundFile, unkn2, unkn3, volume, IEventFinishedCalblack);
			});

			return AudioManager_PlaySound.IsApplied();
		}
	}
}