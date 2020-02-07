#pragma once
#include "Macros.h"
#include "Offsets.h"
#include "Vector3f.h"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT TacticalMap
		{
		public:
			static TacticalMap* GetInstance();

			Vector3f* ToWorldCoord( float x, float y ) const; //ScreenToMinimap
			bool ToMapCoord( Vector3f* vecIn, float* xOut, float* yOut ) const; //WorldToMinimap

			MAKE_GET_SET( MinimapX, float, Offsets::TacticalMapStruct::MinimapX );
			MAKE_GET_SET( MinimapY, float, Offsets::TacticalMapStruct::MinimapY );
			MAKE_GET_SET( Width, float, Offsets::TacticalMapStruct::MinimapWidth );
			MAKE_GET_SET( Height, float, Offsets::TacticalMapStruct::MinimapHeight );
			MAKE_GET_SET( ScaleX, float, Offsets::TacticalMapStruct::ScaleX );
			MAKE_GET_SET( ScaleY, float, Offsets::TacticalMapStruct::ScaleY );
		};
	}
}
