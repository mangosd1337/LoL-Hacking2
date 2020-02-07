#pragma once

#include "GameObject.hpp"

namespace EloBuddy
{
	public ref class Obj_LampBulb : public GameObject {
	public:
		Obj_LampBulb( ushort index, uint networkId, Native::GameObject* unit ) : GameObject( index, networkId, unit ) {};
		Obj_LampBulb() {};
	};
}