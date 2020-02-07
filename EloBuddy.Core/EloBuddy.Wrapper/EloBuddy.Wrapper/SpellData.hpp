#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/SpellData.h"

#include "StaticEnums.h"
#include "Macros.hpp"

using namespace System;
using namespace System::Security;
using namespace System::Reflection;
using namespace System::Runtime::CompilerServices;
using namespace System::Runtime::InteropServices;
using namespace System::Security::Permissions;

#include "RiotString.h"

namespace EloBuddy
{
	public ref class SpellData
	{
	internal:
		Native::SpellData* GetPtr() { return this->self; }
	private:
		Native::SpellData* self;
	public:
		SpellData(Native::SpellData* spelldata);
		SpellData( uint hash );
		SpellData( String^ name );

		static SpellData^ GetSpellData( String^ name );

		property SpellDataTargetType TargettingType
		{
			SpellDataTargetType get()
			{
				if (this->GetPtr() == nullptr)
					return SpellDataTargetType::Self;

				return (SpellDataTargetType)*this->GetPtr()->GetTargettingType();
			}
		}

		//SData Pointer:
		MAKE_STRING( Name );

		MAKE_ARRAY( ManaCostArray, float, 6 );
		MAKE_ARRAY( CastRangeArray, float, 6 );
		MAKE_ARRAY( CastRangeDisplayOverrideArray, float, 6 );
		MAKE_ARRAY( CastRangeGrowthDurationArray, float, 6 );
		MAKE_ARRAY( CastRangeGrowthMaxArray, float, 6 );
		MAKE_ARRAY( ChannelDurationArray, float, 6 );
		MAKE_ARRAY( CastRadiusArray, float, 6 );
		MAKE_ARRAY( CastRadiusSecondaryArray, float, 6 );
		MAKE_ARRAY( AmmoRechargeTimeArray, float, 6 );
		MAKE_ARRAY( AmmoUsedArray, int, 6 );
		MAKE_ARRAY( CooldownArray, float, 6 );
		MAKE_ARRAY( FloatVarsDecimalsArray, float, 16 );
		MAKE_ARRAY( MaxAmmoArray, int, 6 );
		MAKE_ARRAY( LocationTargettingLengthArray, float, 6 );
		MAKE_ARRAY( LocationTargettingWidthArray, float, 6 );

		CREATE_GET(Flags, int);
		CREATE_GET(AffectsTypeFlags, int);
		CREATE_GET(AffectsStatusFlags, int);
		CREATE_GET(RequiredUnitTags, int);
		CREATE_GET(ExcludedUnitTags, int);
		CREATE_GET(PlatformSpellInfo, int);
		MAKE_CC_STRING(AlternateName);
		MAKE_CC_STRING(DisplayName);
		MAKE_CC_STRING(Description);
		MAKE_CC_STRING(SpellTags);
		MAKE_CC_STRING(DynamicTooltip);
		MAKE_CC_STRING(DynamicExtended);
		CREATE_GET(EffectAmount, int);
		CREATE_GET(Coefficient, float);
		CREATE_GET(Coefficient2, float);
		CREATE_GET(MaxHighlightTargets, int);
		MAKE_CC_STRING(AnimationName);
		MAKE_CC_STRING(AnimationLoopName);
		MAKE_CC_STRING(AnimationWinddownName);
		MAKE_CC_STRING(AnimationLeadOutName);
		MAKE_CC_STRING(ImgIconName);
		MAKE_CC_STRING(MinimapIconName);
		MAKE_CC_STRING(KeywordWhenAcquired);
		MAKE_CC_STRING(SummonerSpellUpgradeDescription);
		CREATE_GET(CastTime, float);
		CREATE_GET(ChannelDuration, float);
		CREATE_GET(CooldownTime, float);
		CREATE_GET(DelayCastOffsetPercent, float);
		CREATE_GET(DelayTotalTimePercent, float);
		CREATE_GET(StartCooldown, float);
		CREATE_GET(CastRangeGrowthMax, float);
		CREATE_GET(CastRangeGrowthStartTime, float);
		CREATE_GET(CastRangeGrowthDuration, float);
		CREATE_GET(ChargeUpdateInterval, float);
		CREATE_GET(CancelChargeOnRecastTime, float);
		CREATE_GET(MaxAmmo, int);
		CREATE_GET(AmmoUsed, int);
		CREATE_GET(AmmoRechargeTime, float);
		CREATE_GET(AmmoNotAffectedByCDR, float);
		CREATE_GET(AmmoCountHiddenInUI, float);
		CREATE_GET(CostAlwaysShownInUI, bool);
		CREATE_GET(CannotBeSuppressed, float);
		CREATE_GET(CanCastWhileDisabled, bool);
		CREATE_GET(CanCastOrQueueWhileCasting, bool);
		CREATE_GET(CanOnlyCastWhileDisabled, bool);
		CREATE_GET(CantCancelWhileWindingUp, bool);
		CREATE_GET(CantCancelWhileChanneling, bool);
		CREATE_GET(CantCastWhileRooted, bool);
		CREATE_GET(ApplyAttackDamage, bool);
		CREATE_GET(ApplyAttackEffect, bool);
		CREATE_GET(ApplyMaterialOnHitSound, bool);
		CREATE_GET(DoesntBreakChannels, bool);
		CREATE_GET(BelongsToAvatar, bool);
		CREATE_GET(IsDisabledWhileDead, bool);
		CREATE_GET(CanOnlyCastWhileDead, bool);
		CREATE_GET(CursorChangesInGrass, bool);
		CREATE_GET(CursorChangesInTerrain, bool);
		CREATE_GET(LineMissileEndsAtTargetPoint, bool);
		CREATE_GET(SpellRevealsChampion, bool);
		CREATE_GET(LineMissileTrackUnits, bool);
		CREATE_GET(LineMissileTrackUnitsAndContinues, bool);
		CREATE_GET(UseMinimapTargeting, bool);
		CREATE_GET(CastRangeUseBoundingBoxes, bool);
		CREATE_GET(MinimapIconRotation, bool);
		CREATE_GET(UseChargeChanneling, bool);
		CREATE_GET(UseChargeTargeting, bool);
		CREATE_GET(CanMoveWhileChanneling, bool);
		CREATE_GET(DisableCastBar, bool);
		CREATE_GET(ShowChannelBar, bool);
		CREATE_GET(AlwaysSnapFacing, bool);
		CREATE_GET(UseAnimatorFramerate, bool);
		CREATE_GET(HaveHitEffect, bool);
		CREATE_GET(HaveHitBone, bool);
		CREATE_GET(HaveAfterEffect, bool);
		CREATE_GET(HavePointEffect, bool);
		CREATE_GET(IsToggleSpell, bool);
		CREATE_GET(LineMissileBounces, bool);
		CREATE_GET(LineMissileUsesAccelerationForBounce, bool);
		CREATE_GET(MissileFollowsTerrainHeight, bool);
		CREATE_GET(DoNotNeedToFaceTarget, bool);
		CREATE_GET(NoWinddownIfCancelled, bool);
		CREATE_GET(IgnoreRangeCheck, bool);
		CREATE_GET(OrientRadiusTextureFromPlayer, bool);
		CREATE_GET(UseAutoattackCastTime, float);
		CREATE_GET(IgnoreAnimContinueUntilCastFrame, bool);
		CREATE_GET(HideRangeIndicatorWhenCasting, bool);
		CREATE_GET(UpdateRotationWhenCasting, bool);
		CREATE_GET(ConsideredAsAutoAttack, bool);
		CREATE_GET(MinimapIconDisplayFlag, bool);
		CREATE_GET(CastRange, float);
		CREATE_GET(CastRangeDisplayOverride, float);
		CREATE_GET(CastRadius, float);
		CREATE_GET(CastRadiusSecondary, float);
		CREATE_GET(CastConeAngle, float);
		CREATE_GET(CastConeDistance, float);
		CREATE_GET(CastTargetAdditionalUnitsRadius, float);
		CREATE_GET(BounceRadius, float);
		CREATE_GET(MissileGravity, float);
		CREATE_GET(MissileTargetHeightAugment, float);
		CREATE_GET(LineMissileTargetHeightAugment, float);
		CREATE_GET(LineMissileDelayDestroyAtEndSeconds, float);
		CREATE_GET(LineMissileTimePulseBetweenCollisionSpellHits, float);
		CREATE_GET(LuaOnMissileUpdateDistanceInterval, float);
		CREATE_GET(CastType, int);
		CREATE_GET(CastFrame, float);
		CREATE_GET(SpellDamageRatio, float);
		CREATE_GET(PhysicalDamageRatio, float);
		CREATE_GET(MissileSpeed, float);
		CREATE_GET(MissileAccel, float);
		CREATE_GET(MissileMaxSpeed, float);
		CREATE_GET(MissileMinSpeed, float);
		CREATE_GET(MissileFixedTravelTime, float);
		CREATE_GET(MissileLifetime, float);
		MAKE_CC_STRING(MissileEffectName);
		MAKE_CC_STRING(MissileEffectPlayerName);
		MAKE_CC_STRING(MissileEffectEnemyName);
		MAKE_CC_STRING(MissileBoneName);
		MAKE_CC_STRING(TargetBoneName);
		CREATE_GET(MissileUnblockable, bool);
		CREATE_GET(MissileBlockTriggersOnDestroy, bool);
		CREATE_GET(MissilePerceptionBubbleRadius, float);
		CREATE_GET(MissilePerceptionBubbleRevealsStealth, bool);
		CREATE_GET(CircleMissileRadialVelocity, float);
		CREATE_GET(CircleMissileAngularVelocity, float);
		CREATE_GET(LineWidth, float);
		CREATE_GET(LineDragLength, float);
		CREATE_GET(LookAtPolicy, int);
		CREATE_GET(HitEffectOrientType, int);
		MAKE_CC_STRING(HitBoneName);
		MAKE_CC_STRING(HitEffectName);
		MAKE_CC_STRING(HitEffectPlayerName);
		MAKE_CC_STRING(AfterEffectName);
		MAKE_CC_STRING(PointEffectName);
		CREATE_GET(DeathRecapPriority, float);
		CREATE_GET(FloatVarsDecimals, float);
		CREATE_GET(Mana, float);
		CREATE_GET(ManaUiOverride, bool);
		CREATE_GET(SelectionPriority, int);
		MAKE_CC_STRING(VOEventCategory);
		CREATE_GET(AIData, int);
		CREATE_GET(ClientData, int);


		//Manual
		CREATE_GET( SpellCastTime, float );
		CREATE_GET( SpellTotalTime, float );

		// Translated Strings
		property String^ DynamicExtendedTranslated
		{
			String^ get()
			{
				return RiotString::Translate( this->DynamicExtended );
			}
		}

		property String^ DynamicTooltipTranslated
		{
			String^ get()
			{
				return RiotString::Translate( this->DynamicTooltip );
			}
		}

		property String^ DescriptionTranslated
		{
			String^ get()
			{
				return RiotString::Translate( this->Description );
			}
		}

		property String^ DisplayNameTranslated
		{
			String^ get()
			{
				return RiotString::Translate( this->DisplayName );
			}
		}
	};
}