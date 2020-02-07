#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/Obj_BarracksDampener.h"

#include "Macros.hpp"
#include "Obj_AnimatedBuilding.hpp"

namespace EloBuddy
{
	public ref class Obj_BarracksDampener : public Obj_AnimatedBuilding
	{
	internal:
		Native::Obj_BarracksDampener* GetPtr()
		{
			return reinterpret_cast<Native::Obj_BarracksDampener*>(GameObject::GetPtr());
		}
	public:
		Obj_BarracksDampener( ushort index, uint networkId, Native::GameObject* unit ) : Obj_AnimatedBuilding( index, networkId, unit ) {}
		Obj_BarracksDampener() {};

		property DampenerState State
		{
			DampenerState get()
			{
				auto ptr = this->GetPtr();
				if (ptr != nullptr)
				{
					return static_cast<DampenerState>(*ptr->GetDampenerState());
				}
				return DampenerState::Unknown;
			}
		}
	};
}