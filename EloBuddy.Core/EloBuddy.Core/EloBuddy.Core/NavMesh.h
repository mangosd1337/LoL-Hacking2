#pragma once

#include "Utils.h"
#include "NavMeshCell.h"
#include "Vector3f.h"

namespace EloBuddy
{
	namespace Native
	{
		struct NavigationPath;

		class
			DLLEXPORT NavMesh
		{
		private:

		public:
			static NavMesh* GetInstance();

			NavMeshCell** GetCells();

			CollisionFlags GetCollisionFlags( float x, float y );
			void SetCollisionFlags( float x, float y, CollisionFlags flags );
			void RestoreCollisionFlags( float x, float y );

			float GetHeightForPosition( float x, float y );

			bool IsWallOfGrass( float x, float y, float radius );
			bool LineOfSightTest( Vector3f* start, Vector3f* end );

			static Vector3f CellToWorld( short x, short y );
			static Vector3f WorldToCell( float x, float y );

			void SetCollision( Vector3f* start, Vector3f* end, CollisionFlags flags );

			MAKE_GET( Height, short, Offsets::NavMeshStruct::Height );
			MAKE_GET( Width, short, Offsets::NavMeshStruct::Width );
			MAKE_GET( CellWidth, float, Offsets::NavMeshStruct::CellWidth );
			MAKE_GET( CellHeight, float, Offsets::NavMeshStruct::CellHeight );
			MAKE_GET( CellMultiplicator, float, Offsets::NavMeshStruct::CellMultiplicator );

			MAKE_GET( HeightDelta, short, Offsets::NavMeshStruct::CellHeightDelta );
			MAKE_GET( WidthDelta, short, Offsets::NavMeshStruct::CellWidthDelta );

			MAKE_GET( CellXDivision, float, 0x8 );
			MAKE_GET( CellYDivison, float, 0x10 );
		};
	}
}
