#pragma once
#include "Utils.h"
#include "Vector3f.h"

namespace EloBuddy
{
	namespace Native
	{
		struct DLLEXPORT NavigationPath
		{
			NavigationPath();
			~NavigationPath();

			DWORD dwCurPath;		//0
			DWORD dwUnkn;			//4
			Vector3f StartVec;		//8
			Vector3f EndVec;		//14
			Vector3f* Path;			//20
			Vector3f* PathEnd;		//24
			char _byte0268 [1000];	//1024

			Vector3f** GetBegin() const
			{
				__asm
				{
					lea eax, [ecx+0x20]
				}
			}

			Vector3f** GetEnd() const
			{
				__asm
				{
					lea eax, [ecx+0x24]
				}
			}

			Vector3f* GetPath( int pathId )
			{
				if (pathId > 0)
					pathId--;

				if (pathId < GetPathCount())
					return GetBegin() [pathId];

				return nullptr;
			}

			int GetPathCount()
			{
				return static_cast<DWORD>(reinterpret_cast<DWORD>(PathEnd) -reinterpret_cast<DWORD>(Path)) / 12;
			}
		};
	}
}