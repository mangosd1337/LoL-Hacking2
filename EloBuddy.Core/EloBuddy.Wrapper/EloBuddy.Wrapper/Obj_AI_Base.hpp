#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/Obj_AI_Base.h"

#ifdef _PBE_BUILD
	#include "../../EloBuddy.Core/EloBuddy.Core/CharIntermediatePBE.h"
#else
	#include "../../EloBuddy.Core/EloBuddy.Core/CharacterIntermediate.h"
#endif

#include "../../EloBuddy.Core/EloBuddy.Core/SpellCastInfo.h"
#include "../../EloBuddy.Core/EloBuddy.Core/BuffInstance.h"
#include "../../EloBuddy.Core/EloBuddy.Core/CharacterDataStack.h"

#include "AttackableUnit.hpp"
#include "StaticEnums.h"
#include "InventorySlot.hpp"
#include "Spellbook.hpp"
#include "SpellData.hpp"
#include "BuffInstance.hpp"
#include "Experience.h"
#include "CharData.h"

#include "ProcessSpellCastEventArgs.hpp"
#include "TeleportEventArgs.hpp"
#include "NewPathEventArgs.hpp"
#include "PlayAnimationEventArgs.hpp"
#include "BuffEventArgs.hpp"
#include "LevelUpEventArgs.h"
#include "UpdateModelEventArgs.hpp"
#include "UpdatePositionEventArgs.hpp"
#include "SurrenderVoteEventArgs.hpp"

using namespace System;
using namespace System::Collections::Specialized;
using namespace System::Collections::Generic;

namespace EloBuddy
{
	ref class BuffInstance;
	ref class GameObject;

	MAKE_EVENT_GLOBAL( Obj_AI_ProcessSpellCast, Obj_AI_Base^ sender, GameObjectProcessSpellCastEventArgs^ args );
	MAKE_EVENT_GLOBAL( Obj_AI_BaseTeleport, Obj_AI_Base^ sender, GameObjectTeleportEventArgs^ args );
	MAKE_EVENT_GLOBAL( Obj_AI_BaseNewPath, Obj_AI_Base^ sender, GameObjectNewPathEventArgs^ args );
	MAKE_EVENT_GLOBAL( Obj_AI_BasePlayAnimation, Obj_AI_Base^ sender, GameObjectPlayAnimationEventArgs^ args );
	MAKE_EVENT_GLOBAL( Obj_AI_BaseBuffGain, Obj_AI_Base^ sender, Obj_AI_BaseBuffGainEventArgs^ args );
	MAKE_EVENT_GLOBAL( Obj_AI_BaseBuffLose, Obj_AI_Base^ sender, Obj_AI_BaseBuffLoseEventArgs^ args );
	MAKE_EVENT_GLOBAL( Obj_AI_BaseBuffUpdate, Obj_AI_Base^ sender, Obj_AI_BaseBuffUpdateEventArgs^ args );
	MAKE_EVENT_GLOBAL( Obj_AI_BaseLevelUp, Obj_AI_Base^ sender, Obj_AI_BaseLevelUpEventArgs^ args );
	MAKE_EVENT_GLOBAL( Obj_AI_UpdateModel, Obj_AI_Base^ sender, UpdateModelEventArgs^ args );
	MAKE_EVENT_GLOBAL( Obj_AI_UpdatePosition, Obj_AI_Base^ sender, Obj_AI_UpdatePositionEventArgs^ args );
	MAKE_EVENT_GLOBAL( Obj_AI_BaseDoCastSpell, Obj_AI_Base^ sender, GameObjectProcessSpellCastEventArgs^ args );
	MAKE_EVENT_GLOBAL( Obj_AI_BaseOnBasicAttack, Obj_AI_Base^ sender, GameObjectProcessSpellCastEventArgs^ args );
	MAKE_EVENT_GLOBAL( Obj_AI_BaseOnSurrenderVote, Obj_AI_Base^ sender, Obj_AI_BaseSurrenderVoteEventArgs^ args );

	public ref class Obj_AI_Base : public AttackableUnit {
	internal:
		MAKE_EVENT_INTERNAL_PROCESS( Obj_AI_ProcessSpellCast, (Native::Obj_AI_Base*, Native::SpellCastInfo* castInfo) );
		MAKE_EVENT_INTERNAL( Obj_AI_BaseTeleport, (Native::Obj_AI_Base*, char*, char*) );
		MAKE_EVENT_INTERNAL( Obj_AI_BaseNewPath, (Native::Obj_AI_Base*, std::vector<Native::Vector3f>*, bool, float) );
		MAKE_EVENT_INTERNAL_PROCESS( Obj_AI_BasePlayAnimation, (Native::Obj_AI_Base*, char**) );
		MAKE_EVENT_INTERNAL( Obj_AI_BaseBuffGain, (Native::Obj_AI_Base*, Native::BuffInstance*) );
		MAKE_EVENT_INTERNAL( Obj_AI_BaseBuffLose, (Native::Obj_AI_Base*, Native::BuffInstance*) );
		MAKE_EVENT_INTERNAL( Obj_AI_BaseBuffUpdate, (Native::Obj_AI_Base*, Native::BuffInstance*) );
		MAKE_EVENT_INTERNAL( Obj_AI_BaseLevelUp, (Native::Obj_AI_Base*, int) );
		MAKE_EVENT_INTERNAL_PROCESS( Obj_AI_UpdateModel, (Native::Obj_AI_Base*, char*, int) );
		MAKE_EVENT_INTERNAL( Obj_AI_UpdatePosition, (Native::Obj_AI_Base*, Native::Vector3f*) );
		MAKE_EVENT_INTERNAL( Obj_AI_BaseDoCastSpell, (Native::Obj_AI_Base*, Native::SpellCastInfo*) );
		MAKE_EVENT_INTERNAL( Obj_AI_BaseOnBasicAttack, (Native::Obj_AI_Base*, Native::SpellCastInfo*) );
		MAKE_EVENT_INTERNAL( Obj_AI_BaseOnSurrenderVote, (Native::Obj_AI_Base*, byte) );

		Native::Obj_AI_Base* GetPtr()
		{
			return reinterpret_cast<Native::Obj_AI_Base*>(GameObject::GetPtr());
		}

		static Dictionary<int, List<BuffInstance^>^>^ cachedBuffs = gcnew Dictionary<int, List<BuffInstance^>^>();
	public:
		MAKE_EVENT_PUBLIC( OnProcessSpellCast, Obj_AI_ProcessSpellCast );
		MAKE_EVENT_PUBLIC( OnTeleport, Obj_AI_BaseTeleport );
		MAKE_EVENT_PUBLIC( OnNewPath, Obj_AI_BaseNewPath );
		MAKE_EVENT_PUBLIC( OnPlayAnimation, Obj_AI_BasePlayAnimation );
		MAKE_EVENT_PUBLIC( OnBuffGain, Obj_AI_BaseBuffGain );
		MAKE_EVENT_PUBLIC( OnBuffLose, Obj_AI_BaseBuffLose );
		MAKE_EVENT_PUBLIC( OnBuffUpdate, Obj_AI_BaseBuffUpdate );
		MAKE_EVENT_PUBLIC( OnLevelUp, Obj_AI_BaseLevelUp );
		MAKE_EVENT_PUBLIC( OnUpdateModel, Obj_AI_UpdateModel );
		MAKE_EVENT_PUBLIC( OnUpdatePosition, Obj_AI_UpdatePosition );
		MAKE_EVENT_PUBLIC( OnSpellCast, Obj_AI_BaseDoCastSpell );
		MAKE_EVENT_PUBLIC( OnBasicAttack, Obj_AI_BaseOnBasicAttack );
		MAKE_EVENT_PUBLIC( OnSurrender, Obj_AI_BaseOnSurrenderVote );

		Obj_AI_Base( ushort index, uint networkId, Native::GameObject* unit ) : AttackableUnit( index, networkId, unit ) {}
		Obj_AI_Base::Obj_AI_Base() {}
		static Obj_AI_Base();
		static void DomainUnloadEventHandler( System::Object^, System::EventArgs^ );

		property List<BuffInstance^>^ Buffs
		{
			List<BuffInstance^>^ get();
		}

		property array<InventorySlot^>^ InventoryItems
		{
			array<InventorySlot^>^ get();
		}

		bool HasBuffOfType( BuffType type );
		bool HasBuff( System::String^ name );
		BuffInstance^ GetBuff( System::String^ name );
		int GetBuffCount( System::String^ buffName );

		array<Vector3>^ GetPath( Vector3 end );
		array<Vector3>^ GetPath( Vector3 start, Vector3 end );
		array<Vector3>^ GetPath( Vector3 end, bool smoothPath );
		array<Vector3>^ GetPath( Vector3 start, Vector3 end, bool smoothPath );

		/*bool IssueOrder( GameObjectOrder order, GameObject^ targetUnit );
		bool IssueOrder( GameObjectOrder order, GameObject^ targetUnit, bool triggerEvent );
		bool IssueOrder( GameObjectOrder order, Vector3 targetPos );
		bool IssueOrder( GameObjectOrder order, Vector3 targetPos, bool triggerEvent );*/

		void SetSkinId( int skinId );
		bool SetModel( String^ model );
		bool SetSkin( String^ model, int skinId );

		property bool IsMoving
		{
			bool get();
		}

		property String^ Model
		{
			String^ get()
			{
				auto ptr = this->GetPtr();
				if (ptr != nullptr)
				{
					auto charStack = ptr->GetCharacterDataStack();
					if (charStack != nullptr)
					{
						return gcnew String( charStack->GetActiveModel()->c_str() );
					}
				}

				return "Unknown";
			}
		}

		property int SkinId
		{
			int get()
			{
				auto ptr = this->GetPtr();
				{
					auto charStack = ptr->GetCharacterDataStack();
					if (charStack != nullptr)
					{
						return *charStack->GetActiveSkinId();
					}
				}
				return -1;
			}
		}

		bool Capture()
		{
			auto pPlayer = Native::ObjectManager::GetPlayer();
			if (pPlayer != nullptr)
			{
				Native::ObjectManager::GetPlayer()->Capture( this->GetPtr() );
			}

			return false;
		}

		property Spellbook^ Spellbook
		{
			EloBuddy::Spellbook^ get();
		}

		property Vector3 Direction
		{
			Vector3 get()
			{
				auto ptr = this->GetPtr();

				if (ptr != nullptr)
				{
					auto vec = ptr->GetDirection();
					if (vec != nullptr)
					{
						return Vector3( vec->GetX(), vec->GetZ(), vec->GetY() );
					}
				}

				return Vector3::Zero;
			}
		}

		property String^ BaseSkinName
		{
			String^ get()
			{
				auto ptr = this->GetPtr();

				if (ptr != nullptr)
				{
					return gcnew String( ptr->GetSkinName()->c_str() );
				}

				return "Unknown";
			}
		}

		property Vector2 HPBarPosition
		{
			Vector2 get();
		}

		property float HPBarXOffset
		{
			float get();
			void set( float value );
		}

		property float HPBarYOffset
		{
			float get();
			void set( float value );
		}

		property GameObjectCombatType CombatType
		{
			GameObjectCombatType get()
			{
				auto ptr = this->GetPtr();
				if (ptr != nullptr)
				{
					return static_cast<GameObjectCombatType>(*ptr->GetCombatType());
				}
				return GameObjectCombatType::Melee;
			}
		}

		property SharpDX::Vector3 ServerPosition
		{
			SharpDX::Vector3 get();
		}

		property bool IsMinion
		{
			bool get()
			{
				auto self = this->GetPtr();
				if (self != nullptr)
				{
					auto vt = self->GetVirtual();
					if (vt != nullptr)
					{
						return vt->IsMinion();
					}
				}
				return false;
			}
		}

		property bool IsHPBarRendered
		{
			bool get()
			{
				auto self = this->GetPtr();
				if (self != nullptr)
				{
					auto vt = self->GetVirtual();
					if (vt != nullptr)
					{
						return vt->IsVisible();
					}
				}
				return false;
			}
		}

		property bool IsMonster
		{
			bool get()
			{
				return Team == GameObjectTeam::Neutral;
			}
		}

		property EloBuddy::CharData^ CharData
		{
			EloBuddy::CharData^ get()
			{
				auto ptr = this->GetPtr();
				if (ptr != nullptr)
				{
					auto charData = *ptr->GetCharData();
					if (charData != nullptr)
					{
						auto charInfoStruct = charData->GetCharDataInfo();
						if (charInfoStruct != nullptr)
						{
							return gcnew EloBuddy::CharData( charInfoStruct );
						}
					}
				}
				return nullptr;
			}
		}

		property BitVector32 Flags
		{
			BitVector32 get()
			{
				return BitVector32( *this->GetPtr()->GetCharacterActionState() );
			}
		}

		property float PercentDamageToBarracksMinionMod
		{
			float get()
			{
				auto ptr = this->GetPtr();
				if (ptr != nullptr)
				{
					return *ptr->GetPercentDamageToBarracksMinionMod();
				}
				return 0;
			}
		}

		property float FlatDamageReductionFromBarracksMinionMod
		{
			float get()
			{
				auto ptr = this->GetPtr();
				if (ptr != nullptr)
				{
					return *ptr->GetFlatDamageReductionFromBarracksMinionMod();
				}
				return 0;
			}
		}

		//CharacterStates
		MAKE_PROPERTY( CharacterState, GameObjectCharacterState );
		MAKE_PROPERTY( IsCallForHelpSuppresser, bool );
		MAKE_PROPERTY( IsSuppressCallForHelp, bool );
		MAKE_PROPERTY( IsIgnoreCallForHelp, bool );
		MAKE_PROPERTY( IsForceRenderParticles, bool );
		MAKE_PROPERTY( IsFleeing, bool );
		MAKE_PROPERTY( IsNoRender, bool );
		MAKE_PROPERTY( IsGhosted, bool );
		MAKE_PROPERTY( IsNearSight, bool );
		MAKE_PROPERTY( IsAsleep, bool );
		MAKE_PROPERTY( IsFeared, bool );
		MAKE_PROPERTY( IsCharmed, bool );
		MAKE_PROPERTY( IsTaunted, bool );
		MAKE_PROPERTY( IsRooted, bool );
		MAKE_PROPERTY( IsStunned, bool );
		MAKE_PROPERTY( IsPacified, bool );
		MAKE_PROPERTY( IsRevealSpecificUnit, bool );
		MAKE_PROPERTY( IsStealthed, bool );
		MAKE_PROPERTY( CanMove, bool );
		MAKE_PROPERTY( CanCast, bool );
		MAKE_PROPERTY( CanAttack, bool );

		MAKE_PROPERTY( BasicAttack, SpellData^ );
		MAKE_PROPERTY( Path, array<Vector3>^ );
		MAKE_PROPERTY( InfoComponentBasePosition, Vector3 );
		MAKE_PROPERTY( Pet, GameObject^ );
		MAKE_PROPERTY( TotalAttackDamage, float );
		MAKE_PROPERTY( TotalMagicalDamage, float );
		MAKE_PROPERTY( DeathDuration, float );

		CREATE_GET_G( AttackCastDelay, float );
		CREATE_GET_G( AttackDelay, float );
		CREATE_GET( AutoAttackTargettingFlags, int );
		CREATE_GET( EvolvePoints, int );
		CREATE_GET( ExpGiveRadius, float );
		CREATE_GET( Gold, float );
		CREATE_GET( GoldTotal, float );

		CREATE_GET( PlayerControlled, bool );
		//MAKE_C_STRING( ResourceName );

		//CharacterIntermediates
		CREATE_CHARACTER_INTERMEDIATE( FlatCooldownMod );
		CREATE_CHARACTER_INTERMEDIATE( PassiveCooldownEndTime );
		CREATE_CHARACTER_INTERMEDIATE( PassiveCooldownTotalTime );
		CREATE_CHARACTER_INTERMEDIATE( PercentCooldownMod );
		CREATE_CHARACTER_INTERMEDIATE( FlatHPPoolMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentHPPoolMod );
		CREATE_CHARACTER_INTERMEDIATE( FlatPARPoolMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentPARPoolMod );
		CREATE_CHARACTER_INTERMEDIATE( FlatHPRegenMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentHPRegenMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentBaseHPRegenMod );
		CREATE_CHARACTER_INTERMEDIATE( FlatPARRegenMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentPARRegenMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentTenacityCleanseMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentTenacityCharacterMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentTenacityItemMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentTenacityMasteryMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentTenacityRuneMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentCCReduction );
		CREATE_CHARACTER_INTERMEDIATE( PercentSlowResistMod );
		CREATE_CHARACTER_INTERMEDIATE( FlatMovementSpeedHasteMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentMovementSpeedHasteMod );
		CREATE_CHARACTER_INTERMEDIATE( FlatMovementSpeedSlowMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentMovementSpeedSlowMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentMultiplicativeMovementSpeedMod );
		CREATE_CHARACTER_INTERMEDIATE( MoveSpeedFloorMod );
		CREATE_CHARACTER_INTERMEDIATE( FlatArmorMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentArmorMod );
		CREATE_CHARACTER_INTERMEDIATE( FlatArmorPenetrationMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentArmorPenetrationMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentBonusArmorPenetrationMod );
		CREATE_CHARACTER_INTERMEDIATE( FlatMagicPenetrationMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentMagicPenetrationMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentBonusMagicPenetrationMod );
		CREATE_CHARACTER_INTERMEDIATE( FlatSpellBlockMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentSpellBlockMod );
		CREATE_CHARACTER_INTERMEDIATE( FlatMissChanceMod );
		CREATE_CHARACTER_INTERMEDIATE( FlatDodgeMod );
		CREATE_CHARACTER_INTERMEDIATE( FlatCritChanceMod );
		CREATE_CHARACTER_INTERMEDIATE( FlatCritDamageMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentCritDamageMod );
		CREATE_CHARACTER_INTERMEDIATE( FlatPhysicalDamageMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentPhysicalDamageMod );
		CREATE_CHARACTER_INTERMEDIATE( FlatMagicDamageMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentMagicDamageMod );
		CREATE_CHARACTER_INTERMEDIATE( FlatPhysicalReduction );
		CREATE_CHARACTER_INTERMEDIATE( PercentPhysicalReduction );
		CREATE_CHARACTER_INTERMEDIATE( FlatMagicReduction );
		CREATE_CHARACTER_INTERMEDIATE( PercentMagicReduction );
		CREATE_CHARACTER_INTERMEDIATE( PercentEXPBonus );
		CREATE_CHARACTER_INTERMEDIATE( FlatAttackRangeMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentAttackRangeMod );
		CREATE_CHARACTER_INTERMEDIATE( FlatCastRangeMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentCastRangeMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentAttackSpeedMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentMultiplicativeAttackSpeedMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentHealingAmountMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentLifeStealMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentSpellVampMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentRespawnTimeMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentGoldLostOnDeathMod );
		CREATE_CHARACTER_INTERMEDIATE( AttackSpeedMod );
		CREATE_CHARACTER_INTERMEDIATE( BaseAttackDamage );
		CREATE_CHARACTER_INTERMEDIATE( BaseAbilityDamage );
		CREATE_CHARACTER_INTERMEDIATE( CritDamageMultiplier );
		CREATE_CHARACTER_INTERMEDIATE( ScaleSkinCoef );
		CREATE_CHARACTER_INTERMEDIATE( MissChance );
		CREATE_CHARACTER_INTERMEDIATE( Dodge );
		CREATE_CHARACTER_INTERMEDIATE( Crit );
		CREATE_CHARACTER_INTERMEDIATE( Armor );
		CREATE_CHARACTER_INTERMEDIATE( SpellBlock );
		CREATE_CHARACTER_INTERMEDIATE( HPRegenRate );
		CREATE_CHARACTER_INTERMEDIATE( BasePARRegenRate );
		CREATE_CHARACTER_INTERMEDIATE( PercentBasePARRegenMod );
		CREATE_CHARACTER_INTERMEDIATE( PARRegenRate );
		CREATE_CHARACTER_INTERMEDIATE( MoveSpeed );
		CREATE_CHARACTER_INTERMEDIATE( AttackRange );
		CREATE_CHARACTER_INTERMEDIATE( CastRange );
		CREATE_CHARACTER_INTERMEDIATE( FlatBubbleRadiusMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentBubbleRadiusMod );
		CREATE_CHARACTER_INTERMEDIATE( _FlatHPModPerLevel );
		CREATE_CHARACTER_INTERMEDIATE( _FlatMPModPerLevel );
		CREATE_CHARACTER_INTERMEDIATE( _FlatArmorModPerLevel );
		CREATE_CHARACTER_INTERMEDIATE( _FlatSpellBlockModPerLevel );
		CREATE_CHARACTER_INTERMEDIATE( _FlatHPRegenModPerLevel );
		CREATE_CHARACTER_INTERMEDIATE( _FlatMPRegenModPerLevel );
		CREATE_CHARACTER_INTERMEDIATE( _FlatPhysicalDamageModPerLevel );
		CREATE_CHARACTER_INTERMEDIATE( _FlatMagicDamageModPerLevel );
		CREATE_CHARACTER_INTERMEDIATE( _FlatMovementSpeedModPerLevel );
		CREATE_CHARACTER_INTERMEDIATE( _PercentMovementSpeedModPerLevel );
		CREATE_CHARACTER_INTERMEDIATE( _PercentAttackSpeedModPerLevel );
		CREATE_CHARACTER_INTERMEDIATE( _FlatCritChanceModPerLevel );
		CREATE_CHARACTER_INTERMEDIATE( _FlatCritDamageModPerLevel );
		CREATE_CHARACTER_INTERMEDIATE( _FlatDodgeMod );
		CREATE_CHARACTER_INTERMEDIATE( _FlatDodgeModPerLevel );
		CREATE_CHARACTER_INTERMEDIATE( _FlatArmorPenetrationMod );
		CREATE_CHARACTER_INTERMEDIATE( _FlatArmorPenetrationModPerLevel );
		CREATE_CHARACTER_INTERMEDIATE( _PercentArmorPenetrationMod );
		CREATE_CHARACTER_INTERMEDIATE( _PercentArmorPenetrationModPerLevel );
		CREATE_CHARACTER_INTERMEDIATE( _PercentCooldownMod );
		CREATE_CHARACTER_INTERMEDIATE( _PercentCooldownModPerLevel );
		CREATE_CHARACTER_INTERMEDIATE( _FlatTimeDeadMod );
		CREATE_CHARACTER_INTERMEDIATE( _FlatTimeDeadModPerLevel );
		CREATE_CHARACTER_INTERMEDIATE( _PercentTimeDeadMod );
		CREATE_CHARACTER_INTERMEDIATE( _PercentTimeDeadModPerLevel );
		CREATE_CHARACTER_INTERMEDIATE( FlatGoldPer10Mod );
		CREATE_CHARACTER_INTERMEDIATE( _FlatMagicPenetrationMod );
		CREATE_CHARACTER_INTERMEDIATE( _FlatMagicPenetrationModPerLevel );
		CREATE_CHARACTER_INTERMEDIATE( _PercentMagicPenetrationMod );
		CREATE_CHARACTER_INTERMEDIATE( _PercentMagicPenetrationModPerLevel );
		CREATE_CHARACTER_INTERMEDIATE( _NonHealingFlatHPPoolMod );
		CREATE_CHARACTER_INTERMEDIATE( AcquisitionRangeMod );
		CREATE_CHARACTER_INTERMEDIATE( PathfindingRadiusMod );
		CREATE_CHARACTER_INTERMEDIATE( FlatExpRewardMod );
		CREATE_CHARACTER_INTERMEDIATE( FlatGoldRewardMod );
		CREATE_CHARACTER_INTERMEDIATE( PercentLocalGoldRewardMod );

		//------------ Obsolete Properties
		[Obsolete( "This property will be removed as it's not used in the client." )]
		property float PetReturnRadius
		{
			float get() { return 0;  }
		}

		[Obsolete( "This property will be removed as it's not used in the client." )]
		property float SpellCastBlockingAI
		{
			float get() { return 0; }
		}

		[Obsolete( "This property will be removed as it's not used in the client." )]
		property int AI_LastPetSpawnedId
		{
			int get() { return 0; }
		}

		[Obsolete( "This property will be removed as it's not used in the client." )]
		property Vector3 LastPausePosition
		{
			Vector3 get()
			{
				return Vector3::Zero;
			}
		}

		[Obsolete( "This property will be removed as it's not used in the client." )]
		property Vector3 FearLeashPoint
		{
			Vector3 get()
			{
				return Vector3::Zero;
			}
		}
	};
}