#include "stdafx.h"
#include "MissileClient.hpp"

#include "ObjectManager.hpp"
#include "SpellData.hpp"
#include "GameObject.hpp"

namespace EloBuddy
{
	MissileClient::MissileClient(ushort index, uint networkId, Native::GameObject* unit) : GameObject(index, NetworkId, unit) {}

	Vector3 MissileClient::StartPosition::get()
	{
		auto ptr = this->GetPtr();

		if (ptr != nullptr)
		{
			auto loc = ptr->GetLaunchPos();
			if (loc != nullptr)
			{
				return Vector3( loc->GetX(), loc->GetZ(), loc->GetY() );
			}
		}
		return Vector3::Zero;
	}

	Vector3 MissileClient::EndPosition::get()
	{
		auto ptr = this->GetPtr();

		if (ptr != nullptr)
		{
			auto loc = ptr->GetDestPos();
			auto managedVec = Vector3( loc->GetX(), loc->GetZ(), loc->GetY() );

			if (managedVec == Vector3::Zero)
			{
				if (Target != nullptr)
				{
					return Target->Position;
				}
			}

			return managedVec;
		}
		return Vector3::Zero;
	}

	SpellData^ MissileClient::SData::get()
	{
		auto ptr = this->GetPtr();

		if (ptr != nullptr)
		{
			auto missileData = ptr->GetMissileData();
			if (missileData != nullptr)
			{
				auto sdata = (*missileData)->GetSData();
				if (sdata != nullptr)
				{
					return gcnew SpellData( sdata );
				}
			}
		}
		return nullptr;
	}

	Obj_AI_Base^ MissileClient::SpellCaster::get()
	{
		auto ptr = this->GetPtr();

		if (ptr != nullptr)
		{
			auto casterIndex = ptr->GetSpellCaster();
			if (casterIndex != nullptr)
			{
				return reinterpret_cast<Obj_AI_Base^>(ObjectManager::GetUnitByIndex( *casterIndex ));
			}
		}

		return nullptr;
	}

	GameObject^ MissileClient::Target::get()
	{
		auto ptr = this->GetPtr();

		if (ptr != nullptr)
		{
			auto casterIndex = ptr->GetTarget();
			if (casterIndex != nullptr)
			{
				return ObjectManager::GetUnitByIndex( *casterIndex );
			}
		}

		return nullptr;
	}
}