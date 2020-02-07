#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/Obj_HQ.h"

#include "Macros.hpp"
#include "Obj_AnimatedBuilding.hpp"

namespace EloBuddy
{
	public ref class Obj_HQ : public Obj_AnimatedBuilding {
	public:
		Obj_HQ( ushort index, uint networkId, Native::GameObject* unit ) : Obj_AnimatedBuilding( index, networkId, unit ) {}
		Obj_HQ() {};
	};
}