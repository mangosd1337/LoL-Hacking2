#include "stdafx.h"

namespace EloBuddy
{
	ref class AIHeroClient;

	public ref class OnHeroDeathEventArgs : public System::EventArgs
	{
	private:
		float m_deathDuration;
		Obj_AI_Base^ m_sender;
	public:
		delegate void OnHeroDeath( OnHeroDeathEventArgs^ args );

		OnHeroDeathEventArgs( Obj_AI_Base^ sender, float deathDuration )
		{
			this->m_sender = sender;
			this->m_deathDuration = deathDuration;
		}

		property Obj_AI_Base^ Sender
		{
			Obj_AI_Base^ get()
			{
				return this->m_sender;
			}
		}

		property float DeathDuration
		{
			float get()
			{
				return this->m_deathDuration;
			}
		}
	};
}