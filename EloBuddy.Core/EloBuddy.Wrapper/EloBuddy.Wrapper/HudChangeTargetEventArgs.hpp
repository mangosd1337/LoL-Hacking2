#include "stdafx.h"

namespace EloBuddy
{
	ref class GameObject;

	public ref class HudChangeTargetEventArgs : public System::EventArgs
	{
	private:
		GameObject^ m_target;
		bool m_clear;
	public:
		delegate void HudChangeTargetEvent( HudChangeTargetEventArgs^ args );

		HudChangeTargetEventArgs( GameObject^ target, bool clear )
		{
			this->m_target = target;
			this->m_clear = clear;
		}

		property GameObject^ Target
		{
			GameObject^ get()
			{
				return this->m_target;
			}
		}

		property bool Reset
		{
			bool get()
			{
				return this->m_clear;
			}
		}
	};
}