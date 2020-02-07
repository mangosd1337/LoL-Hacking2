#pragma once

#include "GameObject.hpp"

namespace EloBuddy
{
	public ref class NeutralMinionCamp : public GameObject {
	public:
		NeutralMinionCamp( ushort index, uint networkId, Native::GameObject* unit ) : GameObject( index, networkId, unit ) {};
		NeutralMinionCamp( ) {};
	};
}