#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/GameObject.h"
#include "../../EloBuddy.Core/EloBuddy.Core/GameObjectVTable.h"
#include "../../EloBuddy.Core/EloBuddy.Core/EventHandler.h"
#include "../../EloBuddy.Core/EloBuddy.Core/ObjectManager.h"
#include "../../EloBuddy.Core/EloBuddy.Core/AIHeroClient.h"

#include "Macros.hpp"
#include "StaticEnums.h"
#include "GameObjectCreate.hpp"
#include "GameObjectFloatPropertyChangeEventArgs.h"
#include "GameObjectIntegerPropertyChangeEvent.h"

using namespace System;
using namespace SharpDX;

namespace EloBuddy
{
	MAKE_EVENT_GLOBAL( GameObjectCreate, GameObject^ sender, EventArgs^ args );
	MAKE_EVENT_GLOBAL( GameObjectDelete, GameObject^ sender, EventArgs^ args );
	MAKE_EVENT_GLOBAL( GameObjectFloatPropertyChange, GameObject^ sender, GameObjectFloatPropertyChangeEventArgs^ args );
	MAKE_EVENT_GLOBAL( GameObjectIntegerPropertyChange, GameObject^ sender, GameObjectIntegerPropertyChangeEventArgs^ args );

	public ref class GameObject {
	internal:
		MAKE_EVENT_INTERNAL( GameObjectCreate, (Native::GameObject* unit) );
		MAKE_EVENT_INTERNAL( GameObjectDelete, (Native::GameObject* unit) );
		MAKE_EVENT_INTERNAL( GameObjectFloatPropertyChange, (Native::GameObject*, const char*, float) );
		MAKE_EVENT_INTERNAL( GameObjectIntegerPropertyChange, (Native::GameObject*, const char*, int) );

		Native::GameObject* GetPtr();
		Native::GameObject* GetPtrUncached();
	protected:
		uint m_networkId;
		ushort m_index;
		Native::GameObject* self;
	public:
		MAKE_EVENT_PUBLIC( OnCreate, GameObjectCreate );
		MAKE_EVENT_PUBLIC( OnDelete, GameObjectDelete );
		MAKE_EVENT_PUBLIC( OnFloatPropertyChange, GameObjectFloatPropertyChange );
		MAKE_EVENT_PUBLIC( OnIntegerPropertyChange, GameObjectIntegerPropertyChange );

		GameObject( ushort index, uint networkId, Native::GameObject* );
		GameObject() {};
		static GameObject();
		static void DomainUnloadEventHandler( Object^, EventArgs^ );

		property IntPtr MemoryAddress
		{
			IntPtr get()
			{
				return static_cast<IntPtr>(this->GetPtr());
			}
		}

		property short Index
		{
			short get()
			{
				return static_cast<short>(this->m_index);
			}
		}

		property int NetworkId
		{
			int get()
			{
				return static_cast<int>(this->m_networkId);
			}
		}

		property BoundingBox BBox
		{
			BoundingBox get();
		}

		property String^ Name
		{
			String^ get();
			void set( String^ );
		}

		property Vector3 Position
		{
			Vector3 get();
		}

		property bool IsMe
		{
			bool get();
		}

		property bool IsAlly
		{
			bool get();
		}

		property bool IsEnemy
		{
			bool get();
		}

		property bool IsValid
		{
			bool get();
		}

		property GameObjectTeam Team
		{
			GameObjectTeam get();
		}

		property GameObjectType Type
		{
			GameObjectType get();
		}

		property float BoundingRadius
		{
			float get()
			{
				auto self = this->GetPtr();
				if (self != nullptr)
				{
					auto vt = self->GetVirtual();
					if (vt != nullptr)
					{
						return vt->BoundingRadius();
					}
				}

				return 0.0f;
			}
		}

		//Crashes on all objects that are not assignable from Obj_AI_Base
		//ToDo: Find proper IsVisible
		property bool IsVisible
		{
			bool get();
		}

		CREATE_GET( IsDead, bool );
		CREATE_GET( VisibleOnScreen, bool );
	};
}