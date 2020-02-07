#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/MissileClient.h"

#include "GameObject.hpp"

using namespace System;
using namespace SharpDX;

namespace EloBuddy
{
	ref class SpellData;
	ref class Obj_AI_Base;

	public ref class MissileClient : public GameObject
	{
	internal:
		Native::MissileClient* self;

		Native::MissileClient* GetPtr()
		{
			return reinterpret_cast<Native::MissileClient*>(GameObject::GetPtrUncached());
		}
	public:
		MissileClient( ushort index, uint networkId, Native::GameObject* unit );
		MissileClient() {};

		MAKE_PROPERTY( StartPosition, Vector3 );
		MAKE_PROPERTY( EndPosition, Vector3 );
		MAKE_PROPERTY( SData, SpellData^ );
		MAKE_PROPERTY( SpellCaster, Obj_AI_Base^ );
		MAKE_PROPERTY( Target, GameObject ^ );
	};
}