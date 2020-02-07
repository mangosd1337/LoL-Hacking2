#pragma once

#include "Obj_Building.hpp"

namespace EloBuddy
{
	public ref class Obj_AnimatedBuilding : public Obj_Building {
	public:
		Obj_AnimatedBuilding( ushort index, uint networkId, Native::GameObject* unit ) : Obj_Building( index, networkId, unit ) {}
		Obj_AnimatedBuilding() {};
	};
}