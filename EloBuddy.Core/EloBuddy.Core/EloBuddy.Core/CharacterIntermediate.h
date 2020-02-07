#pragma once

#include "Macros.h"
#include "Obj_AI_Base.h"

#define PADDING -0x4

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT CharacterIntermediate
		{
		public:
			MAKE_GET( FlatCooldownMod, float, -0x1C ); //update
			MAKE_GET( PassiveCooldownEndTime, float, 0x24 );
			MAKE_GET( PassiveCooldownTotalTime, float, 0x28 );
			MAKE_GET( PercentCooldownMod, float, 0x28 );
			MAKE_GET( PercentCooldownCapMod, float, 0x14 );
			MAKE_GET( FlatHPPoolMod, float, 0x2C );
			MAKE_GET( PercentHPPoolMod, float, 0x30 );
			MAKE_GET( PercentBonusHPPoolMod, float, 0x34 );
			MAKE_GET( FlatPARPoolMod, float, 0x38 );
			MAKE_GET( PercentPARPoolMod, float, 0x3C );
			MAKE_GET( FlatHPRegenMod, float, 0x40 );
			MAKE_GET( PercentHPRegenMod, float, 0x44 );
			MAKE_GET( PercentBaseHPRegenMod, float, 0x48 );
			MAKE_GET( FlatPARRegenMod, float, 0x4C );
			MAKE_GET( PercentPARRegenMod, float, 0x50 );
			MAKE_GET( PercentTenacityCleanseMod, float, 0x58 );
			MAKE_GET( PercentTenacityCharacterMod, float, 0x60 );
			MAKE_GET( PercentTenacityItemMod, float, 0x64 );
			MAKE_GET( PercentTenacityMasteryMod, float, 0x5C );
			MAKE_GET( PercentTenacityRuneMod, float, 0x54 );
			MAKE_GET( PercentCCReduction, float, 0x68 );
			MAKE_GET( PercentSlowResistMod, float, 0x6C );
			MAKE_GET( FlatMovementSpeedHasteMod, float, 0x70 );
			MAKE_GET( FlatMovementSpeedSlowMod, float, 0x78 );
			MAKE_GET( PercentMovementSpeedHasteMod, float, 0x7C );
			MAKE_GET( PercentMovementSpeedSlowMod, float, 0x84 );
			MAKE_GET( PercentMultiplicativeMovementSpeedMod, float, 0x88 );
			MAKE_GET( MoveSpeedFloorMod, float, 0x8C );
			MAKE_GET( FlatArmorMod, float, 0x9C );
			MAKE_GET( PercentArmorMod, float, 0x0A0 );
			MAKE_GET( PercentBonusArmorMod, float, 0x0A4 );
			MAKE_GET( FlatArmorPenetrationMod, float, 0x0A8 );
			MAKE_GET( PercentArmorPenetrationMod, float, 0x0B0 );
			MAKE_GET( PercentBonusArmorPenetrationMod, float, 0x0B8 );
			MAKE_GET( FlatMagicPenetrationMod, float, 0x90 );		//update
			MAKE_GET( PercentMagicPenetrationMod, float, 0x98 );	//update
			MAKE_GET( PercentBonusMagicPenetrationMod, float, 0x0BC );
			MAKE_GET( FlatSpellBlockMod, float, 0x0C0 );
			MAKE_GET( PercentSpellBlockMod, float, 0x0C4 );
			MAKE_GET( PercentBonusSpellBlockMod, float, 0x0C8 );
			MAKE_GET( FlatMissChanceMod, float, 0x0CC );
			MAKE_GET( FlatDodgeMod, float, 0x0D0 );
			MAKE_GET( FlatCritChanceMod, float, 0x164 ); //update
			MAKE_GET( FlatCritDamageMod, float, 0x168 );
			MAKE_GET( PercentCritDamageMod, float, 0x16C );
			MAKE_GET( FlatPhysicalDamageMod, float, 0xD4 ); //update
			MAKE_GET( PercentPhysicalDamageMod, float, 0x0E4 PADDING );
			MAKE_GET( FlatMagicDamageMod, float, 0xDC ); //update
			MAKE_GET( PercentMagicDamageMod, float, 0x0EC PADDING );
			MAKE_GET( FlatPhysicalReduction, float, 0x0F0 PADDING );
			MAKE_GET( PercentPhysicalReduction, float, 0x0F4 PADDING );
			MAKE_GET( FlatMagicReduction, float, 0x0F8 PADDING );
			MAKE_GET( PercentMagicReduction, float, 0x0FC PADDING );
			MAKE_GET( PercentEXPBonus, float, 0x100 PADDING );
			MAKE_GET( FlatAttackRangeMod, float, 0x104 PADDING );
			MAKE_GET( PercentAttackRangeMod, float, 0x108 PADDING );
			MAKE_GET( FlatCastRangeMod, float, 0x10C PADDING );
			MAKE_GET( PercentCastRangeMod, float, 0x11C PADDING );
			MAKE_GET( PercentAttackSpeedMod, float, 0x128 PADDING );
			MAKE_GET( PercentMultiplicativeAttackSpeedMod, float, 0x12C PADDING );
			MAKE_GET( PercentHealingAmountMod, float, 0x130 PADDING );
			MAKE_GET( PercentLifeStealMod, float, 0x134 PADDING );
			MAKE_GET( PercentSpellVampMod, float, 0x138 PADDING );
			MAKE_GET( PercentRespawnTimeMod, float, 0x13C PADDING );
			MAKE_GET( PercentGoldLostOnDeathMod, float, 0x140 PADDING );
			MAKE_GET( AttackSpeedMod, float, 0x13C  ); //update
			MAKE_GET( BaseAttackDamage, float, 0x140 ); //update
			MAKE_GET( FlatBaseAttackDamageMod, float, 0xD4  );
			MAKE_GET( PercentBaseAttackDamageMod, float, 0x154  );
			MAKE_GET( BaseAbilityDamage, float, 0x158  ); //update
			MAKE_GET( CritDamageMultiplier, float, 0x15C  );
			MAKE_GET( ScaleSkinCoef, float, 0x160  );
			MAKE_GET( MissChance, float, 0x164  );
			MAKE_GET( Dodge, float, 0x168  );
			MAKE_GET( Crit, float, 0x16C  );
			MAKE_GET( Armor, float, 0x168  );		//update
			MAKE_GET( SpellBlock, float, 0x170  ); //update
			MAKE_GET( BaseHPRegenRate, float, 0x178  );
			MAKE_GET( HPRegenRate, float, 0x17C  ); //update
			MAKE_GET( BasePARRegenRate, float, 0x180  );
			MAKE_GET( PercentBasePARRegenMod, float, 0x184  );
			MAKE_GET( PARRegenRate, float, 0x188  );
			MAKE_GET( MoveSpeed, float, 0x18C  ); //update
			MAKE_GET( AttackRange, float, 0x190  ); //update
			MAKE_GET( CastRange, float, 0x194  ); //update
			MAKE_GET( FlatBubbleRadiusMod, float, 0x198  );
			MAKE_GET( PercentBubbleRadiusMod, float, 0x19C  );
			MAKE_GET( _FlatHPModPerLevel, float, 0x1A0  );
			MAKE_GET( _FlatMPModPerLevel, float, 0x1A4  );
			MAKE_GET( _FlatArmorModPerLevel, float, 0x1A8 );
			MAKE_GET( _FlatSpellBlockModPerLevel, float, 0x1AC  );
			MAKE_GET( _FlatHPRegenModPerLevel, float, 0x1B0  );
			MAKE_GET( _FlatMPRegenModPerLevel, float, 0x1B4  );
			MAKE_GET( _FlatPhysicalDamageModPerLevel, float, 0x1B8  );
			MAKE_GET( _FlatMagicDamageModPerLevel, float, 0x1BC  );
			MAKE_GET( _FlatMovementSpeedModPerLevel, float, 0x1C0  );
			MAKE_GET( _PercentMovementSpeedModPerLevel, float, 0x1C4  );
			MAKE_GET( _PercentAttackSpeedModPerLevel, float, 0x1C8  );
			MAKE_GET( _FlatCritChanceModPerLevel, float, 0x1CC  );
			MAKE_GET( _FlatCritDamageModPerLevel, float, 0x1D0  );
			MAKE_GET( _FlatDodgeMod, float, 0x1D4  );
			MAKE_GET( _FlatDodgeModPerLevel, float, 0x1D8  );
			MAKE_GET( _FlatArmorPenetrationMod, float, 0x1DC  );
			MAKE_GET( _FlatArmorPenetrationModPerLevel, float, 0x1E0  );
			MAKE_GET( _PercentArmorPenetrationMod, float, 0x1E4  );
			MAKE_GET( _PercentArmorPenetrationModPerLevel, float, 0x1E8  );
			MAKE_GET( _PercentCooldownMod, float, 0x1EC  );
			MAKE_GET( _PercentCooldownModPerLevel, float, 0x1F0  );
			MAKE_GET( _FlatTimeDeadMod, float, 0x1F4  );
			MAKE_GET( _FlatTimeDeadModPerLevel, float, 0x1F8  );
			MAKE_GET( _PercentTimeDeadMod, float, 0x1FC  );
			MAKE_GET( _PercentTimeDeadModPerLevel, float, 0x200  );
			MAKE_GET( FlatGoldPer10Mod, float, 0x204  );
			MAKE_GET( _FlatMagicPenetrationMod, float, 0x208  );
			MAKE_GET( _FlatMagicPenetrationModPerLevel, float, 0x20C  );
			MAKE_GET( _PercentMagicPenetrationMod, float, 0x210  );
			MAKE_GET( _PercentMagicPenetrationModPerLevel, float, 0x214  );
			MAKE_GET( _NonHealingFlatHPPoolMod, float, 0x218  );
			MAKE_GET( AcquisitionRangeMod, float, 0x120  );
			MAKE_GET( PathfindingRadiusMod, float, 0x124  );
			MAKE_GET( FlatExpRewardMod, float, 0x21C  );
			MAKE_GET( FlatGoldRewardMod, float, 0x220  );
			MAKE_GET( PercentLocalGoldRewardMod, float, 0x224  );
		};
	}
}
