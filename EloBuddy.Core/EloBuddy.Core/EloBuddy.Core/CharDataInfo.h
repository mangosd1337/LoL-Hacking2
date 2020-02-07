#pragma once
#include "Macros.h"
#include "includes.h"
#include "Vector3f.h"

#define MAKE_INFO(NUM) NUM*0x4

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT CharDataInfo
		{
		public:
			MAKE_GET(Name, char*, 0);
			MAKE_GET(BaseSkinName, char*, 0);

			MAKE_GET(HPPerTick, float, MAKE_INFO(378)); //Deprecated
			MAKE_GET(XOffset, float, MAKE_INFO(379)); //Deprecated
			MAKE_GET(YOffset, float, MAKE_INFO(380)); //Deprecated
			MAKE_GET(WorldOffset, Vector3f*, MAKE_INFO(381));
			MAKE_GET(ShowWhileUntargetable, bool, MAKE_INFO(1568));

			MAKE_GET(MonsterDataTableID, int, MAKE_INFO(17));

			MAKE_GET(BaseHP, float, MAKE_INFO(18));
			MAKE_GET(HPPerLevel, float, MAKE_INFO(19));
			MAKE_GET(BaseStaticHPRegen, float, MAKE_INFO(20));
			MAKE_GET(BaseFactorHPRegen, float, MAKE_INFO(21));

			MAKE_GET(BaseMP, float, MAKE_INFO(24));
			MAKE_GET(MPPerLevel, float, MAKE_INFO(25));
			MAKE_GET(BaseStaticMPRegen, float, MAKE_INFO(26));
			MAKE_GET(BaseFactorMPRegen, float, MAKE_INFO(27));

			MAKE_GET(Armor, float, MAKE_INFO(43));
			MAKE_GET(SpellBlock, float, MAKE_INFO(45));
			MAKE_GET(BaseDodge, float, MAKE_INFO(47));
			MAKE_GET(BaseMissChance, float, MAKE_INFO(49));
			MAKE_GET(BaseCritChance, float, MAKE_INFO(50));
			MAKE_GET(CritDamageBonus, float, MAKE_INFO(52));
			MAKE_GET(MoveSpeed, float, MAKE_INFO(53));
			MAKE_GET(AttackRange, float, MAKE_INFO(54));
			MAKE_GET(AcquisitionRange, float, MAKE_INFO(145));
			MAKE_GET(FirstAcquisitionRange, float, MAKE_INFO(147));
			MAKE_GET(AttackAutoInterruptPercent, float, MAKE_INFO(148));
			MAKE_GET(TowerTargetingPriorityBoost, float, MAKE_INFO(149));
			MAKE_GET(AttackDelayCastOffsetPercentAttackSpeedRatio, float, MAKE_INFO(57));
			MAKE_GET(AttackDelayCastOffsetPercent, float, MAKE_INFO(75));
			MAKE_GET(AttackDelayOffsetPercent, float, 93);

			MAKE_GET(ExpGivenOnDeath, float, MAKE_INFO(151));
			MAKE_GET(GoldGivenOnDeath, float, MAKE_INFO(150));
			MAKE_GET(GoldRadius, float, MAKE_INFO(152));
			MAKE_GET(ExperienceRadius, float, MAKE_INFO(153));
			MAKE_GET(DeathEventListeningRadius, float, MAKE_INFO(154));
			MAKE_GET(LocalGoldGivenOnDeath, float, MAKE_INFO(155));
			MAKE_GET(LocalExpGivenOnDeath, float, MAKE_INFO(156));
			MAKE_GET(LocalGoldSplitWithLastHitter, float, 628);
			MAKE_GET(GlobalGoldGivenOnDeath, float, MAKE_INFO(158));
			MAKE_GET(GlobalExpGivenOnDeath, float, MAKE_INFO(159));
			MAKE_GET(PerceptionBubbleRadius, float, MAKE_INFO(157));
			MAKE_GET(Significance, float, MAKE_INFO(162));
			MAKE_GET(UntargetableSpawnTime, float, MAKE_INFO(163));
			MAKE_GET(BaseAbilityPower, float, MAKE_INFO(164));

			MAKE_GET(Spell1, char*, MAKE_INFO(167)); //LuxLightBinding
			MAKE_GET(Spell2, char*, MAKE_INFO(170)); //LuxPrismaticWave
			MAKE_GET(Spell3, char*, MAKE_INFO(173)); //LuxLightStrikeKugel
			MAKE_GET(Spell4, char*, MAKE_INFO(176)); //LuxMaliceCannon
			MAKE_GET(ExtraSpell1, char*, MAKE_INFO(179)); //LuxLightstrikeToggle
			MAKE_GET(ExtraSpell2, char*, MAKE_INFO(182)); //LuxMaliceCannonMis
			MAKE_GET(ExtraSpell3, char*, MAKE_INFO(185)); //LuxPrismaticWaveMissile
			MAKE_GET(ExtraSpell4, char*, MAKE_INFO(188)); //LuxLightBindingDummy
			MAKE_GET(ExtraSpell5, char*, MAKE_INFO(191)); //LuxRVfxMis
			MAKE_GET(ExtraSpell6, char*, MAKE_INFO(194)); //BaseSpell
			MAKE_GET(ExtraSpell7, char*, MAKE_INFO(197)); //BaseSpell
			MAKE_GET(ExtraSpell8, char*, MAKE_INFO(200)); //BaseSpell
			MAKE_GET(ExtraSpell9, char*, MAKE_INFO(203)); //BaseSpell
			MAKE_GET(ExtraSpell10, char*, MAKE_INFO(206)); //BaseSpell
			MAKE_GET(ExtraSpell11, char*, MAKE_INFO(209)); //BaseSpell
			MAKE_GET(ExtraSpell12, char*, MAKE_INFO(212)); //BaseSpell
			MAKE_GET(ExtraSpell13, char*, MAKE_INFO(215)); //BaseSpell
			MAKE_GET(ExtraSpell14, char*, MAKE_INFO(218)); //BaseSpell
			MAKE_GET(ExtraSpell15, char*, MAKE_INFO(221)); //BaseSpell
			MAKE_GET(ExtraSpell16, char*, MAKE_INFO(224)); //BaseSpell
			MAKE_GET(CriticalAttack, char*, MAKE_INFO(227)); //BaseSpell
			MAKE_GET(Passive1Name, char*, MAKE_INFO(230)); //game_character_passiveName_Lux
			MAKE_GET(Passive1LuaName, char*, MAKE_INFO(233)); //BadDesc
			MAKE_GET(Passive1Desc, char*, MAKE_INFO(236)); //LuxIlluminationPassive
			MAKE_GET(Passive1Desc1, char*, MAKE_INFO(239)); //game_character_passiveDescription_Lux
			MAKE_GET(PassiveSpell, char*, MAKE_INFO(246)); //LuxIlluminatingFraulein.dds
			MAKE_GET(Passive1Range, float, MAKE_INFO(247)); //27?

			MAKE_GET(BasicAttack1, char, 0x3E8);
			MAKE_GET(BasicAttack2, char, 0x400);
			MAKE_GET(BasicAttack3, char, 0x418);
			MAKE_GET(BasicAttack4, char, 0x430);
			MAKE_GET(BasicAttack5, char, 0x448);
			MAKE_GET(BasicAttack6, char, 0x460);
			MAKE_GET(BasicAttack7, char, 0x478);
			MAKE_GET(BasicAttack8, char, 0x490);
			MAKE_GET(BasicAttack9, char, 0x4A8);
			MAKE_GET(CritAttack1, char, 0x4C0);

			MAKE_GET(Lore1, char*, 0x648);
			MAKE_GET(Tips1, char*, 0x654);
			MAKE_GET(Tips2, char*, 0x660);

			MAKE_GET(HoverIndicatorRadius, float, MAKE_INFO(444));
			MAKE_GET(HoverIndicatorWidth, float, MAKE_INFO(443));
			MAKE_GET(HitFxScale, float, MAKE_INFO(249));
			MAKE_GET(SelectionHeight, float, MAKE_INFO(358));
			MAKE_GET(SelectionRadius, float, MAKE_INFO(359));
			MAKE_GET(PathfindingCollisionRadius, float, MAKE_INFO(360));
			MAKE_GET(GameplayCollisionRadius, float, MAKE_INFO(362));
			MAKE_GET(UnitTags, int, MAKE_INFO(461));

			float GetStatPerLevel(byte stat) const;
		};
	}
}