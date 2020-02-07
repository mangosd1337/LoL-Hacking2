#pragma once

#include "GameObject.hpp"

namespace EloBuddy
{
	public ref class GrassObject : public GameObject {
	public:
		GrassObject( ushort index, uint networkId, Native::GameObject* unit ) : GameObject( index, networkId, unit ) {}
		GrassObject() {};
	};
}