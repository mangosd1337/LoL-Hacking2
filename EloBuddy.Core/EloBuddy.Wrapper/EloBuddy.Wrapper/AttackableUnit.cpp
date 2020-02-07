#include "stdafx.h"

#include "AttackableUnit.hpp"
#include "GameObject.hpp"
#include "ObjectManager.hpp"
#include "StaticEnums.h"

namespace EloBuddy
{
	static AttackableUnit::AttackableUnit()
	{
		ATTACH_DOMAIN();
		ATTACH_EVENT
		(
			AttackableUnitModifyShield,
			29, Native::OnAttackableUnitModifyShield, Native::AttackableUnit*, float, float
		);
		ATTACH_EVENT
		(
			AttackableUnitDamage,
			30, Native::OnAttackableUnitOnDamage, Native::AttackableUnit*, Native::AttackableUnit*, float, Native::DamageLayout*
		);
	}

	void AttackableUnit::DomainUnloadEventHandler( System::Object^, System::EventArgs^ )
	{
		DETACH_EVENT
		(
			AttackableUnitModifyShield,
			29, Native::OnAttackableUnitModifyShield, Native::AttackableUnit*, float, float
		);
		DETACH_EVENT
		(
			AttackableUnitDamage,
			30, Native::OnAttackableUnitOnDamage, Native::AttackableUnit*, Native::AttackableUnit*, float, Native::DamageLayout*
		);
	}

	void AttackableUnit::OnAttackableUnitModifyShieldNative( Native::AttackableUnit* unit, float attackShield, float magicShield )
	{
		START_TRACE
			AttackableUnit^ sender = (AttackableUnit^) ObjectManager::CreateObjectFromPointer( unit );
			auto args = gcnew AttackableUnitModifyShieldEventArgs( attackShield, magicShield );

			for each (auto eventHandle in AttackableUnitModifyShieldHandlers->ToArray())
			{
				START_TRACE
					eventHandle(
						sender,
						args
					);
				END_TRACE
			}
		END_TRACE
	}

	void AttackableUnit::OnAttackableUnitDamageNative( Native::AttackableUnit* sender, Native::AttackableUnit* target, float damage, Native::DamageLayout* dmgLayout )
	{
		START_TRACE
			AttackableUnit^ m_sender = (AttackableUnit^) ObjectManager::CreateObjectFromPointer( sender );
			AttackableUnit^ m_target = (AttackableUnit^)ObjectManager::CreateObjectFromPointer( target );
			auto args = gcnew AttackableUnitDamageEventArgs( m_sender, m_target, DamageHitType::Normal, DamageType::Physical, damage, *dmgLayout->GetTime() );

			for each (auto eventHandle in AttackableUnitDamageHandlers->ToArray())
			{
				START_TRACE
					eventHandle(
						m_sender,
						args
					);
				END_TRACE
			}
		END_TRACE
	}
}