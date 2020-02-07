#pragma once
#include "Obj_AnimatedBuilding.h"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT Obj_BarracksDampener : Obj_AnimatedBuilding
		{
		public:
			MAKE_GET( DampenerState, byte, Offsets::Obj_BarracksDampener::DampenerState ); //InvalidState on dampener; state=%i newS
		};
	}
}
