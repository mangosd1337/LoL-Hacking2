#include "stdafx.h"
#include "StaticEnums.h"

using namespace System;
using namespace SharpDX;

namespace EloBuddy
{
	ref class Obj_AI_Base;
	ref class Spellbook;

	public ref class SpellbookUpdateChargeableSpellEventArgs : public System::EventArgs
	{
	private:
		SpellSlot m_slot;
		Vector3 m_position;
		bool m_releaseCast;
		bool m_process;
	public:
		delegate void SpellbookUpdateChargeableSpellEvent( Spellbook^ sender, SpellbookUpdateChargeableSpellEventArgs^ args );

		SpellbookUpdateChargeableSpellEventArgs( SpellSlot slot, Vector3 position, bool releaseCast )
		{
			this->m_slot = slot;
			this->m_position = position;
			this->m_releaseCast = releaseCast;
			this->m_process = true;
		}

		property SpellSlot Slot
		{
			SpellSlot get()
			{
				return this->m_slot;
			}
		}

		property Vector3 Position
		{
			Vector3 get()
			{
				return this->m_position;
			}
		}

		property bool ReleaseCast
		{
			bool get()
			{
				return this->m_releaseCast;
			}
		}

		property bool Process
		{
			bool get()
			{
				return this->m_process;
			}
			void set( bool value )
			{
				this->m_process = value;
			}
		}
	};
}