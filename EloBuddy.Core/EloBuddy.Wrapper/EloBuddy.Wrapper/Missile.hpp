#pragma once

#include "GameObject.hpp"

namespace EloBuddy
{
	public ref class Missile : public GameObject {
	public:
		Missile( ushort index, uint networkId, Native::GameObject* unit ) : GameObject( index, networkId, unit ) {};
		Missile( ) {};
	};
}