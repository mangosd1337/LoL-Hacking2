#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/NavMesh.h"
#include "../../EloBuddy.Core/EloBuddy.Core/NavMeshCell.h"

#include "StaticEnums.h"
#include "NavMeshcell.h"
#include "ObjectManager.hpp"
#include "Obj_AI_Base.hpp"
#include "AIHeroClient.hpp"

using namespace System::Collections::Generic;
using namespace SharpDX;

namespace EloBuddy
{
	public ref class NavMesh
	{
	public:
		static NavMeshCell^ GetCell(short x, short y);
		static NavMeshCell^ GetCell( int x, int y );

		static CollisionFlags GetCollisionFlags(float x, float y);
		static CollisionFlags GetCollisionFlags(Vector2 position);
		static CollisionFlags GetCollisionFlags(Vector3 position);

		static bool SetCollisionFlags(CollisionFlags flags, float x, float y);
		static bool SetCollisionFlags(CollisionFlags flags, Vector2 position);
		static bool SetCollisionFlags(CollisionFlags flags, Vector3 position);

		static float GetHeightForPosition(float x, float y);

		static bool IsWallOfGrass(SharpDX::Vector3 pos, float radius);
		static bool IsWallOfGrass(float x, float y, float radius);

		static bool LineOfSightTest(SharpDX::Vector3 begin, SharpDX::Vector3 end);

		static Vector3 GridToWorld(short x, short y);
		static Vector2 WorldToGrid(float x, float y);

		static List<Vector3>^ CreatePath(Vector3 end);
		static List<Vector3>^ CreatePath(Vector3 start, Vector3 end);
		static List<Vector3>^ CreatePath( Vector3 end, bool smoothPath );
		static List<Vector3>^ CreatePath( Vector3 start, Vector3 end, bool smoothPath );

		static property short Height
		{
			short get();
		}

		static property short Width
		{
			short get();
		}

		static property float CellHeight
		{
			float get();
		}

		static property float CellWidth
		{
			float get();
		}
	};
}