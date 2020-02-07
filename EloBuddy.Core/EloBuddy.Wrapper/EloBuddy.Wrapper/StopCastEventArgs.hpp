#include "stdafx.h"

using namespace System;
using namespace SharpDX;

namespace EloBuddy
{
	ref class Obj_AI_Base;

	public ref class SpellbookStopCastEventArgs : public System::EventArgs
	{
	private:
		bool m_stopAnimation;
		bool m_executeCastFrame;
		bool m_forceStop;
		bool m_destroyMissile;
		uint m_missileNetworkId;
		int m_counter;
	public:
		delegate void SpellbookStopCastEvent( Obj_AI_Base^ sender, SpellbookStopCastEventArgs^ args );

		SpellbookStopCastEventArgs( bool stopAnimation, bool executeCastFrame, bool forceStop, bool destroyMissile, uint missileNetworkId, int counter )
		{
			this->m_stopAnimation = stopAnimation;
			this->m_executeCastFrame = executeCastFrame;
			this->m_forceStop = forceStop;
			this->m_destroyMissile = destroyMissile;
			this->m_missileNetworkId = missileNetworkId;
			this->m_counter = counter;
		}

		property bool StopAnimation
		{
			bool get()
			{
				return this->m_stopAnimation;
			}
		}

		property bool ExecuteCastFrame
		{
			bool get()
			{
				return this->m_executeCastFrame;
			}
		}

		property bool ForceStop
		{
			bool get()
			{
				return this->m_forceStop;
			}
		}

		property bool DestroyMissile
		{
			bool get()
			{
				return this->m_destroyMissile;
			}
		}

		property uint MissileNetworkId
		{
			uint get()
			{
				return this->m_missileNetworkId;
			}
		}

		property int Counter
		{
			int get()
			{
				return this->m_counter;
			}
		}
	};
}