#pragma once

#include "Obj_AI_Base.h"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT Obj_AI_Minion : public Obj_AI_Base
		{
		public:
			MAKE_GET( CampNumber, int, Offsets::Obj_AIMinion::CampNumber );
			MAKE_GET( LeashedPosition, Vector3f, Offsets::Obj_AIMinion::LeashedPosition );
			MAKE_GET( MinionLevel, int, Offsets::Obj_AIMinion::MinionLevel );
			MAKE_GET( OriginalState, int, Offsets::Obj_AIMinion::OriginalState );
			MAKE_GET( RoamState, int, Offsets::Obj_AIMinion::RoamState );

				
		};
	}
}
