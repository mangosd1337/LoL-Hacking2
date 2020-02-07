#pragma once

#include "GameObject.hpp"

namespace EloBuddy
{
	public ref class LevelPropGameObject : public GameObject {
	public:
		LevelPropGameObject( ushort index, uint networkId, Native::GameObject* unit ) : GameObject( index, networkId, unit ) {};
		LevelPropGameObject() {};
	};
}