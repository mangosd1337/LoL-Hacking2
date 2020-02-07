#pragma once
#include "../../EloBuddy.Core/EloBuddy.Core/GameObject.h"
#include "../../EloBuddy.Core/EloBuddy.Core/ObjectManager.h"

#include "GameObject.hpp"
#include "OnAppendObjectEventArgs.hpp"

using namespace System;
using namespace System::Linq;
using namespace System::Collections::Generic;

namespace EloBuddy
{
	ref class AIHeroClient;

	MAKE_EVENT_GLOBAL( ObjectManagerAppendObject, GameObject^ sender, OnCreateObjectEventArgs^ args );

	public ref class ObjectManager 
	{
	internal:
		MAKE_EVENT_INTERNAL( ObjectManagerAppendObject, (Native::GameObject*, char**, int*, int*, Native::Vector3f**) );
		static void OnObjectCreate( GameObject ^sender, EventArgs ^args );
		static void OnObjectDelete( GameObject ^sender, EventArgs ^args );
		static void RefreshCache();
		static bool cacheCreated;
		value class EntityPredicate
		{
			IntPtr address;
		public:
			EntityPredicate( IntPtr address )
			{
				this->address = address;
			}
			bool IsMatch( GameObject ^entity )
			{
				return entity->MemoryAddress == address;
			}
		};
	private:
		static AIHeroClient^ m_cachedPlayer;
		static List<GameObject^>^ cachedObjects = gcnew List<GameObject^>();
	public:
		MAKE_EVENT_PUBLIC( OnCreate, ObjectManagerAppendObject );

		static ObjectManager();
		static void DomainUnloadEventHandler( System::Object^, System::EventArgs^ );

		static property AIHeroClient^ Player
		{
			AIHeroClient^ get();
		}

		static GameObject^ GetUnitByNetworkId(uint networkId);
		static GameObject^ GetUnitByIndex(ushort index);
		static GameObject^ CreateObjectFromPointer(Native::GameObject* obj);
		static Type^ NativeTypeToManagedType( Native::UnitType type );

		generic <typename ObjectType>
		where ObjectType : GameObject, gcnew()
		static ObjectType GetUnitByNetworkId( uint networkId )
		{
			Native::GameObject* nativeUnit = Native::ObjectManager::GetUnitByNetworkId( networkId );
			if (nativeUnit != nullptr)
			{
				return (ObjectType) CreateObjectFromPointer( nativeUnit );
			}

			return ObjectType();
		}

		generic <typename ObjectType>
		where ObjectType : GameObject, gcnew()
		static IEnumerable<ObjectType>^ Get()
		{
			RefreshCache();
			if (GameObject::typeid == ObjectType::typeid)
			{
				return Enumerable::Cast<ObjectType>( cachedObjects );
			}

			return Enumerable::OfType<ObjectType>( cachedObjects );
		}
	};
}