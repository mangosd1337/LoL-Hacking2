#pragma once

#include "stdafx.h"
#include "AttackableUnit.h"
#include "StaticEnums.h"
#include "Vector3f.h"
#ifdef _PBE_BUILD
	#include "CharIntermediatePBE.h"
#else
	#include "CharacterIntermediate.h"
#endif
#include "Detour.hpp"
#include "SpellCastInfo.h"
#include "SpellData.h"
#include "Spellbook.h"
#include "RiotAsset.h"
#include "pwConsole.h"
#include <utility>
#include "NavigationPath.h"

namespace EloBuddy
{
	namespace Native
	{
		class BuffInstance;
		class Spellbook;
		class BuffManager;
		class HeroInventory;
		class Actor_Common;
		class CharacterDataStack;
		class CharDataInfo;

		class
			DLLEXPORT UnitInfoHealthBar
		{
		public:
			MAKE_GET_SET( XOffset, float, 0x4 );
			MAKE_GET_SET( YOffset, float, 0x8 );
		};

		class RecallStruct
		{
		public:
			MAKE_GET( RecallType, char, -0x18 ); //OdinRecall
			MAKE_GET( RecallName, char, 0x0 ); //Recall
		};

		class
			DLLEXPORT UnitInfoComponent
		{
		public:
			Vector3f* GetBaseDrawPosition()
			{
				auto vecOut = new Vector3f( 0, 0, 0 );

				if (this != nullptr)
				{
					reinterpret_cast<int( __thiscall* )(void*, Vector3f*)>
						MAKE_RVA( Offsets::GameObjectFunctions::GetBaseDrawPosition )
						(this, vecOut);
				}

				return vecOut;
			}

			Vector3f GetHPBarPosition();
			MAKE_GET( Healthbar, UnitInfoHealthBar*, 0xC );
		};

		class
			DLLEXPORT AIManager_Client
		{
		public:
			Actor_Common* GetActor()
			{
				return reinterpret_cast<Actor_Common*>(this + static_cast<int>(Offsets::Obj_AIBase::Actor_Common));
			}

			NavigationPath* GetNavPath()
			{
				return reinterpret_cast<NavigationPath*>(this + static_cast<int>(Offsets::ActorCommonStruct::AINavPath));
			}
		};

		class
			DLLEXPORT CharData
		{
		public:
			CharDataInfo* GetCharDataInfo();
		};

		class
			DLLEXPORT Obj_AI_Base : public AttackableUnit
		{
		public:
			static bool ApplyHooks();

			bool IssueOrder( Vector3f* pos, GameObject*, GameObjectOrder order, bool triggerEvent );

			bool Capture( Obj_AI_Base* object );
			bool UseObject( Obj_AI_Base* object );

			SpellData* GetBasicAttack()
			{
				return
					*reinterpret_cast <SpellData**(__thiscall*)(void*, int)>
					(MAKE_RVA(Offsets::SpellHelper::GetBasicAttack))
					(this, 64);
			}

			float GetAttackCastDelay()
			{
				__try
				{
					auto returnValue = 0.0f;

					reinterpret_cast<float( __fastcall* )(void*, int)>
						(MAKE_RVA( Offsets::SpellHelper::ComputeCharacterAttackCastDelay ))
						(this, 64);

					__asm
					{
						movss returnValue, xmm2
					}

					return returnValue;
				}
				__except (1) { return 0; }
			}

			float GetAttackDelay()
			{
				__try
				{
					auto returnValue = 0.0f;

					reinterpret_cast<float( __fastcall* )(void*, int)>
						(MAKE_RVA( Offsets::SpellHelper::ComputeCharacterAttackDelay ))
						(this, 64);

					__asm
					{
						movss returnValue, xmm0
					}

					return returnValue;
				}
				__except (1) { return 0; }
			}

			bool GetIsHPBarBeingDrawn();
			 
			CharacterDataStack* GetCharacterDataStack()
			{
				return reinterpret_cast<CharacterDataStack*>(reinterpret_cast<DWORD>(this) + static_cast<int>(Offsets::Obj_AIBase::CharacterDataStack));
			}

			//MAKE_GET( AI_LastPetSpawnedID, int, Offsets::Obj_AIBase::AI_LastPetSpawnedID );
			MAKE_GET( AIManager_Client, AIManager_Client*, Offsets::Obj_AIBase::AIManager );
			MAKE_GET( AutoAttackTargettingFlags, int, Offsets::Obj_AIBase::AutoAttackTargettingFlags );
			MAKE_GET( BuffManager, BuffManager, Offsets::BuffManager::BuffManagerInst );
			MAKE_GET( CharacterActionState, int, Offsets::Obj_AIBase::CharacterActionState );
			MAKE_GET( CharacterIntermediate, CharacterIntermediate, Offsets::Obj_AIBase::CharacterIntermediate );
			MAKE_GET( CharacterState, int, Offsets::Obj_AIBase::CharacterState );
			MAKE_GET( CombatType, uint, Offsets::Obj_AIBase::CombatType );
			//MAKE_GET( DeathDuration, float, Offsets::Obj_AIBase::DeathDuration );
			MAKE_GET( EvolvePoints, int, Offsets::Obj_AIBase::EvolvePoints );
			MAKE_GET( ExpGiveRadius, float, Offsets::Obj_AIBase::ExpGiveRadius );
			//MAKE_GET( FearLeashPoint, Vector3f, Offsets::Obj_AIBase::FearLeashPoint );
			MAKE_GET( Gold, float, Offsets::Obj_AIBase::Gold );
			MAKE_GET( GoldTotal, float, Offsets::Obj_AIBase::GoldTotal );
			MAKE_GET( Inventory, HeroInventory, Offsets::HeroInventory::Inventory );
			MAKE_GET( InfoComponent, UnitInfoComponent*, Offsets::UnitInfoComponent::InfoComponent );
			//MAKE_GET( PetReturnRadius, float, Offsets::Obj_AIBase::PetReturnRadius );
			MAKE_GET( PlayerControlled, bool, Offsets::Obj_AIBase::PlayerControlled );
			MAKE_GET( SkinName, std::string, Offsets::Obj_AIBase::SkinName );
			MAKE_GET( Spellbook, Spellbook, Offsets::Spellbook::SpellbookInst );
			//MAKE_GET( SpellCastBlockingAI, bool, Offsets::Obj_AIBase::SpellCastBlockingAI );
			MAKE_GET( UserComponent, int, Offsets::Obj_AIBase::UserComponent );
			MAKE_GET( ResourceName, char, Offsets::Obj_AIBase::ResourceName );
			MAKE_GET( CharData, CharData*, Offsets::Obj_AIBase::CharData );
			MAKE_GET(PercentDamageToBarracksMinionMod, float, Offsets::Obj_AIBase::mPercentDamageToBarracksMinionMod);
			MAKE_GET(FlatDamageReductionFromBarracksMinionMod, float, Offsets::Obj_AIBase::mFlatDamageReductionFromBarracks);

			static void ExportFunctions();
		};
	}
}
