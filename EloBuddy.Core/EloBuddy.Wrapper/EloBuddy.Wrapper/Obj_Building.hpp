#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/Obj_Building.h"

#include "Macros.hpp"
#include "AttackableUnit.hpp"

namespace EloBuddy
{
	public ref class Obj_Building : public AttackableUnit {
	public:
		Obj_Building( ushort index, uint networkId, Native::GameObject* unit ) : AttackableUnit( index, networkId, unit ) {}
		Obj_Building() {};
	};
}