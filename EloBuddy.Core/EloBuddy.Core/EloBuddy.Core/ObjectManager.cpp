#include "stdafx.h"
#include "StaticEnums.h"
#include <vector>
#include "Detour.hpp"
#include "EventHandler.h"
#include "Utils.h"
#include "AIHeroClient.h"
#include "ObjectManager.h"
#include "Patchables.h"

namespace EloBuddy
{
	namespace Native
	{
		MAKE_HOOK<convention_type::stdcall_t, void, GameObject*> ObjectManager_CreateObject;
		MAKE_HOOK<convention_type::stdcall_t, int, int> ObjectManager_AssignNetworkId;
		MAKE_HOOK<convention_type::stdcall_t, int, GameObject*> ObjectManager_DestroyObject;
		MAKE_HOOK<convention_type::cdecl_t, int, char*, int, int, int, Vector3f*, int> SpawnObject;

		bool ObjectManager::ApplyHooks()
		{
			ObjectManager_AssignNetworkId.Apply( MAKE_RVA( Offsets::ObjectManager::AssignNetworkId ), [] ( int unkn1 ) -> int
			{
				GameObject* object = nullptr;
				__asm mov object, ecx;
				
				auto returnValue = ObjectManager_AssignNetworkId.CallOriginal( unkn1 );

				if (object != nullptr)
				{
					EventHandler<13, OnGameObjectCreate, GameObject*>::GetInstance()->Trigger( object );
				}

				return returnValue;
			} );

			ObjectManager_DestroyObject.Apply( MAKE_RVA( Offsets::ObjectManager::DestroyObject ), [] ( GameObject* object ) -> int
			{
				if (object != nullptr)
				{
					EventHandler<24, OnGameObjectDelete, GameObject*>::GetInstance()->Trigger( object );
				}

				return ObjectManager_DestroyObject.CallOriginal( object );
			} );

			return ObjectManager_AssignNetworkId.IsApplied()
				&& ObjectManager_DestroyObject.IsApplied();
		}

		AIHeroClient* ObjectManager::GetPlayer()
		{
			return *reinterpret_cast<AIHeroClient**>(Patchables::g_localPlayer);
		}

		uint ObjectManager::GetMaxSize()
		{
			return *reinterpret_cast<int*>(Patchables::g_objectManagerMaxSize);
		}

		uint ObjectManager::GetUsedIndexes()
		{
			return *reinterpret_cast<uint*>(Patchables::g_objectManagerUsedIndexes);
		}

		uint ObjectManager::GetHighestObjectId()
		{
			return *reinterpret_cast<uint*>(MAKE_RVA( Offsets::ObjectManager::HighestObjectId ));
		}

		uint ObjectManager::GetHighestPlayerId()
		{
			return *reinterpret_cast<uint*>(MAKE_RVA( Offsets::ObjectManager::HighestPlayerObjectId ));
		}

		GameObject** ObjectManager::GetUnitArray()
		{
			return *reinterpret_cast<GameObject***>(Patchables::g_objectManagerUnitArray);
		}

		GameObject* ObjectManager::GetUnitByIndex( ushort index )
		{
			if (index <= 0)
			{
				return nullptr;
			}

			return GetUnitArray() [index];
		}

		GameObject* ObjectManager::GetUnitByNetworkId( uint networkId )
		{
			__try
			{
				if (networkId != 0)
				{
					for (auto i = 0; i < 10000; i++)
					{
						auto unit = GetUnitArray() [i];
						if (unit != nullptr)
						{
							auto netId = unit->GetNetworkId();
							if (netId != nullptr)
							{
								if (*netId == networkId && *netId >= 0x3FFFFCC8)
								{
									return unit;
								}
							}
						}
					}
				}
			}
			__except (1) {}

			return nullptr;
		}
	}
}
