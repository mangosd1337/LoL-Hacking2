#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/Obj_SpellChainMissile.h"

#include "Macros.hpp"
#include "Obj_SpellMissile.hpp"

namespace EloBuddy
{
	[Obsolete("This class has been replaced with MissileClient.")]
	public ref class Obj_SpellChainMissile : public Obj_SpellMissile
	{
	public:
		Obj_SpellChainMissile( ushort index, uint networkId, Native::GameObject* unit ) : Obj_SpellMissile( index, networkId, unit ) {}
		Obj_SpellChainMissile() {};
	};
}