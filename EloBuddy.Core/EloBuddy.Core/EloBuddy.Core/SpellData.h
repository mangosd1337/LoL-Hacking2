#pragma once
#include "Utils.h"
#include "Vector3f.h"

namespace EloBuddy
{
	namespace Native
	{
#pragma pack(push, 1)
		class
			DLLEXPORT SpellData
		{
		public:
			static SpellData* FindSpell( char* spellName );
			static SpellData* FindSpell( uint hash );
			static int HashSpell( char* spellName );

			DWORD* GetSDataArray()
			{
				return *reinterpret_cast<DWORD**>(reinterpret_cast<DWORD*>(this + 0x34)); //+0x4
			}

			MAKE_GET( Name, std::string, 0x18 );

			MAKE_SDATA(Flags, int, 0x4);
			MAKE_SDATA(AffectsTypeFlags, int, 0x8);
			MAKE_SDATA(AffectsStatusFlags, int, 0xc);
			MAKE_SDATA(RequiredUnitTags, int, 0x10);
			MAKE_SDATA(ExcludedUnitTags, int, 0x28);
			MAKE_SDATA(PlatformSpellInfo, int, 0x4c);
			MAKE_SDATA(AlternateName, char*, 0x64);
			MAKE_SDATA(DisplayName, char*, 0x70);
			MAKE_SDATA(Description, char*, 0x7c);
			MAKE_SDATA(SpellTags, char*, 0x94);
			MAKE_SDATA(DynamicTooltip, char*, 0xa0);
			MAKE_SDATA(DynamicExtended, char*, 0xac);
			MAKE_SDATA(EffectAmount, int, 0xb8);
			MAKE_SDATA(Coefficient, float, 0x1d0);
			MAKE_SDATA(Coefficient2, float, 0x1d4);
			MAKE_SDATA(MaxHighlightTargets, int, 0x1d8);
			MAKE_SDATA(AnimationName, char*, 0x1dc);
			MAKE_SDATA(AnimationLoopName, char*, 0x1e8);
			MAKE_SDATA(AnimationWinddownName, char*, 0x1f4);
			MAKE_SDATA(AnimationLeadOutName, char*, 0x200);
			MAKE_SDATA(ImgIconName, char*, 0x20c);
			MAKE_SDATA(MinimapIconName, char*, 0x218);
			MAKE_SDATA(KeywordWhenAcquired, char*, 0x224);
			MAKE_SDATA(SummonerSpellUpgradeDescription, char*, 0x230);
			MAKE_SDATA(CastTime, float, 0x274);
			MAKE_SDATA(ChannelDuration, float, 0x278);
			MAKE_SDATA(CooldownTime, float, 0x298);
			MAKE_SDATA(DelayCastOffsetPercent, float, 0x2b4);
			MAKE_SDATA(DelayTotalTimePercent, float, 0x2b8);
			MAKE_SDATA(StartCooldown, float, 0x2bc);
			MAKE_SDATA(CastRangeGrowthMax, float, 0x2c0);
			MAKE_SDATA(CastRangeGrowthStartTime, float, 0x2dc);
			MAKE_SDATA(CastRangeGrowthDuration, float, 0x2f8);
			MAKE_SDATA(ChargeUpdateInterval, float, 0x314);
			MAKE_SDATA(CancelChargeOnRecastTime, float, 0x318);
			MAKE_SDATA(MaxAmmo, int, 0x31c);
			MAKE_SDATA(AmmoUsed, int, 0x338);
			MAKE_SDATA(AmmoRechargeTime, float, 0x354);
			MAKE_SDATA(AmmoNotAffectedByCDR, float, 0x370);
			MAKE_SDATA(AmmoCountHiddenInUI, float, 0x371);
			MAKE_SDATA(CostAlwaysShownInUI, bool, 0x372);
			MAKE_SDATA(CannotBeSuppressed, float, 0x373);
			MAKE_SDATA(CanCastWhileDisabled, bool, 0x374);
			MAKE_SDATA(CanCastOrQueueWhileCasting, bool, 0x375);
			MAKE_SDATA(CanOnlyCastWhileDisabled, bool, 0x376);
			MAKE_SDATA(CantCancelWhileWindingUp, bool, 0x377);
			MAKE_SDATA(CantCancelWhileChanneling, bool, 0x378);
			MAKE_SDATA(CantCastWhileRooted, bool, 0x379);
			MAKE_SDATA(ApplyAttackDamage, bool, 0x37a);
			MAKE_SDATA(ApplyAttackEffect, bool, 0x37b);
			MAKE_SDATA(ApplyMaterialOnHitSound, bool, 0x37c);
			MAKE_SDATA(DoesntBreakChannels, bool, 0x37d);
			MAKE_SDATA(BelongsToAvatar, bool, 0x37e);
			MAKE_SDATA(IsDisabledWhileDead, bool, 0x37f);
			MAKE_SDATA(CanOnlyCastWhileDead, bool, 0x380);
			MAKE_SDATA(CursorChangesInGrass, bool, 0x381);
			MAKE_SDATA(CursorChangesInTerrain, bool, 0x382);
			MAKE_SDATA(LineMissileEndsAtTargetPoint, bool, 0x383);
			MAKE_SDATA(SpellRevealsChampion, bool, 0x384);
			MAKE_SDATA(LineMissileTrackUnits, bool, 0x385);
			MAKE_SDATA(LineMissileTrackUnitsAndContinues, bool, 0x386);
			MAKE_SDATA(UseMinimapTargeting, bool, 0x387);
			MAKE_SDATA(CastRangeUseBoundingBoxes, bool, 0x388);
			MAKE_SDATA(MinimapIconRotation, bool, 0x389);
			MAKE_SDATA(UseChargeChanneling, bool, 0x38a);
			MAKE_SDATA(UseChargeTargeting, bool, 0x38b);
			MAKE_SDATA(CanMoveWhileChanneling, bool, 0x38c);
			MAKE_SDATA(DisableCastBar, bool, 0x38d);
			MAKE_SDATA(ShowChannelBar, bool, 0x38e);
			MAKE_SDATA(AlwaysSnapFacing, bool, 0x38f);
			MAKE_SDATA(UseAnimatorFramerate, bool, 0x390);
			MAKE_SDATA(HaveHitEffect, bool, 0x391);
			MAKE_SDATA(HaveHitBone, bool, 0x392);
			MAKE_SDATA(HaveAfterEffect, bool, 0x393);
			MAKE_SDATA(HavePointEffect, bool, 0x394);
			MAKE_SDATA(IsToggleSpell, bool, 0x395);
			MAKE_SDATA(LineMissileBounces, bool, 0x396);
			MAKE_SDATA(LineMissileUsesAccelerationForBounce, bool, 0x397);
			MAKE_SDATA(MissileFollowsTerrainHeight, bool, 0x398);
			MAKE_SDATA(DoNotNeedToFaceTarget, bool, 0x399);
			MAKE_SDATA(NoWinddownIfCancelled, bool, 0x39a);
			MAKE_SDATA(IgnoreRangeCheck, bool, 0x39b);
			MAKE_SDATA(OrientRadiusTextureFromPlayer, bool, 0x39c);
			MAKE_SDATA(UseAutoattackCastTime, float, 0x39d);
			MAKE_SDATA(IgnoreAnimContinueUntilCastFrame, bool, 0x39e);
			MAKE_SDATA(HideRangeIndicatorWhenCasting, bool, 0x39f);
			MAKE_SDATA(UpdateRotationWhenCasting, bool, 0x3a0);
			MAKE_SDATA(ConsideredAsAutoAttack, bool, 0x3a1);
			MAKE_SDATA(MinimapIconDisplayFlag, bool, 0x3a2);
			MAKE_SDATA(CastRange, float, 0x3a4);
			MAKE_SDATA(CastRangeDisplayOverride, float, 0x3c0);
			MAKE_SDATA(CastRadius, float, 0x3dc);
			MAKE_SDATA(CastRadiusSecondary, float, 0x3f8);
			MAKE_SDATA(CastConeAngle, float, 0x414);
			MAKE_SDATA(CastConeDistance, float, 0x418);
			MAKE_SDATA(CastTargetAdditionalUnitsRadius, float, 0x41c);
			MAKE_SDATA(BounceRadius, float, 0x420);
			MAKE_SDATA(MissileGravity, float, 0x424);
			MAKE_SDATA(MissileTargetHeightAugment, float, 0x428);
			MAKE_SDATA(LineMissileTargetHeightAugment, float, 0x42c);
			MAKE_SDATA(LineMissileDelayDestroyAtEndSeconds, float, 0x430);
			MAKE_SDATA(LineMissileTimePulseBetweenCollisionSpellHits, float, 0x434);
			MAKE_SDATA(LuaOnMissileUpdateDistanceInterval, float, 0x438);
			MAKE_SDATA(CastType, int, 0x43c);
			MAKE_SDATA(CastFrame, float, 0x440);
			MAKE_SDATA(SpellDamageRatio, float, 0x444);
			MAKE_SDATA(PhysicalDamageRatio, float, 0x448);
			MAKE_SDATA(MissileSpeed, float, 0x44c);
			MAKE_SDATA(MissileAccel, float, 0x450);
			MAKE_SDATA(MissileMaxSpeed, float, 0x454);
			MAKE_SDATA(MissileMinSpeed, float, 0x458);
			MAKE_SDATA(MissileFixedTravelTime, float, 0x45c);
			MAKE_SDATA(MissileLifetime, float, 0x460);
			MAKE_SDATA(MissileEffectName, char*, 0x464);
			MAKE_SDATA(MissileEffectPlayerName, char*, 0x470);
			MAKE_SDATA(MissileEffectEnemyName, char*, 0x47c);
			MAKE_SDATA(MissileBoneName, char*, 0x488);
			MAKE_SDATA(TargetBoneName, char*, 0x494);
			MAKE_SDATA(MissileUnblockable, bool, 0x4a4);
			MAKE_SDATA(MissileBlockTriggersOnDestroy, bool, 0x4a5);
			MAKE_SDATA(MissilePerceptionBubbleRadius, float, 0x4a8);
			MAKE_SDATA(MissilePerceptionBubbleRevealsStealth, bool, 0x4ac);
			MAKE_SDATA(CircleMissileRadialVelocity, float, 0x4b0);
			MAKE_SDATA(CircleMissileAngularVelocity, float, 0x4b4);
			MAKE_SDATA(LineWidth, float, 0x4c8);
			MAKE_SDATA(LineDragLength, float, 0x4cc);
			MAKE_SDATA(LookAtPolicy, int, 0x4d0);
			MAKE_SDATA(HitEffectOrientType, int, 0x4d4);
			MAKE_SDATA(HitBoneName, char*, 0x4d8);
			MAKE_SDATA(HitEffectName, char*, 0x4e4);
			MAKE_SDATA(HitEffectPlayerName, char*, 0x4f0);
			MAKE_SDATA(AfterEffectName, char*, 0x4fc);
			MAKE_SDATA(PointEffectName, char*, 0x508);
			MAKE_SDATA(DeathRecapPriority, float, 0x514);
			MAKE_SDATA(ParticleStartOffset, Vector3f, 0x518);
			MAKE_SDATA(FloatVarsDecimals, float, 0x524);
			MAKE_SDATA(Mana, float, 0x564);
			MAKE_SDATA(ManaUiOverride, bool, 0x57c);
			MAKE_SDATA(SelectionPriority, int, 0x594);
			MAKE_SDATA(TargettingType, byte, 0x598);
			MAKE_SDATA(VOEventCategory, char*, 0x59c);
			MAKE_SDATA(AIData, int, 0x5a8);
			MAKE_SDATA(ClientData, int, 0x5c0);

			// Arrays
			MAKE_SDATA( ManaCostArray, float, 1380 );
			MAKE_SDATA( CastRangeArray, float, 936 );
			MAKE_SDATA( CastRangeDisplayOverrideArray, float, 964 );
			MAKE_SDATA( CastRangeGrowthDurationArray, float, 764 );
			MAKE_SDATA( CastRangeGrowthMaxArray, float, 708 );
			MAKE_SDATA( ChannelDurationArray, float, 636 );
			MAKE_SDATA( CastRadiusArray, float, 992 );
			MAKE_SDATA( CastRadiusSecondaryArray, float, 1020 );
			MAKE_SDATA( AmmoRechargeTimeArray, float, 856 );
			MAKE_SDATA( AmmoUsedArray, int, 1404 );
			MAKE_SDATA( CooldownArray, float, 668 );
			MAKE_SDATA( FloatVarsDecimalsArray, float, 1444 );
			MAKE_SDATA( MaxAmmoArray, int, 1404 );
			MAKE_SDATA( LocationTargettingLengthArray, float, 1576 );
			MAKE_SDATA( LocationTargettingWidthArray, float, 1576 );

			//Manual
			MAKE_SDATA( SpellCastTime, float, 1052 );
			MAKE_SDATA( SpellTotalTime, float, 1052 );
		};

	}
}
