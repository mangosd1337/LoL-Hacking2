#include "stdafx.h"

using namespace System;
using namespace SharpDX;

namespace EloBuddy
{
	ref class Spellbook;
	ref class GameObject;

	public ref class SpellbookCastSpellEventArgs : public System::EventArgs
	{
	private:
		bool m_process;
		Vector3 m_startPosition;
		Vector3 m_endPosition;
		GameObject^ m_target;
		SpellSlot m_slot;
	public:
		delegate void SpellbookCastSpellEvent( Spellbook^ sender, SpellbookCastSpellEventArgs^ args );

		SpellbookCastSpellEventArgs( Vector3 startPos, Vector3 endPos, GameObject^ target, SpellSlot slot)
		{
			this->m_startPosition = startPos;
			this->m_endPosition = endPos;
			this->m_target = target;
			this->m_slot = slot;
			this->m_process = true;
		}

		property bool Process
		{
			bool get( )
			{
				return this->m_process;
			}
			void set( bool value )
			{
				this->m_process = value;
			}
		}

		property Vector3 StartPosition
		{
			Vector3 get( )
			{
				return this->m_startPosition;
			}
		}

		property Vector3 EndPosition
		{
			Vector3 get( )
			{
				return this->m_endPosition;
			}
		}

		property SpellSlot Slot
		{
			SpellSlot get( )
			{
				return this->m_slot;
			}
			void set( SpellSlot value )
			{
				this->m_slot = value;
			}
		}

		property GameObject^ Target
		{
			GameObject^ get( )
			{
				return this->m_target;
			}
		}
	};
}