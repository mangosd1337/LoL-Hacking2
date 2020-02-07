#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/Obj_Shop.h"

#include "Macros.hpp"
#include "Obj_Building.hpp"

namespace EloBuddy
{
	public ref class Obj_Shop : public Obj_Building {
	public:
		Obj_Shop( ushort index, uint networkId, Native::GameObject* unit ) : Obj_Building( index, networkId, unit ) {}
		Obj_Shop() {};
	};
}