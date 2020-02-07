#include "stdafx.h"

#include "GameObject.hpp"
#include "Exceptions.hpp"
#include "ObjectManager.hpp"
#include "Obj_AI_Base.hpp"
#include <msclr\marshal_cppstd.h>

using namespace System;
using namespace SharpDX;
using namespace System::Runtime::InteropServices;

namespace EloBuddy
{
	GameObject::GameObject(ushort index, uint networkId, Native::GameObject* unit)
	{
		this->m_index = index;
		this->m_networkId = networkId;
		this->self = unit;
	}

	static GameObject::GameObject()
	{
		ATTACH_DOMAIN();
		ATTACH_EVENT
		(
			GameObjectCreate,
			13, Native::OnGameObjectCreate, Native::GameObject*
		);
		ATTACH_EVENT
		(
			GameObjectDelete,
			24, Native::OnGameObjectDelete, Native::GameObject*
		);
		ATTACH_EVENT
		(
			GameObjectFloatPropertyChange,
			61, Native::OnGameObjectFloatPropertyChange, Native::GameObject*, const char*, float
		);
		ATTACH_EVENT
		(
			GameObjectIntegerPropertyChange,
			62, Native::OnGameObjectIntegerPropertyChange, Native::GameObject*, const char*, int
		);
	}

	void GameObject::DomainUnloadEventHandler(System::Object^, System::EventArgs^)
	{
		DETACH_EVENT
		(
			GameObjectCreate,
			13, Native::OnGameObjectCreate, Native::GameObject*
		);
		DETACH_EVENT
		(
			GameObjectDelete,
			24, Native::OnGameObjectDelete, Native::GameObject*
		);
		DETACH_EVENT
		(
			GameObjectFloatPropertyChange,
			61, Native::OnGameObjectFloatPropertyChange, Native::GameObject*, const char*, float
		);
		DETACH_EVENT
		(
			GameObjectIntegerPropertyChange,
			62, Native::OnGameObjectIntegerPropertyChange, Native::GameObject*, const char*, int
		);
	}

	void GameObject::OnGameObjectCreateNative( Native::GameObject* obj )
	{
		START_TRACE
			if (obj != nullptr)
			{
				if (GameObjectCreateHandlers->Count > 0)
				{
					GameObject^ sender = ObjectManager::CreateObjectFromPointer( obj );

					if (sender == nullptr)
					{
						return;
					}

					for each (auto eventHandle in GameObjectCreateHandlers->ToArray())
					{
						START_TRACE
							eventHandle( sender, EventArgs::Empty );
						END_TRACE
					}
				}
			}
		END_TRACE
	}

	void GameObject::OnGameObjectDeleteNative( Native::GameObject* obj )
	{
		START_TRACE
			if (obj != nullptr)
			{
				GameObject^ sender = ObjectManager::CreateObjectFromPointer( obj );

				if (sender == nullptr)
				{
					return;
				}

				// Remove all cached buffs
				Obj_AI_Base::cachedBuffs->Remove( sender->NetworkId );

				for each (auto eventHandle in GameObjectDeleteHandlers->ToArray())
				{
					START_TRACE
						eventHandle( sender, EventArgs::Empty );
					END_TRACE
				}
			}
		END_TRACE
	}

	void GameObject::OnGameObjectIntegerPropertyChangeNative(Native::GameObject* unit, const char* propName, int value)
	{
		START_TRACE
			if (unit != nullptr && propName != nullptr)
			{
				GameObject^ sender = ObjectManager::CreateObjectFromPointer( unit );
				auto args = gcnew GameObjectIntegerPropertyChangeEventArgs( propName, value );

				for each (auto eventHandle in GameObjectIntegerPropertyChangeHandlers->ToArray())
				{
					START_TRACE
						eventHandle( sender, args );
					END_TRACE
				}
			}
		END_TRACE
	}

	void GameObject::OnGameObjectFloatPropertyChangeNative( Native::GameObject* unit, const char* propName, float value )
	{
		START_TRACE
			if (unit != nullptr && propName != nullptr)
			{
				GameObject^ sender = ObjectManager::CreateObjectFromPointer( unit );
				auto args = gcnew GameObjectFloatPropertyChangeEventArgs( propName, value );

				for each (auto eventHandle in GameObjectFloatPropertyChangeHandlers->ToArray())
				{
					START_TRACE
						eventHandle( sender, args );
					END_TRACE
				}
			}
		END_TRACE
	}

	Native::GameObject* GameObject::GetPtr()
	{
		if (this->self != nullptr)
		{
			return this->self;
		}

		// Try to get by Index
		auto unit = Native::ObjectManager::GetUnitByIndex( this->m_index );
		if (unit != nullptr)
		{
			this->self = unit;
		}
		else
		{
			// Try to get by NetworkId
			auto unit = Native::ObjectManager::GetUnitByNetworkId( this->m_networkId );
			if (unit != nullptr)
			{
				this->self = unit;
			}
		}

		if (this->self == nullptr)
		{
			System::Console::WriteLine( "GameObjectNotFoundException(): Index: {0} - NetworkId: {1}", m_index, m_networkId );
			throw gcnew GameObjectNotFoundException();
		}

		return this->self;
	}


	Native::GameObject* GameObject::GetPtrUncached()
	{
		// Try to get by Index
		auto unit = Native::ObjectManager::GetUnitByIndex( this->m_index );
		if (unit != nullptr)
		{
			return unit;
		}
		else
		{
			// Try to get by NetworkId
			auto unit = Native::ObjectManager::GetUnitByNetworkId( this->m_networkId );
			if (unit != nullptr)
			{
				return unit;
			}
		}

		if (unit != nullptr)
		{
			this->self = unit;
		}

		return nullptr;
	}

	SharpDX::BoundingBox GameObject::BBox::get()
	{
		auto ptr = this->GetPtr();
		if (ptr != nullptr)
		{
			Native::BBox* bbox = ptr->GetBBox();
			auto minimum = Vector3( bbox->MinimumX, bbox->MinimumZ, bbox->MinimumY );
			auto maximum = Vector3( bbox->MaximumX, bbox->MaximumZ, bbox->MaximumY );
			return BoundingBox( minimum, maximum );
		}
		return BoundingBox( Vector3::Zero, Vector3::Zero );
	}

	System::String^ GameObject::Name::get()
	{
		auto ptr = this->GetPtr();
		if (ptr != nullptr)
		{
			auto name = ptr->GetName();
			return msclr::interop::marshal_as<String^>(name);
		}

		return "Unknown";
	}

	void GameObject::Name::set(String^ name)
	{
		auto ptr = this->GetPtr();
		if (ptr != nullptr)
		{
			auto ansiPtr = Marshal::StringToHGlobalAnsi( name );
			auto string = std::string(static_cast<char*>(ansiPtr.ToPointer()));
			ptr->SetName(std::wstring(string.begin(), string.end()));
			Marshal::FreeHGlobal( ansiPtr );
		}
	}

	SharpDX::Vector3 GameObject::Position::get()
	{
		auto unit = this->GetPtr();

		if (unit != nullptr)
		{
			auto pos = unit->GetPosition();
			return Vector3( pos.GetX(), pos.GetY(), pos.GetZ() );
		}

		return Vector3::Zero;
	}

	bool GameObject::IsMe::get()
	{
		auto me = Native::ObjectManager::GetPlayer();

		if (me == nullptr)
		{
			return false;
		}

		return (this->m_networkId == *me->GetNetworkId());
	}

	bool GameObject::IsAlly::get( )
	{
		auto me = Native::ObjectManager::GetPlayer();

		if (me == nullptr)
		{
			return false;
		}

		return ((int)this->Team == (int)*me->GetTeam( ));
	}

	bool GameObject::IsEnemy::get( )
	{
		return !this->IsAlly;
	}
	
	bool GameObject::IsValid::get()
	{
		if (this == nullptr)
		{
			return false;
		}

		Native::GameObject* ptr = nullptr;
		try
		{
			ptr = this->GetPtr();
		}
		catch (System::Exception^)
		{
			return false;
		}
		return ((ptr != nullptr) ? true : false);
	}

	GameObjectTeam GameObject::Team::get()
	{
		return static_cast<GameObjectTeam>(*this->GetPtr( )->GetTeam( ));
	}

	GameObjectType GameObject::Type::get()
	{
		return static_cast<GameObjectType>(this->GetPtr()->GetType());
	}

	bool GameObject::IsVisible::get()
	{
		if (!this->GetType()->IsAssignableFrom( Obj_AI_Base::typeid ))
		{
			return true; //ToDo: Fix
		}

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