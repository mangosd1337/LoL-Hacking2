	#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/Obj_Barracks.h"

#include "stdafx.h"
#include "Macros.hpp"
#include "Obj_Building.hpp"

namespace EloBuddy
{
	public ref class Obj_Barracks : public Obj_Building {
	public:
		Obj_Barracks( ushort index, uint networkId, Native::GameObject* unit ) : Obj_Building( index, networkId, unit ) {}
		Obj_Barracks() {};
	};
}