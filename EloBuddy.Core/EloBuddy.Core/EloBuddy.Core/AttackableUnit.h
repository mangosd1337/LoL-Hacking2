#pragma once

#include "GameObject.h"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT DamageLayout
		{
		public:
			MAKE_GET( Time, float, 0x48 );
		};

		class
			DLLEXPORT AttackableUnit : public GameObject
		{
		public:
			static bool ApplyHooks();

			MAKE_GET( ArmorMaterial, std::string, Offsets::AttackableUnit::ArmorMaterial );
			MAKE_GET( AttackShield, float, Offsets::AttackableUnit::AttackShield );
			MAKE_GET( HasBotAI, bool, Offsets::AttackableUnit::HasBotAI );
			MAKE_GET( Health, float, Offsets::AttackableUnit::Health );
			MAKE_GET( IsBot, bool, Offsets::AttackableUnit::IsBot );
			MAKE_GET( IsInvulnerable, bool, Offsets::AttackableUnit::IsInVulnerable );
			MAKE_GET( IsLifestealImmune, bool, Offsets::AttackableUnit::IsLifestealImmune );
			MAKE_GET( IsPhysicalImmune, bool, Offsets::AttackableUnit::IsPhysicalImmune );
			MAKE_GET( IsZombie, bool, Offsets::AttackableUnit::IsZombie );
			MAKE_GET( MagicImmune, bool, Offsets::AttackableUnit::MagicImmune );
			MAKE_GET( AllShield, float, Offsets::AttackableUnit::AllShield );
			MAKE_GET( MagicShield, float, Offsets::AttackableUnit::MagicShield );
			MAKE_GET( Mana, float, Offsets::AttackableUnit::Mana );
			MAKE_GET( MaxHealth, float, Offsets::AttackableUnit::MaxHealth );
			MAKE_GET( MaxMana, float, Offsets::AttackableUnit::MaxMana );
			MAKE_GET( OverrideCollisionHeight, float, Offsets::AttackableUnit::OverrideCollisionHeight );
			MAKE_GET( OverrideCollisionRadius, float, Offsets::AttackableUnit::OverrideCollisionRadius );
			MAKE_GET( PathfindingCollisionRadius, float, Offsets::AttackableUnit::PathfindingCollisionRadius );
			MAKE_GET( WeaponMaterial, std::string, Offsets::AttackableUnit::WeaponMaterial );
			MAKE_GET( Direction, Vector3f, Offsets::AttackableUnit::Direction );

			bool IsMelee()
			{
				return *reinterpret_cast<byte*>(this + static_cast<int>(Offsets::Obj_AIBase::CombatType)) == 1;
			}

			bool IsRanged()
			{
				return *reinterpret_cast<byte*>(this + static_cast<int>(Offsets::Obj_AIBase::CombatType)) == 2;
			}

			bool IsAttackingPlayer()
			{
				return false;
			}

			float AttackableUnit::GetHealthPercent()
			{
				if (!this
					|| *this->GetIsDead()
					|| *this->GetHealth() == 0) return 0;

				return *this->GetHealth() * 100 / *this->GetMaxHealth();
			}

			float AttackableUnit::GetManaPercent()
			{
				if (!this
					|| *this->GetIsDead()
					|| *this->GetMana() == 0) return 0;

				return *this->GetMana() * 100 / *this->GetMaxMana();
			}

			static void ExportFunctions();
		};
	}
}
