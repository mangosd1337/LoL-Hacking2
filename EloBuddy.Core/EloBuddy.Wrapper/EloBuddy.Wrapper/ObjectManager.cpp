#include "stdafx.h"

#include "../../EloBuddy.Core/EloBuddy.Core/ObjectManager.h"
#include "AIHeroClient.hpp"
#include "ObjectManager.hpp"
#include "AllUnits.h"

#define NATIVE_TO_MANAGED(INDEX, TYPE) case((Native::UnitType) static_cast<int>(INDEX)): return TYPE::typeid;
#define OBJECT_FROM_POINTER(UNIT_TYPE, OBJECT_TYPE, POINTER) case UNIT_TYPE:\
	{\
		auto unit = EloBuddy::ObjectManager::cachedObjects->Find(gcnew Predicate<GameObject^>(static_cast<IntPtr>(POINTER), &ObjectManager::EntityPredicate::IsMatch));\
		if (unit != nullptr)\
		{\
			return unit;\
		}\
	}\
	return gcnew OBJECT_TYPE(*POINTER->GetIndex(), *POINTER->GetNetworkId(), POINTER);

namespace EloBuddy
{
	static ObjectManager::ObjectManager()
	{
		ATTACH_DOMAIN();
		ATTACH_EVENT
		(
			ObjectManagerAppendObject,
			60, Native::OnObjectStackLoad, Native::GameObject*, char**, int*, int*, Native::Vector3f**
		);

		GameObject::OnCreate += gcnew GameObjectCreate( &ObjectManager::OnObjectCreate );
		GameObject::OnDelete += gcnew GameObjectDelete( &ObjectManager::OnObjectDelete );
		
		// check for spectator mode
		if (ObjectManager::Player != nullptr)
		{
			RefreshCache();
		}
	}

	void ObjectManager::DomainUnloadEventHandler( System::Object^, System::EventArgs^ )
	{
		DETACH_EVENT
		(
			ObjectManagerAppendObject,
			60, Native::OnObjectStackLoad, Native::GameObject*, char**, int*, int*, Native::Vector3f**
		);
	}

	void ObjectManager::OnObjectManagerAppendObjectNative( Native::GameObject* sender, char** model, int* skinId, int* chromaId, Native::Vector3f** position )
	{
		//START_TRACE
		//	auto args = gcnew OnCreateObjectEventArgs( nullptr, model, skinId, chromaId, position );

		//	for each (auto eventHandle in ObjectManagerAppendObjectHandlers->ToArray())
		//	{
		//		START_TRACE
		//			eventHandle( nullptr, args );
		//		END_TRACE
		//	}
		//END_TRACE
	}

	AIHeroClient^ ObjectManager::Player::get()
	{
		if (m_cachedPlayer == nullptr)
		{
			auto pPlayer = Native::ObjectManager::GetPlayer();
			if (pPlayer != nullptr)
			{
				m_cachedPlayer = gcnew AIHeroClient( *pPlayer->GetIndex(), *pPlayer->GetNetworkId(), pPlayer );
			}
		}

		return m_cachedPlayer;
	}

	void ObjectManager::OnObjectCreate( GameObject ^sender, EventArgs ^args )
	{
		OnObjectDelete( sender, args );
		cachedObjects->Add( sender );
	}

	void ObjectManager::OnObjectDelete( GameObject ^sender, EventArgs ^args )
	{
		for each (auto obj in cachedObjects->ToArray())
		{
			if (obj->MemoryAddress == sender->MemoryAddress)
			{
				cachedObjects->Remove( obj );
			}
		}
	}

	void ObjectManager::RefreshCache()
	{
		if (!cacheCreated && Game::Mode == GameMode::Running)
		{
			cacheCreated = true;

			if (Player != nullptr)
			{
				cachedObjects->Add( Player );
			}

			auto nativeObjects = Native::ObjectManager::GetUnitArray();
			for (uint i = 0; i < 10000; i++)
			{
				auto unit = nativeObjects [i];
				if (unit != nullptr)
				{
					auto managedObject = ObjectManager::CreateObjectFromPointer( unit );
					if (managedObject != nullptr)
					{
						if (cachedObjects->Count == 0)
						{
							cachedObjects->Add( managedObject );
						}
						else
						{
							auto memoryAddress = managedObject->MemoryAddress;
							int i = 0;
							do
							{
								if (memoryAddress == cachedObjects [i]->MemoryAddress)
								{
									break;
								}

								i++;
								if (i == cachedObjects->Count)
								{
									cachedObjects->Add( managedObject );
								}
							} while (i < cachedObjects->Count);
						}
					}
				}
			}
		}
	}

	GameObject^ ObjectManager::GetUnitByNetworkId( uint networkId )
	{
		return CreateObjectFromPointer( Native::ObjectManager::GetUnitByNetworkId( networkId ) );
	}

	GameObject^ ObjectManager::GetUnitByIndex( ushort index )
	{
		return CreateObjectFromPointer( Native::ObjectManager::GetUnitByIndex( index ) );
	}

	Type^ ObjectManager::NativeTypeToManagedType( Native::UnitType type )
	{
		switch (type)
		{
			NATIVE_TO_MANAGED( Native::UnitType::NeutralMinionCamp, NeutralMinionCamp );
			NATIVE_TO_MANAGED( Native::UnitType::obj_AI_Base, Obj_AI_Base );
			NATIVE_TO_MANAGED( Native::UnitType::FollowerObject, FollowerObject );
			NATIVE_TO_MANAGED( Native::UnitType::FollowerObjectWithLerpMovement, FollowerObjectWithLerpMovement );
			NATIVE_TO_MANAGED( Native::UnitType::AIHeroClient, AIHeroClient );
			NATIVE_TO_MANAGED( Native::UnitType::obj_AI_Marker, Obj_AI_Marker );
			NATIVE_TO_MANAGED( Native::UnitType::obj_AI_Minion, Obj_AI_Minion );
			NATIVE_TO_MANAGED( Native::UnitType::LevelPropAI, LevelPropAI );
			NATIVE_TO_MANAGED( Native::UnitType::obj_AI_Turret, Obj_AI_Turret );
			NATIVE_TO_MANAGED( Native::UnitType::obj_GeneralParticleEmitter, Obj_GeneralParticleEmitter );
			NATIVE_TO_MANAGED( Native::UnitType::MissileClient, MissileClient );
			NATIVE_TO_MANAGED( Native::UnitType::DrawFX, DrawFX );
			NATIVE_TO_MANAGED( Native::UnitType::UnrevealedTarget, UnrevealedTarget );
			NATIVE_TO_MANAGED( Native::UnitType::obj_LampBulb, Obj_LampBulb );
			NATIVE_TO_MANAGED( Native::UnitType::obj_Barracks, Obj_Barracks );
			NATIVE_TO_MANAGED( Native::UnitType::obj_BarracksDampener, Obj_BarracksDampener );
			NATIVE_TO_MANAGED( Native::UnitType::obj_AnimatedBuilding, Obj_AnimatedBuilding );
			NATIVE_TO_MANAGED( Native::UnitType::obj_Building, Obj_Building );
			NATIVE_TO_MANAGED( Native::UnitType::obj_Levelsizer, Obj_Levelsizer );
			NATIVE_TO_MANAGED( Native::UnitType::obj_NavPoint, Obj_NavPoint );
			NATIVE_TO_MANAGED( Native::UnitType::obj_SpawnPoint, Obj_SpawnPoint );
			NATIVE_TO_MANAGED( Native::UnitType::obj_Lake, Obj_Lake );
			NATIVE_TO_MANAGED( Native::UnitType::obj_HQ, Obj_HQ );
			NATIVE_TO_MANAGED( Native::UnitType::obj_InfoPoint, Obj_InfoPoint );
			NATIVE_TO_MANAGED( Native::UnitType::LevelPropGameObject, LevelPropGameObject );
			NATIVE_TO_MANAGED( Native::UnitType::LevelPropSpawnerPoint, LevelPropSpawnerPoint );
			NATIVE_TO_MANAGED( Native::UnitType::obj_Shop, Obj_Shop );
			NATIVE_TO_MANAGED( Native::UnitType::obj_Turret, Obj_Turret );
			NATIVE_TO_MANAGED( Native::UnitType::GrassObject, GrassObject );
			NATIVE_TO_MANAGED( Native::UnitType::obj_Ward, Obj_Ward );

		default:
			return nullptr;
		}
	}

	GameObject^ ObjectManager::CreateObjectFromPointer( Native::GameObject* obj )
	{
		if (obj == nullptr || (obj->GetIndex() == nullptr && obj->GetNetworkId() == nullptr))
		{
			return nullptr;
		}

		switch (obj->GetType())
		{
			OBJECT_FROM_POINTER( Native::UnitType::NeutralMinionCamp, NeutralMinionCamp, obj );
			OBJECT_FROM_POINTER( Native::UnitType::obj_AI_Base, Obj_AI_Base, obj );
			OBJECT_FROM_POINTER( Native::UnitType::FollowerObject, FollowerObject, obj );
			OBJECT_FROM_POINTER( Native::UnitType::FollowerObjectWithLerpMovement, FollowerObjectWithLerpMovement, obj );
			OBJECT_FROM_POINTER( Native::UnitType::AIHeroClient, AIHeroClient, obj );
			OBJECT_FROM_POINTER( Native::UnitType::obj_AI_Marker, Obj_AI_Marker, obj );
			OBJECT_FROM_POINTER( Native::UnitType::obj_AI_Minion, Obj_AI_Minion, obj );
			OBJECT_FROM_POINTER( Native::UnitType::LevelPropAI, LevelPropAI, obj );
			OBJECT_FROM_POINTER( Native::UnitType::obj_AI_Turret, Obj_AI_Turret, obj );
			OBJECT_FROM_POINTER( Native::UnitType::obj_GeneralParticleEmitter, Obj_GeneralParticleEmitter, obj );
			OBJECT_FROM_POINTER( Native::UnitType::MissileClient, MissileClient, obj );
			OBJECT_FROM_POINTER( Native::UnitType::DrawFX, DrawFX, obj );
			OBJECT_FROM_POINTER( Native::UnitType::UnrevealedTarget, UnrevealedTarget, obj );
			OBJECT_FROM_POINTER( Native::UnitType::obj_LampBulb, Obj_LampBulb, obj );
			OBJECT_FROM_POINTER( Native::UnitType::obj_Barracks, Obj_Barracks, obj );
			OBJECT_FROM_POINTER( Native::UnitType::obj_BarracksDampener, Obj_BarracksDampener, obj );
			OBJECT_FROM_POINTER( Native::UnitType::obj_AnimatedBuilding, Obj_AnimatedBuilding, obj );
			OBJECT_FROM_POINTER( Native::UnitType::obj_Building, Obj_Building, obj );
			OBJECT_FROM_POINTER( Native::UnitType::obj_Levelsizer, Obj_Levelsizer, obj );
			OBJECT_FROM_POINTER( Native::UnitType::obj_NavPoint, Obj_NavPoint, obj );
			OBJECT_FROM_POINTER( Native::UnitType::obj_SpawnPoint, Obj_SpawnPoint, obj );
			OBJECT_FROM_POINTER( Native::UnitType::obj_Lake, Obj_Lake, obj );
			OBJECT_FROM_POINTER( Native::UnitType::obj_HQ, Obj_HQ, obj );
			OBJECT_FROM_POINTER( Native::UnitType::obj_InfoPoint, Obj_InfoPoint, obj );
			OBJECT_FROM_POINTER( Native::UnitType::LevelPropGameObject, LevelPropGameObject, obj );
			OBJECT_FROM_POINTER( Native::UnitType::LevelPropSpawnerPoint, LevelPropSpawnerPoint, obj );
			OBJECT_FROM_POINTER( Native::UnitType::obj_Shop, Obj_Shop, obj );
			OBJECT_FROM_POINTER( Native::UnitType::obj_Turret, Obj_Turret, obj );
			OBJECT_FROM_POINTER( Native::UnitType::GrassObject, GrassObject, obj );
			OBJECT_FROM_POINTER( Native::UnitType::obj_Ward, Obj_Ward, obj );

		default:
			return gcnew GameObject( *obj->GetIndex(), *obj->GetNetworkId(), obj );
		}
	}
}
