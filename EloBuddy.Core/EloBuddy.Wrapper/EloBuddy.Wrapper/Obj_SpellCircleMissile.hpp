#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/Obj_SpellCircleMissile.h"

#include "Macros.hpp"
#include "Obj_SpellMissile.hpp"

namespace EloBuddy
{
	[Obsolete( "This class has been replaced with MissileClient." )]
	public ref class Obj_SpellCircleMissile : public Obj_SpellMissile
	{
	public:
		Obj_SpellCircleMissile( ushort index, uint networkId, Native::GameObject* unit ) : Obj_SpellMissile( index, networkId, unit ) {}
		Obj_SpellCircleMissile() {};
	};
}