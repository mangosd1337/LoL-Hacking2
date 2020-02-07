#pragma once

#include "Obj_AI_Base.hpp"

namespace EloBuddy
{
	public ref class FollowerObject : public Obj_AI_Base {
	public:
		FollowerObject( ushort index, uint networkId, Native::GameObject* unit ) : Obj_AI_Base( index, networkId, unit ) {}
		FollowerObject() {};
	};
}