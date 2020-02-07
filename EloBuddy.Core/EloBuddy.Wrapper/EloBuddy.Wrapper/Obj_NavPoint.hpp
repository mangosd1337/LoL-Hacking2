#pragma once

#include "GameObject.hpp"

namespace EloBuddy
{
	public ref class Obj_NavPoint : public GameObject {
	public:
		Obj_NavPoint( ushort index, uint networkId, Native::GameObject* unit ) : GameObject( index, networkId, unit ) {};
		Obj_NavPoint() {};
	};
}