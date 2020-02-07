#include "stdafx.h"

using namespace System;

namespace EloBuddy
{
	public ref class AudioManagerPlaySoundEventArgs : public System::EventArgs
	{
	private:
		String^ m_soundFile;
		bool m_process;
	public:
		delegate void AudioManagerPlaySoundEvent( AudioManagerPlaySoundEventArgs^ args );

		AudioManagerPlaySoundEventArgs( String^ soundFile )
		{
			this->m_soundFile = soundFile;
			this->m_process = true;
		}

		property String^ SoundFile
		{
			String^ get()
			{
				return this->m_soundFile;
			}
		}

		property bool Process
		{
			bool get()
			{
				return m_process;
			}
			void set(bool value)
			{
				m_process = value;
			}
		}
	};
}