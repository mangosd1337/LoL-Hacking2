#include "stdafx.h"

#include "Obj_SpellMissile.hpp"
#include "GameObject.hpp"
#include "SpellData.hpp"
#include "Obj_AI_Base.hpp"

namespace EloBuddy
{
	Obj_SpellMissile::Obj_SpellMissile( ushort index, uint networkId, Native::GameObject* unit ) : GameObject( index, networkId, unit )
	{
		
	}

	Vector3 Obj_SpellMissile::StartPosition::get()
	{
		//if (this->GetPtr() != nullptr)
		//{
		//	auto loc = this->GetPtr()->GetLaunchPos();
		//	return Vector3( loc->GetX(), loc->GetZ(), loc->GetY() );
		//}
		return Vector3::Zero;
	}

	Vector3 Obj_SpellMissile::EndPosition::get()
	{
		//if (this->GetPtr() != nullptr)
		//{
		//	auto loc = this->GetPtr()->GetDestPos();
		//	return Vector3( loc->GetX(), loc->GetZ(), loc->GetY() );
		//}
		return Vector3::Zero;
	}

	SpellData^ Obj_SpellMissile::SData::get()
	{
		//if (this->GetPtr() != nullptr)
		//{
		//	if (this->GetPtr()->GetSData() != nullptr)
		//	{
		//		return gcnew SpellData( this->GetPtr()->GetSData() );
		//	}
		//}
		return nullptr;
	}

	Obj_AI_Base^ Obj_SpellMissile::SpellCaster::get()
	{
		//if (this->GetPtr() != nullptr)
		//{
		//	auto casterIndex = this->GetPtr()->GetSpellCaster();
		//	return  (Obj_AI_Base^) ObjectManager::GetUnitByIndex( casterIndex );
		//}

		return nullptr;
	}

	GameObject^ Obj_SpellMissile::Target::get()
	{
		//if (this->GetPtr() != nullptr)
		//{
		//	auto casterIndex = this->GetPtr()->Virtual_GetTarget();
		//	if (casterIndex != NULL)
		//		return ObjectManager::GetUnitByIndex( casterIndex );
		//}

		return nullptr;
	}

	array<Vector3Time^>^ Obj_SpellMissile::GetPath( float precision )
	{
		auto vectorList = gcnew List<Vector3Time^>();

		//if (this->GetPtr() != nullptr)
		//{
		//	auto wpVector = this->GetPtr()->GetMissilePath();

		//	for (auto path = wpVector->begin(); path != wpVector->end(); ++path)
		//	{
		//		vectorList->Add( gcnew Vector3Time( Vector3( path->GetX(), path->GetZ(), path->GetY() ), 0 ) );
		//	}

		//	delete wpVector;
		//}

		return vectorList->ToArray();
	}

	Vector3 Obj_SpellMissile::GetPositionAfterTime( float timeElapsed )
	{
		//if (this->GetPtr() != nullptr)
		//{
		//	auto vec = this->GetPtr()->GetPositionAfterTime( timeElapsed );
		//	if (vec != nullptr)
		//	{
		//		return Vector3( vec->GetX(), vec->GetY(), vec->GetZ() );
		//	}
		//}

		return Vector3::Zero;
	}
}