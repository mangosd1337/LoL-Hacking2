#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/Obj_SpawnPoint.h"

#include "Macros.hpp"
#include "Obj_Building.hpp"

namespace EloBuddy
{
	public ref class Obj_SpawnPoint : public Obj_Building {
	public:
		Obj_SpawnPoint( ushort index, uint networkId, Native::GameObject* unit ) : Obj_Building( index, networkId, unit ) {}
		Obj_SpawnPoint() {};
	};
}