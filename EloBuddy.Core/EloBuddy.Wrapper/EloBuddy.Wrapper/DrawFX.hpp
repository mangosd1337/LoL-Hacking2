#pragma once

#include "GameObject.hpp"

namespace EloBuddy
{
	public ref class DrawFX : public GameObject {
	public:
		DrawFX( ushort index, uint networkId, Native::GameObject* unit ) : GameObject( index, networkId, unit ) {};
		DrawFX() {};
	};
}