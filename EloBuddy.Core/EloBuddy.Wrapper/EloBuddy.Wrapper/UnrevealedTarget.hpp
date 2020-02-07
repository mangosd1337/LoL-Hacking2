#pragma once

#include "GameObject.hpp"

namespace EloBuddy
{
	public ref class UnrevealedTarget : public GameObject {
	public:
		UnrevealedTarget( ushort index, uint networkId, Native::GameObject* unit ) : GameObject( index, networkId, unit ) {};
		UnrevealedTarget() {};
	};
}