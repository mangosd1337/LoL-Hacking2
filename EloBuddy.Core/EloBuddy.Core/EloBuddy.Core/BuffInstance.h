#pragma once

#include "Macros.h"
#include "StaticEnums.h"
#include "GameObject.h"
#include "RiotClock.h"
#include "ObjectManager.h"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT ChildScriptBuff
		{
		public:
			MAKE_GET( SourceName, char, 0x1C );
		};

		class VirtualScriptBaseBuff
		{
		public:
			virtual ~VirtualScriptBaseBuff()
			{
			}

			virtual void Function0() = 0; //0
			virtual void Function1() = 0; //4
			virtual void Function2() = 0; //8
			virtual void Function3() = 0; //C
			virtual void Function4() = 0; //10
			virtual void Function5() = 0; //14
			virtual void Function6() = 0; //18
			virtual void Function7() = 0; //1C
			virtual void Function8() = 0; //20
			virtual void Function9() = 0; //24
			virtual void Function10() = 0; //28
			virtual void Function11() = 0; //2C
			virtual char* GetDisplayName() = 0; //30
		};

		class
			DLLEXPORT ScriptBaseBuff
		{
		public:
			VirtualScriptBaseBuff* GetVirtual()
			{
				return reinterpret_cast<VirtualScriptBaseBuff*>(this);
			}

			char* GetName()
			{
				IS_NULL_RETN( this, static_cast<int>(Offsets::ScriptBaseBuff::Name), "Unknown" );

				return reinterpret_cast<char*>(this + static_cast<int>(Offsets::ScriptBaseBuff::Name));
			}

			ChildScriptBuff* GetChildScriptBuff()
			{
				__try
				{
					return *reinterpret_cast<ChildScriptBuff**>(this + static_cast<int>(Offsets::ScriptBaseBuff::ChildScriptBuff));
				}
				__except (EXCEPTION_EXECUTE_HANDLER)
				{
					return nullptr;
				}
			}
		};

		class
			DLLEXPORT BuffScriptInstance
		{
		public:
			GameObject* GetCaster()
			{
				__try
				{
					if (this != nullptr)
					{
						auto netId = reinterpret_cast<uint*>(this + 0x8);
						if (netId != nullptr)
						{
							return ObjectManager::GetUnitByNetworkId( *netId );
						}
					}
				}
				__except (1) {}

				return nullptr;
			}
		};

		class
			DLLEXPORT BuffInstance
		{
		public:
			bool IsPositive()
			{
				auto static const riotClock = RiotClock::GetInstance();
				if (riotClock != nullptr)
				{
					return this != nullptr
						&& *this->GetEndTime() > *riotClock->GetClockTime();
				}
				return false;
			}

			bool IsActive()
			{
				auto static const riotClock = RiotClock::GetInstance();
				if (riotClock != nullptr)
				{
					return this != nullptr
						&& *this->GetEndTime() > *riotClock->GetClockTime();
				}
				return false;
			}

			bool IsValid()
			{
				IS_NULL_RETN( this, 0x18, false );
				IS_NULL_RETN( this, 0x1C, false );
				IS_NULL_RETN( this, 0x4, false );
				IS_NULL_RETN( this, 0x68, false );
				IS_NULL_RETN( this, static_cast<int>(Offsets::BuffInstance::StartTime), false );

				return this != nullptr
					&& *reinterpret_cast<DWORD*>(this + 0x18) != *reinterpret_cast<DWORD*>(this + 0x1C)
					&& *reinterpret_cast<DWORD*>(this + 0x4) || *reinterpret_cast<BYTE*>(this + 0x68)
					&& *this->GetStartTime() > 0; // Negative time on game reload?
			}

			bool IsPermanent()
			{
				IS_NULL_RETN( this, static_cast<int>(Offsets::BuffInstance::EndTime), false );

				return *this->GetEndTime() > 20000.0;
			}

			ScriptBaseBuff* GetScriptBaseBuff()
			{
				IS_NULL_RETN( this, 0, nullptr );
				IS_NULL_RETN( this, static_cast<int>(Offsets::BuffInstance::ScriptBaseBuff), nullptr );

				return *reinterpret_cast<ScriptBaseBuff**>(this + static_cast<int>(Offsets::BuffInstance::ScriptBaseBuff));
			}

			BuffScriptInstance* GetBuffScriptInstance()
			{
				IS_NULL_RETN( this, 0, nullptr );

				auto static const pGetBuffScriptInstance = MAKE_RVA( Offsets::BuffManager::BaseScriptBuff );

				__asm
				{
					mov ecx, this;
					call [pGetBuffScriptInstance]
				}
			}

			int GetCount()
			{
				IS_NULL_RETN( this, static_cast<int>(Offsets::BuffInstance::Count), 0 );
				IS_NULL_RETN( this, 0x1C, 0 );
				IS_NULL_RETN( this, 0x18, 0 );

				if ((1 << *reinterpret_cast<DWORD *>(this)) & 0x4100000)
					return *reinterpret_cast<DWORD*>(this + static_cast<int>(Offsets::BuffInstance::Count));

				return (*reinterpret_cast<DWORD *>(this + 0x1C) - *reinterpret_cast<DWORD *>(this + 0x18)) >> 3;
			}

			int GetCountAlt()
			{
				IS_NULL_RETN( this, 0x20, 0 );
				IS_NULL_RETN( this, 0x1C, 0 );

				return (*reinterpret_cast<DWORD *>(this + 0x20) - *reinterpret_cast<DWORD *>(this + 0x1C)) >> 3;
			}


			//MAKE_GET( Count, int, Offsets::BuffInstance::Count );
			MAKE_GET( StartTime, float, Offsets::BuffInstance::StartTime );
			MAKE_GET( EndTime, float, Offsets::BuffInstance::EndTime );
			MAKE_GET( Type, uint, Offsets::BuffInstance::Type );
			MAKE_GET( Index, byte, Offsets::BuffInstance::Index );
			MAKE_GET( IsVisible, bool, Offsets::BuffInstance::IsVisible );
		};
	}
}