#pragma once

#include "GameObject.hpp"

namespace EloBuddy
{
	public ref class Obj_InfoPoint : public GameObject {
	public:
		Obj_InfoPoint( ushort index, uint networkId, Native::GameObject* unit ) : GameObject( index, networkId, unit ) {};
		Obj_InfoPoint() {};
	};
}