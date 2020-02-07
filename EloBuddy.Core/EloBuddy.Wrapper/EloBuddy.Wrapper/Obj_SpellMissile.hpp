#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/Obj_SpellMissile.h"

#include "Macros.hpp"
#include "GameObject.hpp"
#include "Vector3Time.hpp"

using namespace SharpDX;

namespace EloBuddy
{
	ref class SpellData;
	ref class Obj_AI_Base;

	[Obsolete( "This class has been replaced with MissileClient." )]
	public ref class Obj_SpellMissile : public GameObject {
	private:
		Native::Obj_SpellMissile* self;
	public:
		Obj_SpellMissile( ushort index, uint networkId, Native::GameObject* unit );
		Obj_SpellMissile() {};

		array<Vector3Time^>^ GetPath( float precision );
		Vector3 GetPositionAfterTime( float timeElapsed );

		MAKE_PROPERTY( StartPosition, Vector3 );
		MAKE_PROPERTY( EndPosition, Vector3 );
		MAKE_PROPERTY( SData, SpellData^ );
		MAKE_PROPERTY( SpellCaster, Obj_AI_Base^ );
		MAKE_PROPERTY( Target, GameObject ^ );
	};
}