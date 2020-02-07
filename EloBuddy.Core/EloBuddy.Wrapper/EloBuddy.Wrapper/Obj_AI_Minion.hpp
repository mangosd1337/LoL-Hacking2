#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/Obj_AI_Minion.h"

#include "Obj_AI_Base.hpp"

using namespace System;
using namespace SharpDX;

namespace EloBuddy
{
	public ref class Obj_AI_Minion : public Obj_AI_Base
	{
	internal:
		Native::Obj_AI_Minion* GetPtr()
		{
			return reinterpret_cast<Native::Obj_AI_Minion*>(GameObject::GetPtr());
		}
	public:
		Obj_AI_Minion( ushort index, uint networkId, Native::GameObject* unit ) : Obj_AI_Base( index, networkId, unit ) {}
		Obj_AI_Minion() {};

		MAKE_PROPERTY( LeashedPosition, Vector3 );

		CREATE_GET( CampNumber, int );
		CREATE_GET( MinionLevel, int );
		CREATE_GET( OriginalState, int );
		CREATE_GET( RoamState, int );
	};
}