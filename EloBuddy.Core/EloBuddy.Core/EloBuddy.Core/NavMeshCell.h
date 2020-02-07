#pragma once

#include "StaticEnums.h"
#include "Utils.h"
#include "Vector3f.h"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT NavMeshCell
		{
		public:
			MAKE_GET_SET(CollisionFlags, int, 0x0);
		};
	}
}
