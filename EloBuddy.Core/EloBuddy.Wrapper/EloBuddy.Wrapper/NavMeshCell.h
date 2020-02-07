#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/NavMesh.h"
#include "../../EloBuddy.Core/EloBuddy.Core/NavMeshCell.h"

#include "StaticEnums.h"

using namespace SharpDX;

namespace EloBuddy
{
	public ref class NavMeshCell
	{
	private:
		ushort m_x;
		ushort m_y;
	public:
		NavMeshCell(ushort x, ushort y);
		NavMeshCell( int x, int y );

		property SharpDX::Vector3 WorldPosition
		{
			SharpDX::Vector3 get();
		}

		property CollisionFlags CollFlags
		{
			CollisionFlags get();
			void set(CollisionFlags value);
		}

		property ushort GridX
		{
			ushort get();
		}

		property ushort GridY
		{
			ushort get();
		}
	};
}