#include "stdafx.h"

namespace EloBuddy
{
	public ref class OnLevelUpSpellEventArgs : public System::EventArgs
	{
	private:
		int points;
		int spellid;
		int spellLevel;
	public:
		delegate void OnLevelUpSpell(OnLevelUpSpellEventArgs^ args);

		OnLevelUpSpellEventArgs(int points, int spellid, int spellLevel)
		{
			this->points = points;
			this->spellid = spellid;
			this->spellLevel = spellLevel;
		}

		property int RemainingPoints
		{
			int get()
			{
				return this->points;
			}
		}

		property int SpellId
		{
			int get()
			{
				return this->spellid;
			}
		}

		property int SpellLevel
		{

			int get()
			{
				return this->spellLevel;
			}
		}
	};
}