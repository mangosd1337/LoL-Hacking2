#include "Stdafx.h"
#include "NavMesh.h"

namespace EloBuddy
{
	NavMeshCell^ NavMesh::GetCell(short x, short y)
	{
		return gcnew NavMeshCell(x, y);
	}

	NavMeshCell^ NavMesh::GetCell( int x, int y )
	{
		return gcnew NavMeshCell( x, y );
	}

	CollisionFlags NavMesh::GetCollisionFlags(float x, float y)
	{
		auto navMesh = Native::NavMesh::GetInstance();
		if (navMesh != nullptr)
		{
			return (CollisionFlags)navMesh->GetCollisionFlags(x, y);
		}

		return CollisionFlags::None;
	}

	bool NavMesh::SetCollisionFlags(CollisionFlags flags, float x, float y)
	{
		auto navMesh = Native::NavMesh::GetInstance();
		if (navMesh != nullptr)
		{
			navMesh->SetCollisionFlags(x, y, static_cast<Native::CollisionFlags>(flags));
			return true;
		}
		return false;
	}

	bool NavMesh::SetCollisionFlags(CollisionFlags flags, Vector2 pos)
	{
		return SetCollisionFlags(flags, pos.X, pos.Y);
	}

	bool NavMesh::SetCollisionFlags(CollisionFlags flags, Vector3 pos)
	{
		return SetCollisionFlags(flags, pos.X, pos.Y);
	}

	CollisionFlags NavMesh::GetCollisionFlags(Vector2 position)
	{
		return NavMesh::GetCollisionFlags(position.X, position.Y);
	}

	CollisionFlags NavMesh::GetCollisionFlags(Vector3 position)
	{
		return NavMesh::GetCollisionFlags(position.X, position.Y);
	}

	float NavMesh::GetHeightForPosition(float x, float y)
	{
		auto navMesh = Native::NavMesh::GetInstance();
		if (navMesh != nullptr)
		{
			return navMesh->GetHeightForPosition(x, y);
		}
		return 0;
	}

	bool NavMesh::IsWallOfGrass(Vector3 pos, float radius)
	{
		auto navMesh = Native::NavMesh::GetInstance();
		if (navMesh != nullptr)
		{
			return NavMesh::GetCollisionFlags( pos.X, pos.Y ).HasFlag( CollisionFlags::Grass );
			//return navMesh->IsWallOfGrass(pos.X, pos.Z, radius);
		}
		return false;
	}

	bool NavMesh::IsWallOfGrass(float x, float y, float radius)
	{
		auto navMesh = Native::NavMesh::GetInstance();
		if (navMesh != nullptr)
		{
			return NavMesh::GetCollisionFlags(x, y).HasFlag(CollisionFlags::Grass);
			//return navMesh->IsWallOfGrass( x, y, radius );
		}
		return false;
	}

	bool NavMesh::LineOfSightTest(Vector3 begin, Vector3 end)
	{
		auto navMesh = Native::NavMesh::GetInstance();
		if (navMesh != nullptr)
		{
			auto start = Native::Vector3f(0, 0, 0);
			auto end = Native::Vector3f(0, 0, 0);

			return navMesh->LineOfSightTest(&start, &end);
		}
		return false;
	}

	Vector3 NavMesh::GridToWorld(short x, short y)
	{
		auto navMesh = Native::NavMesh::GetInstance();
		if (navMesh != nullptr)
		{
			auto vecOut = navMesh->CellToWorld(x, y);
			return Vector3(vecOut.GetX(), vecOut.GetY(), vecOut.GetZ());
		}
		return Vector3::Zero;
	}

	Vector2 NavMesh::WorldToGrid(float x, float y)
	{
		auto navMesh = Native::NavMesh::GetInstance();
		if (navMesh != nullptr)
		{
			auto vec = navMesh->WorldToCell(x, y);
			return Vector2(vec.GetX(), vec.GetY());
		}
		return Vector2::Zero;
	}

	short NavMesh::Height::get()
	{
		auto navMesh = Native::NavMesh::GetInstance();
		if (navMesh != nullptr)
		{
			return *navMesh->GetHeightDelta();
		}
		return 0;
	}

	short NavMesh::Width::get()
	{
		auto navMesh = Native::NavMesh::GetInstance();
		if (navMesh != nullptr)
		{
			return *navMesh->GetWidthDelta();
		}
		return 0;
	}

	float NavMesh::CellWidth::get()
	{
		auto navMesh = Native::NavMesh::GetInstance();
		if (navMesh != nullptr)
		{
			return *navMesh->GetCellWidth();
		}
		return 0;
	}

	float NavMesh::CellHeight::get()
	{
		auto navMesh = Native::NavMesh::GetInstance();
		if (navMesh != nullptr)
		{
			return *navMesh->GetCellHeight();
		}
		return 0;
	}

	List<Vector3>^ NavMesh::CreatePath(Vector3 end)
	{
		auto vecList = gcnew List<Vector3>();
		auto player = ObjectManager::Player;
		if (player != nullptr)
		{
			for each(auto vec in player->GetPath(end))
				vecList->Add(vec);
		}
		return vecList;
	}

	List<Vector3>^ NavMesh::CreatePath(Vector3 start, Vector3 end)
	{
		auto vecList = gcnew List<Vector3>();
		auto player = ObjectManager::Player;
		if (player != nullptr)
		{
			for each(auto vec in player->GetPath(start, end))
				vecList->Add(vec);
		}
		return vecList;
	}

	List<Vector3>^ NavMesh::CreatePath( Vector3 end, bool smoothPath )
	{
		auto vecList = gcnew List<Vector3>();
		auto player = ObjectManager::Player;
		if (player != nullptr)
		{
			for each(auto vec in player->GetPath( end, smoothPath ))
				vecList->Add( vec );
		}
		return vecList;
	}

	List<Vector3>^ NavMesh::CreatePath( Vector3 start, Vector3 end, bool smoothPath )
	{
		auto vecList = gcnew List<Vector3>();
		auto player = ObjectManager::Player;
		if (player != nullptr)
		{
			for each(auto vec in player->GetPath( start, end, smoothPath ))
				vecList->Add( vec );
		}
		return vecList;
	}
}