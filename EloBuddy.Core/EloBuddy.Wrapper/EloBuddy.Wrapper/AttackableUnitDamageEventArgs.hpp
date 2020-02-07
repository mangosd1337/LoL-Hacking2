#include "stdafx.h"
#include "AttackableUnit.hpp"
#include "StaticEnums.h"

namespace EloBuddy
{
	ref class AttackableUnit;

	public ref class AttackableUnitDamageEventArgs : public System::EventArgs
	{
	private:
		AttackableUnit^ m_source;
		AttackableUnit^ m_target;
		DamageHitType m_hitType;
		DamageType m_damageType;
		float m_gameTime;
		float m_damage;
	public:
		delegate void AttackableUnitDamageEvent( AttackableUnit^ sender, AttackableUnitDamageEventArgs^ args );

		AttackableUnitDamageEventArgs( AttackableUnit^ source, AttackableUnit^ target, DamageHitType hitType, DamageType damageType, float damage, float gameTime )
		{
			this->m_source = source;
			this->m_target = target;
			this->m_hitType = hitType;
			this->m_damageType = damageType;
			this->m_damage = damage;
		}

		property float Damage
		{
			float get() { return this->m_damage; }
		}

		property AttackableUnit^ Source
		{
			AttackableUnit^ get( ) { return this->m_source; }
		}

		property AttackableUnit^ Target
		{
			AttackableUnit^ get( ) { return this->m_target; }
		}

		property DamageType Type
		{
			DamageType get() { return this->m_damageType; }
		}

		property DamageHitType HitType
		{
			DamageHitType get() { return this->m_hitType; }
		}
	};
}