#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/Obj_Turret.h"

#include "Macros.hpp"
#include "Obj_AnimatedBuilding.hpp"

namespace EloBuddy
{
	public ref class Obj_Turret : public Obj_AnimatedBuilding {
	public:
		Obj_Turret( ushort index, uint networkId, Native::GameObject* unit ) : Obj_AnimatedBuilding( index, networkId, unit ) {}
		Obj_Turret() {};
	};
}