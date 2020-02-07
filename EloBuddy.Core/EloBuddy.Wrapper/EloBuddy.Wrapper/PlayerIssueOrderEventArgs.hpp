#include "stdafx.h"

#include "GameObject.hpp"

using namespace System;
using namespace SharpDX;

namespace EloBuddy
{
	ref class Obj_AI_Base;
	ref class SpellData;

	public ref class PlayerIssueOrderEventArgs : public System::EventArgs
	{
	private:
		bool m_process;
		GameObjectOrder m_order;
		Vector3 m_targetPosition;
		GameObject^ m_target;
		bool m_attackMove;
	public:
		delegate void PlayerIssueOrderEvent( Obj_AI_Base^ sender, PlayerIssueOrderEventArgs^ args );

		PlayerIssueOrderEventArgs( GameObjectOrder order, Vector3 targetPosition, GameObject^ target, bool attackMove )
		{
			this->m_order = order;
			this->m_targetPosition = targetPosition;
			this->m_target = target;
			this->m_attackMove = attackMove;
			this->m_process = true;
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

		property GameObjectOrder Order
		{
			GameObjectOrder get()
			{
				return this->m_order;
			}
			void set( GameObjectOrder value )
			{
				this->m_order = value;
			}
		}

		property Vector3 TargetPosition
		{
			Vector3 get()
			{
				return this->m_targetPosition;
			}
		}

		property bool IsAttackMove
		{
			bool get()
			{
				return this->m_attackMove;
			}
		}

		property GameObject^ Target
		{
			GameObject^ get()
			{
				return this->m_target;
			}
		}
	};
}