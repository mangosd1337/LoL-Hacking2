#include "Stdafx.h"

#include "../../EloBuddy.Core/EloBuddy.Core/Obj_AI_Minion.h"

#include "Obj_AI_Minion.hpp"

namespace EloBuddy
{
	Vector3 Obj_AI_Minion::LeashedPosition::get()
	{
		auto ptr = this->GetPtr();
		if (ptr != nullptr)
		{
			auto pLeashedPosition = ptr->GetLeashedPosition();
			if (pLeashedPosition != nullptr)
			{
				return Vector3( pLeashedPosition->GetX(), pLeashedPosition->GetY(), pLeashedPosition->GetZ() );
			}
		}

		return Vector3::Zero;
	}
}