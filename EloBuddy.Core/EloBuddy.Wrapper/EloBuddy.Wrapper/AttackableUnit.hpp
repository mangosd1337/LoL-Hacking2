#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/AttackableUnit.h"
#include "../../EloBuddy.Core/EloBuddy.Core/EventHandler.h"

#include "Macros.hpp"
#include "GameObject.hpp"

#include "AttackableUnitDamageEventArgs.hpp"
#include "AttackableUnitModifyShieldEventArgs.hpp"

using namespace SharpDX;
using namespace System;
using namespace System::Collections::Generic;

namespace EloBuddy
{
	MAKE_EVENT_GLOBAL( AttackableUnitModifyShield, AttackableUnit^ sender, AttackableUnitModifyShieldEventArgs^ args );
	MAKE_EVENT_GLOBAL( AttackableUnitDamage, AttackableUnit^ sender, AttackableUnitDamageEventArgs^ args );

	public ref class AttackableUnit : public GameObject {
	internal:
		MAKE_EVENT_INTERNAL( AttackableUnitModifyShield, (Native::AttackableUnit*, float attackShield, float magicShield) );
		MAKE_EVENT_INTERNAL( AttackableUnitDamage, (Native::AttackableUnit*, Native::AttackableUnit*, float, Native::DamageLayout*) );

		Native::AttackableUnit* GetPtr()
		{
			return reinterpret_cast<Native::AttackableUnit*>(GameObject::GetPtr());
		}
	private:
		Native::AttackableUnit* self;

	public:
		MAKE_EVENT_PUBLIC( OnModifyShield, AttackableUnitModifyShield );
		MAKE_EVENT_PUBLIC( OnDamage, AttackableUnitDamage );

		AttackableUnit( ushort index, uint networkId, Native::GameObject* unit ) : GameObject( index, networkId, unit ) {}
		AttackableUnit::AttackableUnit() {};
		static AttackableUnit::AttackableUnit();
		static void DomainUnloadEventHandler( System::Object^, System::EventArgs^ );

		property Vector3 Direction
		{
			Vector3 get()
			{
				auto ptr = this->GetPtr();
				if (ptr != nullptr)
				{
					auto vec = ptr->GetDirection();
					return Vector3( vec->GetX(), vec->GetZ(), vec->GetY() );
				}
				return Vector3::Zero;
			}
		}

		property bool IsMelee
		{
			bool get()
			{
				auto ptr = this->GetPtr();
				if (ptr != nullptr)
				{
					return ptr->IsMelee();
				}
				return false;
			}
		}

		property bool IsRanged
		{
			bool get()
			{
				auto ptr = this->GetPtr();
				if (ptr != nullptr)
				{
					return ptr->IsRanged();
				}
				return false;
			}
		}

		property bool IsAttackingPlayer
		{
			bool get()
			{
				auto ptr = this->GetPtr();
				if (ptr != nullptr)
				{
					return ptr->IsAttackingPlayer();
				}
				return false;
			}
		}

		property bool IsTargetable
		{
			bool get()
			{
				auto ptr = this->GetPtr();
				if (ptr != nullptr)
				{
					auto vt = ptr->GetVirtual();
					if (vt != nullptr)
					{
						return vt->IsTargetable();
					}
				}
				return false;
			}
		}

		property bool IsTargetableToTeam
		{
			bool get()
			{
				auto ptr = this->GetPtr();
				if (ptr != nullptr)
				{
					auto vt = ptr->GetVirtual();
					if (vt != nullptr)
					{
						return vt->IsTargetableToTeam();
					}
				}
				return false;
			}
		}

		MAKE_STRING( ArmorMaterial );
		CREATE_GET( AllShield, float );
		CREATE_GET( AttackShield, float );
		CREATE_GET( HasBotAI, int );
		CREATE_GET( Health, float );
		CREATE_GET( IsBot, int );
		CREATE_GET( IsInvulnerable, bool );
		CREATE_GET( IsLifestealImmune, bool );
		CREATE_GET( IsPhysicalImmune, bool );
		CREATE_GET( IsZombie, bool );
		CREATE_GET( MagicImmune, bool );
		CREATE_GET( MagicShield, float );
		CREATE_GET( Mana, float );
		CREATE_GET( MaxHealth, float );
		CREATE_GET( MaxMana, float );
		CREATE_GET_G( HealthPercent, float );
		CREATE_GET_G( ManaPercent, float );
		MAKE_STRING( WeaponMaterial );

		CREATE_GET( OverrideCollisionHeight, float );
		CREATE_GET( OverrideCollisionRadius, float );
		CREATE_GET( PathfindingCollisionRadius, float );
	};
}