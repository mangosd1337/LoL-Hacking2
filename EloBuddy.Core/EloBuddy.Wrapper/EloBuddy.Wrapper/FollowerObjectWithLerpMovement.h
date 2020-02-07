#pragma once

#include "FollowerObject.hpp"

namespace EloBuddy
{
	public ref class FollowerObjectWithLerpMovement : public FollowerObject {
	public:
		FollowerObjectWithLerpMovement( ushort index, uint networkId, Native::GameObject* unit ) : FollowerObject( index, networkId, unit ) {}
		FollowerObjectWithLerpMovement() {};
	};
}