#include "Stdafx.h"
#include "NavMeshCell.h"

namespace EloBuddy
{
	NavMeshCell::NavMeshCell(ushort x, ushort y)
	{
		this->m_x = x;
		this->m_y = y;
	}

	NavMeshCell::NavMeshCell( int x, int y )
	{
		this->m_x = static_cast<ushort>(x);
		this->m_y = static_cast<ushort>(y);
	}

	CollisionFlags NavMeshCell::CollFlags::get()
	{
		auto navMesh = Native::NavMesh::GetInstance();
		if (navMesh != nullptr)
		{
			auto w2sCell = this->WorldPosition;
			return (CollisionFlags)Native::NavMesh::GetInstance()->GetCollisionFlags(w2sCell.X, w2sCell.Y);
		}
		return CollisionFlags::None;
	}

	void NavMeshCell::CollFlags::set(CollisionFlags flags)
	{
		auto navMesh = Native::NavMesh::GetInstance();
		if (navMesh != nullptr)
		{
			auto w2sCell = this->WorldPosition;
			Native::NavMesh::GetInstance()->SetCollisionFlags(w2sCell.X, w2sCell.Y, static_cast<Native::CollisionFlags>(flags));
		}
	}

	Vector3 NavMeshCell::WorldPosition::get()
	{
		auto navMesh = Native::NavMesh::GetInstance();
		if (navMesh != nullptr)
		{
			auto c2w = navMesh->CellToWorld(this->m_x, this->m_y);
			return Vector3(c2w.GetX(), c2w.GetY(), c2w.GetZ());
		}
		return Vector3::Zero;
	}

	ushort NavMeshCell::GridX::get()
	{
		return this->m_x;
	}

	ushort NavMeshCell::GridY::get()
	{
		return this->m_y;
	}
}