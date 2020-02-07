#include "stdafx.h"

namespace EloBuddy
{
	ref class GameObject;

	public ref class OnUnitDashEventArgs : public System::EventArgs
	{
	private:
		unsigned int networkId;
		int speed;
		GameObject^ unit;
	public:
		delegate void OnUnitDash(OnUnitDashEventArgs^ args);

		OnUnitDashEventArgs( unsigned int networkId, int speed, GameObject^ unit )
		{
			this->networkId = networkId;
			this->speed = speed;
			this->unit = unit;
		}

		property unsigned int NetworkId
		{
			unsigned int get()
			{
				return this->networkId;
			}
		}

		property int Speed
		{
			int get()
			{
				return this->speed;
			}
		}

		property GameObject^ Unit
		{
			GameObject^ get()
			{
				return this->unit;
			}
		}
	};
}