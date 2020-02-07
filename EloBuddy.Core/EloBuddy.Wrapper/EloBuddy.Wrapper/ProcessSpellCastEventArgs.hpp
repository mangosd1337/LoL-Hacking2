#include "stdafx.h"

#include "GameObject.hpp"
#include "ObjectManager.hpp"
#include "Game.hpp"

using namespace System;
using namespace SharpDX;

namespace EloBuddy
{
	ref class Obj_AI_Base;
	ref class SpellData;

	public ref class GameObjectProcessSpellCastEventArgs : public System::EventArgs
	{
	private:
		SpellData^ m_sdata;
		int m_level;
		Vector3 m_start;
		Vector3 m_end;
		uint m_targetLocalId;
		int m_castedSpellCount;
		float m_time;
		SpellSlot m_slot;
		bool m_isToggle;
		bool m_process;
	public:
		delegate void GameObjectProcessSpellCastEvent( Obj_AI_Base^ sender, GameObjectProcessSpellCastEventArgs^ args );

		GameObjectProcessSpellCastEventArgs( SpellData^ sdata, int level, Vector3 start, Vector3 end, uint targetLocalId, int counter, SpellSlot slot, bool isToggle)
		{
			this->m_sdata = sdata;
			this->m_level = level;
			this->m_start = start;
			this->m_end = end;
			this->m_targetLocalId = targetLocalId;
			this->m_castedSpellCount = counter;
			this->m_time = Game::Time;
			this->m_isToggle = isToggle;
			this->m_process = true;
			this->m_slot = slot;
		}

		property SpellSlot Slot
		{
			SpellSlot get()
			{
				return this->m_slot;
			}
		}

		property float Time
		{
			float get()
			{
				return this->m_time;
			}
		}

		property SpellData^ SData
		{
			SpellData^ get( )
			{
				return this->m_sdata;
			}
		}

		property int Level
		{
			int get( )
			{
				return this->m_level;
			}
		}

		property Vector3 Start
		{
			Vector3 get()
			{
				return this->m_start;
			}
		}

		property Vector3 End
		{
			Vector3 get( )
			{
				return this->m_end;
			}
		}

		property int CastedSpellCount
		{
			int get()
			{
				return this->m_castedSpellCount;
			}
		}

		property GameObject^ Target
		{
			GameObject^ get()
			{
				if (this->m_targetLocalId == NULL)
				{
					return nullptr;
				}

				GameObject^ unit = ObjectManager::GetUnitByIndex( m_targetLocalId );

				if (unit == nullptr)
				{
					return nullptr;
				}

				return unit;
			}
		}

		property bool IsToggle
		{
			bool get()
			{
				return SData->IsToggleSpell;
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