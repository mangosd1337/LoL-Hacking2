#include "stdafx.h"

namespace EloBuddy
{
	public ref class OnLevelUpEventArgs : public System::EventArgs
	{
	private:
		int level;
		int pointsLeft;
	public:
		delegate void OnLevelUp(OnLevelUpEventArgs^ args);

		OnLevelUpEventArgs(int level, int pointsLeft)
		{
			this->level = level;
			this->pointsLeft = pointsLeft;
		}

		property int Level
		{
			int get()
			{
				return this->level;
			}
		}

		property int PointsLeft
		{
			int get()
			{
				return this->pointsLeft;
			}
		}
	};
}