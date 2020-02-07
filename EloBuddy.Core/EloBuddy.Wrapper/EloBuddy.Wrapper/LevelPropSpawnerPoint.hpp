#pragma once

#include "GameObject.hpp"

namespace EloBuddy
{
	public ref class LevelPropSpawnerPoint : public GameObject {
	public:
		LevelPropSpawnerPoint( ushort index, uint networkId, Native::GameObject* unit ) : GameObject( index, networkId, unit ) {};
		LevelPropSpawnerPoint() {};
	};
}