#include "stdafx.h"
#include "NavMesh.h"
#include "Detour.hpp"
#include "Patchables.h"

namespace EloBuddy
{
	namespace Native
	{
		NavMesh* NavMesh::GetInstance()
		{
			return *reinterpret_cast<NavMesh**>(MAKE_RVA(Offsets::NavMesh::NavMeshController));
		}

		NavMeshCell** NavMesh::GetCells()
		{
			return *reinterpret_cast<NavMeshCell***>(this + 0x44);
		}

#pragma optimize( "", off )
		CollisionFlags NavMesh::GetCollisionFlags(float x, float y)
		{
			auto multiplicator = *this->GetCellMultiplicator();
			auto substractedX = x - *this->GetCellXDivision();
			auto substractedY = y - *this->GetCellYDivison();

			auto translatedX = static_cast<signed int>(floor(multiplicator * substractedX));
			auto translatedY = static_cast<signed int>(floor(multiplicator * substractedY));

			translatedX += 1;
			translatedY += 1;

			//Project
			if (translatedX <= *GetWidthDelta() - 1)
				translatedX--;

			if (translatedY <= *GetHeightDelta() - 1)
				translatedY--;

			auto navMeshFlagArray = *reinterpret_cast<DWORD*>(this + 0x24);
			return *reinterpret_cast<CollisionFlags*>(navMeshFlagArray + 2 * (translatedX + translatedY * *GetWidth()));
		}

		void NavMesh::SetCollisionFlags(float x, float y, CollisionFlags flags)
		{
			auto multiplicator = *this->GetCellMultiplicator();
			auto substractedX = x - *this->GetCellXDivision();
			auto substractedY = y - *this->GetCellYDivison();

			auto translatedX = static_cast<signed int>(floor(multiplicator * substractedX));
			auto translatedY = static_cast<signed int>(floor(multiplicator * substractedY));

			//Project
			if (translatedX <= *GetWidthDelta() - 1)
				translatedX--;

			if (translatedY <= *GetHeightDelta() - 1)
				translatedY--;

			auto navMeshFlagArray = *reinterpret_cast<DWORD*>(this + 0x24);
			*reinterpret_cast<CollisionFlags*>(navMeshFlagArray + 2 * (translatedX + translatedY * *GetWidth())) = flags;
		}

		void NavMesh::RestoreCollisionFlags(float x, float y)
		{

		}

		float NavMesh::GetHeightForPosition(float x, float y)
		{
			/*return reinterpret_cast<long double(__thiscall*)(void*, float, float)>
				MAKE_RVA(Offsets::NavMesh::GetHeightForPosition)
				(this, x, y);*/

			auto pFGetHeightForPosition = MAKE_RVA(Offsets::NavMesh::GetHeightForPosition);
			float height = 0.0f;

			__asm
			{
				movss xmm2, y
				movss xmm1, x
				mov ecx, this
				call [pFGetHeightForPosition]

				movss height, xmm0
			}

			return height;
		}

		bool NavMesh::IsWallOfGrass(float x, float y, float radius)
		{
			return reinterpret_cast<bool(__thiscall*)(void*, float, float, float, float)>
				MAKE_RVA(Offsets::NavMesh::IsWallOfGrass)
				(this, 0, x, y, radius);
		}

		bool NavMesh::LineOfSightTest(Vector3f* start, Vector3f* end)
		{
			return false;
		}

		Vector3f NavMesh::CellToWorld(short x, short y)
		{
			auto static const cellWidth = *GetInstance()->GetCellWidth();
			auto static const cellHeight = *GetInstance()->GetCellHeight();

			auto xWorld = x * cellWidth;
			auto yWorld = y * cellHeight;
			auto zWorld = GetInstance()->GetHeightForPosition(xWorld, yWorld);

			return Vector3f(xWorld, yWorld, zWorld);
		}

		Vector3f NavMesh::WorldToCell(float x, float y)
		{
			auto static const cellWidth = *GetInstance()->GetCellWidth();
			auto static const cellHeight = *GetInstance()->GetCellHeight();

			auto xWorld = x / cellWidth;
			auto yWorld = y / cellHeight;

			return Vector3f(xWorld, yWorld, 0);
		}
#pragma optimize( "", on ) 

		void NavMesh::SetCollision(Vector3f* start, Vector3f* end, CollisionFlags flags)
		{
			auto m = start->GetY() - end->GetY() / (start->GetX() - end->GetX());
			auto c = start->GetY() - m * start->GetX();

			auto startX = start->GetX() > end->GetX() ? end->GetX() : start->GetX();
			auto endX = start->GetX() > end->GetX() ? start->GetX() : end->GetX();

			for (auto x = startX; x <= endX; x += 0.3f)
			{
				auto y = x*m + c;
				this->SetCollisionFlags(x, y, flags);
			}
		}
	}
}
