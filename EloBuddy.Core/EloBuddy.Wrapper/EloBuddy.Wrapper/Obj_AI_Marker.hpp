#pragma once

#include "Obj_AI_Base.hpp"

namespace EloBuddy
{
	public ref class Obj_AI_Marker : public Obj_AI_Base {
	public:
		Obj_AI_Marker( ushort index, uint networkId, Native::GameObject* unit ) : Obj_AI_Base( index, networkId, unit ) {}
		Obj_AI_Marker() {};
	};
}