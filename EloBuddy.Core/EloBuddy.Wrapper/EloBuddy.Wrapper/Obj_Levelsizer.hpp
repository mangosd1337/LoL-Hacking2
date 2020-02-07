#pragma once

#include "GameObject.hpp"

namespace EloBuddy
{
	public ref class Obj_Levelsizer : public GameObject {
	public:
		Obj_Levelsizer( ushort index, uint networkId, Native::GameObject* unit ) : GameObject( index, networkId, unit ) {};
		Obj_Levelsizer() {};
	};
}