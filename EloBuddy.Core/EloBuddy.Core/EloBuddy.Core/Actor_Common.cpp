#include "stdafx.h"
#include "Actor_Common.h"
#include "Vector3f.h"
#include <memory>
#include "Detour.hpp"
#include "Obj_AI_Base.h"
#include "EventHandler.h"

namespace EloBuddy
{
	namespace Native
	{
		MAKE_HOOK<convention_type::cdecl_t, int, int, int, int, int, int, int, float> Actor_SetPath;
		MAKE_HOOK<convention_type::stdcall_t, int, NavigationPath*, Vector3f*, Vector3f*, int*, int, int, int, int, int> Actor_CreatePath;

		bool Actor_Common::ApplyHooks()
		{
			Actor_SetPath.Apply(MAKE_RVA(Offsets::Actor_Common::SetPath), [] (int wpVector, int unkn1, int unkn2, int pathCompressedHeader, int m_isDash, int unkn3, float unkn4) -> int
			{
				Obj_AI_Base* sender = nullptr;

				__asm
				{
					mov sender, ecx
				}

				auto waypointList = reinterpret_cast<std::vector<Vector3f>*>(&wpVector);
				auto isDash = (*reinterpret_cast<int*>(&m_isDash) == 1) ? false : true;

				if (waypointList != nullptr && sender != nullptr)
				{
					EventHandler<17, OnObjAIBaseNewPath, Obj_AI_Base*, std::vector<Native::Vector3f>*, bool, float>::GetInstance()->Trigger(sender, waypointList, isDash, 0);
				}

				__asm
				{
					mov ecx, sender
				}

				return Actor_SetPath.CallOriginal(wpVector, unkn1, unkn2, pathCompressedHeader, m_isDash, unkn3, unkn4);
			});

			return Actor_SetPath.IsApplied();
		}

		bool Actor_Common::CreatePath(GameObject* unit, const Vector3f& destination, const NavigationPath& pathOut)
		{
			return false;
			//auto returnValue = reinterpret_cast<bool( __thiscall* )(void*, const NavigationPath*, const Vector3f*)>
			//	MAKE_RVA( Offsets::Actor_Common::CreatePath )
			//	(this, &pathOut, &destination);

			//Console::PrintLn( "returnValue: %d", returnValue );

			//if (returnValue == 1)
			//	return false;

			//return true;

			/*
			static auto ActorCreatePath = MAKE_RVA( Offsets::Actor_Common::CreatePath );
			auto pDest = &destination;
			auto pPath = &pathOut;

			//int returnValue = false;

			__asm
			{
			push pDest
			push pPath
			mov ecx, this
			call [ActorCreatePath]

			retn
			}

			//Console::PrintLn( "returnValue: %d", returnValue );
			*/
			//return returnValue;
		}

		bool Actor_Common::CreatePath(const Vector3f& start, const Vector3f& destination, const NavigationPath& pathOut)
		{
			static auto ActorCreatePath = MAKE_RVA(Offsets::Actor_Common::NavMesh_CreatePath);
			static auto navMesh = *this->GetNavMesh();

			auto null_int = std::make_unique<int>();

			auto pStart = &start;
			auto pDest = &destination;
			auto pOut = &pathOut;

			auto returnValue = false;

			__asm
			{
				push 0x2710
					push 0x2710
					push null_int
					push 0x2710
					push null_int
					push 0x2710
					push this
					push destination
					push pStart
					push pOut
					mov ecx, navMesh
					call [ActorCreatePath]

					mov returnValue, al
			}

			return returnValue;
		}

		void Actor_Common::SmoothPath(NavigationPath* path)
		{
			reinterpret_cast<void(__thiscall*)(void*, NavigationPath*)>
				MAKE_RVA(Offsets::Actor_Common::SmoothPath)
				(this, path);
		}

		bool Actor_Common::GetHasNavPath()
		{
			return *reinterpret_cast<byte*>(this + static_cast<int>(Offsets::ActorCommonStruct::HasNavPath)) == 1;
		}

		int* Actor_Common::GetNavMesh()
		{
			return reinterpret_cast<int*>(this + static_cast<int>(Offsets::ActorCommonStruct::NavMesh));
		}
	}
}

