#pragma once

#include "Obj_AI_Base.hpp"

namespace EloBuddy
{
	public ref class Obj_AI_Turret : public Obj_AI_Base {
	public:
		Obj_AI_Turret( ushort index, uint networkId, Native::GameObject* unit ) : Obj_AI_Base( index, networkId, unit ) {}
		Obj_AI_Turret() {};
	};
}