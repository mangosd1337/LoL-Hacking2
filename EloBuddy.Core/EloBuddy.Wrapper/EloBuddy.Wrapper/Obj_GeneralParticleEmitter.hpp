#pragma once

#include "GameObject.hpp"

namespace EloBuddy
{
	public ref class Obj_GeneralParticleEmitter : public GameObject {
	public:
		Obj_GeneralParticleEmitter( ushort index, uint networkId, Native::GameObject* unit ) : GameObject( index, networkId, unit ) {};
		Obj_GeneralParticleEmitter() {};
	};
}