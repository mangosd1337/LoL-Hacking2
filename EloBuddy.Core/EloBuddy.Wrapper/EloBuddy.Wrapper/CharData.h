#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/CharDataInfo.h"

#include "StaticEnums.h"
#include "Macros.hpp"

using namespace System;
using namespace SharpDX;

namespace EloBuddy
{
	public ref class CharData
	{
	private:
		Native::CharDataInfo* m_charInfo;
	public:
		CharData::CharData(Native::CharDataInfo* charInfo)
		{
			this->m_charInfo = charInfo;
		}

		property String^ BaseSkinName
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetBaseSkinName());
			}
		}

		property float HPPerTick
		{
			float get()
			{
				return *this->m_charInfo->GetHPPerTick();
			}
		}

		property float XOffset
		{
			float get()
			{
				return *this->m_charInfo->GetXOffset();
			}
		}

		property float YOffset
		{
			float get()
			{
				return *this->m_charInfo->GetYOffset();
			}
		}

		property Vector3 WorldOffset
		{
			Vector3 get()
			{
				return Vector3::Zero;
			}
		}

		property bool ShowWhileUntargetable
		{
			bool get()
			{
				return *this->m_charInfo->GetShowWhileUntargetable();
			}
		}

		property int MonsterDataTableID
		{
			int get()
			{
				return *this->m_charInfo->GetMonsterDataTableID();
			}
		}
		property float BaseHP
		{
			float get()
			{
				return *this->m_charInfo->GetBaseHP();
			}
		}

		property float HPPerLevel
		{
			float get()
			{
				return *this->m_charInfo->GetHPPerLevel();
			}
		}

		property float BaseStaticHPRegen
		{
			float get()
			{
				return *this->m_charInfo->GetBaseStaticHPRegen();
			}
		}

		property float BaseFactorHPRegen
		{
			float get()
			{
				return *this->m_charInfo->GetBaseFactorHPRegen();
			}
		}

		property float BaseMP
		{
			float get()
			{
				return *this->m_charInfo->GetBaseMP();
			}
		}

		property float MPPerLevel
		{
			float get()
			{
				return *this->m_charInfo->GetMPPerLevel();
			}
		}

		property float BaseStaticMPRegen
		{
			float get()
			{
				return *this->m_charInfo->GetBaseStaticMPRegen();
			}
		}

		property float Armor
		{
			float get()
			{
				return *this->m_charInfo->GetArmor();
			}
		}

		property float SpellBlock
		{
			float get()
			{
				return *this->m_charInfo->GetSpellBlock();
			}
		}

		property float BaseDodge
		{
			float get()
			{
				return *this->m_charInfo->GetBaseDodge();
			}
		}

		property float BaseMissChance
		{
			float get()
			{
				return *this->m_charInfo->GetBaseMissChance();
			}
		}

		property float BaseCritChance
		{
			float get()
			{
				return *this->m_charInfo->GetBaseCritChance();
			}
		}

		property float CritDamageBonus
		{
			float get()
			{
				return *this->m_charInfo->GetCritDamageBonus();
			}
		}

		property float MoveSpeed
		{
			float get()
			{
				return *this->m_charInfo->GetMoveSpeed();
			}
		}

		property float AttackRange
		{
			float get()
			{
				return *this->m_charInfo->GetAttackRange();
			}
		}

		property float AcquisitionRange
		{
			float get()
			{
				return *this->m_charInfo->GetAcquisitionRange();
			}
		}

		property float FirstAcquisitionRange
		{
			float get()
			{
				return *this->m_charInfo->GetFirstAcquisitionRange();
			}
		}

		property float AttackAutoInterruptPercent
		{
			float get()
			{
				return *this->m_charInfo->GetAttackAutoInterruptPercent();
			}
		}

		property float TowerTargetingPriorityBoost
		{
			float get()
			{
				return *this->m_charInfo->GetTowerTargetingPriorityBoost();
			}
		}

		property float AttackDelayCastOffsetPercentAttackSpeedRatio
		{
			float get()
			{
				return *this->m_charInfo->GetAttackDelayCastOffsetPercentAttackSpeedRatio();
			}
		}

		property float AttackDelayCastOffsetPercent
		{
			float get()
			{
				return *this->m_charInfo->GetAttackDelayCastOffsetPercent();
			}
		}

		property float AttackDelayOffsetPercent
		{
			float get()
			{
				return *this->m_charInfo->GetAttackDelayOffsetPercent();
			}
		}


		property float ExpGivenOnDeath
		{
			float get()
			{
				return *this->m_charInfo->GetExpGivenOnDeath();
			}
		}

		property float GoldGivenOnDeath
		{
			float get()
			{
				return *this->m_charInfo->GetGoldGivenOnDeath();
			}
		}

		property float GoldRadius
		{
			float get()
			{
				return *this->m_charInfo->GetGoldRadius();
			}
		}

		property float ExperienceRadius
		{
			float get()
			{
				return *this->m_charInfo->GetExperienceRadius();
			}
		}

		property float DeathEventListeningRadius
		{
			float get()
			{
				return *this->m_charInfo->GetDeathEventListeningRadius();
			}
		}

		property float LocalGoldGivenOnDeath
		{
			float get()
			{
				return *this->m_charInfo->GetLocalGoldGivenOnDeath();
			}
		}

		property float LocalExpGivenOnDeath
		{
			float get()
			{
				return *this->m_charInfo->GetLocalExpGivenOnDeath();
			}
		}

		property float LocalGoldSplitWithLastHitter
		{
			float get()
			{
				return *this->m_charInfo->GetLocalGoldSplitWithLastHitter();
			}
		}

		property float GlobalGoldGivenOnDeath
		{
			float get()
			{
				return *this->m_charInfo->GetGlobalGoldGivenOnDeath();
			}
		}

		property float GlobalExpGivenOnDeath
		{
			float get()
			{
				return *this->m_charInfo->GetGlobalExpGivenOnDeath();
			}
		}

		property float PerceptionBubbleRadius
		{
			float get()
			{
				return *this->m_charInfo->GetPerceptionBubbleRadius();
			}
		}

		property float Significance
		{
			float get()
			{
				return *this->m_charInfo->GetSignificance();
			}
		}

		property float UntargetableSpawnTime
		{
			float get()
			{
				return *this->m_charInfo->GetUntargetableSpawnTime();
			}
		}

		property float BaseAbilityPower
		{
			float get()
			{
				return *this->m_charInfo->GetBaseAbilityPower();
			}
		}

		property String^ Spell1
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetSpell1());
			}
		}

		property String^ Spell2
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetSpell2());
			}
		}

		property String^ Spell3
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetSpell3());
			}
		}

		property String^ Spell4
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetSpell4());
			}
		}

		property String^ ExtraSpell1
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetExtraSpell1());
			}
		}

		property String^ ExtraSpell2
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetExtraSpell2());
			}
		}

		property String^ ExtraSpell3
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetExtraSpell3());
			}
		}

		property String^ ExtraSpell4
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetExtraSpell4());
			}
		}

		property String^ ExtraSpell5
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetExtraSpell5());
			}
		}

		property String^ ExtraSpell6
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetExtraSpell6());
			}
		}

		property String^ ExtraSpell7
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetExtraSpell7());
			}
		}

		property String^ ExtraSpell8
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetExtraSpell8());
			}
		}

		property String^ ExtraSpell9
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetExtraSpell9());
			}
		}

		property String^ ExtraSpell10
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetExtraSpell10());
			}
		}

		property String^ ExtraSpell112
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetExtraSpell11());
			}
		}

		property String^ ExtraSpell12
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetExtraSpell12());
			}
		}

		property String^ ExtraSpell13
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetExtraSpell13());
			}
		}

		property String^ ExtraSpell14
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetExtraSpell14());
			}
		}

		property String^ ExtraSpell15
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetExtraSpell15());
			}
		}

		property String^ ExtraSpell16
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetExtraSpell16());
			}
		}

		property String^ CriticalAttack
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetCriticalAttack());
			}
		}

		property String^ Passive1Name
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetPassive1Name());
			}
		}

		property String^ Passive1LuaName
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetPassive1LuaName());
			}
		}

		property String^ Passive1Desc
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetPassive1Desc());
			}
		}

		property String^ Passive1Desc1
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetPassive1Desc1());
			}
		}

		property String^ PassiveSpell
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetPassiveSpell());
			}
		}

		property float Passive1Range
		{
			float get()
			{
				return *this->m_charInfo->GetPassive1Range();
			}
		}

		property String^ BasicAttack1
		{
			String^ get()
			{
				return gcnew String(this->m_charInfo->GetBasicAttack1());
			}
		}

		property String^ BasicAttack2
		{
			String^ get()
			{
				return gcnew String(this->m_charInfo->GetBasicAttack2());
			}
		}

		property String^ BasicAttack3
		{
			String^ get()
			{
				return gcnew String(this->m_charInfo->GetBasicAttack3());
			}
		}

		property String^ BasicAttack4
		{
			String^ get()
			{
				return gcnew String(this->m_charInfo->GetBasicAttack4());
			}
		}

		property String^ BasicAttack5
		{
			String^ get()
			{
				return gcnew String(this->m_charInfo->GetBasicAttack5());
			}
		}

		property String^ BasicAttack6
		{
			String^ get()
			{
				return gcnew String(this->m_charInfo->GetBasicAttack6());
			}
		}

		property String^ BasicAttack7
		{
			String^ get()
			{
				return gcnew String(this->m_charInfo->GetBasicAttack7());
			}
		}

		property String^ BasicAttack8
		{
			String^ get()
			{
				return gcnew String(this->m_charInfo->GetBasicAttack8());
			}
		}

		property String^ BasicAttack9
		{
			String^ get()
			{
				return gcnew String(this->m_charInfo->GetBasicAttack9());
			}
		}

		property String^ CritAttack1
		{
			String^ get()
			{
				return gcnew String(this->m_charInfo->GetCritAttack1());
			}
		}

		property String^ Lore1
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetLore1());
			}
		}

		property String^ Tips1
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetTips1());
			}
		}

		property String^ Tips2
		{
			String^ get()
			{
				return gcnew String(*this->m_charInfo->GetTips2());
			}
		}

		property float HoverIndicatorRadius
		{
			float get()
			{
				return *this->m_charInfo->GetHoverIndicatorRadius();
			}
		}
		property float HoverIndicatorWidth
		{
			float get()
			{
				return *this->m_charInfo->GetHoverIndicatorWidth();
			}
		}
		property float HitFxScale
		{
			float get()
			{
				return *this->m_charInfo->GetHitFxScale();
			}
		}
		property float SelectionHeight
		{
			float get()
			{
				return *this->m_charInfo->GetSelectionHeight();
			}
		}
		property float SelectionRadius
		{
			float get()
			{
				return *this->m_charInfo->GetSelectionRadius();
			}
		}
		property float PathfindingCollisionRadius
		{
			float get()
			{
				return *this->m_charInfo->GetPathfindingCollisionRadius();
			}
		}
		property float AttaGameplayCollisionRadiusckRange
		{
			float get()
			{
				return *this->m_charInfo->GetGameplayCollisionRadius();
			}
		}
		property int UnitTags
		{
			int get()
			{
				return *this->m_charInfo->GetUnitTags();
			}
		}
	};
}