#pragma once

#include "GameObject.hpp"

namespace EloBuddy
{
	public ref class LevelPropAI : public GameObject {
	public:
		LevelPropAI( ushort index, uint networkId, Native::GameObject* unit ) : GameObject( index, networkId, unit ) {};
		LevelPropAI() {};
	};
}