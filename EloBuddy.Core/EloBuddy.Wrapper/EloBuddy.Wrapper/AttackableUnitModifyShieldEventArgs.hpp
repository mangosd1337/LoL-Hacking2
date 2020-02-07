#include "stdafx.h"
#include "AttackableUnit.hpp"
#include "StaticEnums.h"

namespace EloBuddy
{
	ref class AttackableUnit;

	public ref class AttackableUnitModifyShieldEventArgs : public System::EventArgs
	{
	private:
		float m_attackShield;
		float m_magicShield;
	public:
		delegate void AttackableUnitModifyShieldEvent( AttackableUnit^ sender, AttackableUnitModifyShieldEventArgs^ args );

		AttackableUnitModifyShieldEventArgs( float magicShield, float attackShield )
		{
			this->m_attackShield = attackShield;
			this->m_magicShield = magicShield;
		}

		property float MagicShield
		{
			float get() { return this->m_magicShield; }
		}

		property float AttackShield
		{
			float get() { return this->m_attackShield; }
		}
	};
}