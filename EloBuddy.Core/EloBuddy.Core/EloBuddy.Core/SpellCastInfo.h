#pragma once

#include "Macros.h"
#include "Detour.hpp"
#include "SpellDataInst.h"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT SpellCastInfo
		{
		public:
			MAKE_GET( Counter, int, Offsets::SpellCastInfo::Counter );
			MAKE_GET( Start, Vector3f, Offsets::SpellCastInfo::Start );
			MAKE_GET( End, Vector3f, Offsets::SpellCastInfo::End );
			MAKE_GET( SpellSlot, int, Offsets::SpellCastInfo::Slot );

			inline ushort GetLocalId()
			{
				__try
				{
					return (*reinterpret_cast<ushort*>(this + 0x58)) & 0xFFFF;
				}
				__except (EXCEPTION_EXECUTE_HANDLER)
				{
					return 0;
				}
			}

			inline int GetLevel()
			{
				return (*reinterpret_cast<int*>(this + static_cast<int>(Offsets::SpellCastInfo::Level))) + 1;
			}

			inline std::string* GetMissileName()
			{
				auto sdata = *reinterpret_cast<DWORD**>(this + static_cast<int>(Offsets::SpellCastInfo::SpellData));
				return reinterpret_cast<std::string*>(sdata + 0x6);
			}

			inline SpellData* __stdcall GetSpellData()
			{
				return *reinterpret_cast<SpellData**>(this);
			}

			bool GetIsValid()
			{
				//Fuck me :S?

				__try
				{
					return *reinterpret_cast<DWORD*>(this + 0x60) == NULL
						&& *reinterpret_cast<DWORD*>(this + 0x64) == NULL
						&& *reinterpret_cast<DWORD*>(this + 0x68) == NULL
						&& *reinterpret_cast<DWORD*>(this + 0x6C) == NULL
						&& *reinterpret_cast<DWORD*>(this + 0x80) == NULL;
				}
				__except (1)
				{
					return false;
				}
			}
		};
	}
}
