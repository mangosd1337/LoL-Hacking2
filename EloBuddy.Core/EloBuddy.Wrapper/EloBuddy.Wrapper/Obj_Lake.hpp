#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/Obj_Lake.h"

#include "Macros.hpp"
#include "Obj_Building.hpp"

namespace EloBuddy
{
	public ref class Obj_Lake : public Obj_Building {
	public:
		Obj_Lake( ushort index, uint networkId, Native::GameObject* unit ) : Obj_Building( index, networkId, unit ) {}
		Obj_Lake() {};
	};
}