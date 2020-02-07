#include "stdafx.h"
#include "CRepl32Info.h"
#include "Detour.hpp"
#include "GameObject.h"

#define PKT_OFFSET_START 0x20
#define PROP_FLOAT 1
#define PROP_INT   0
#include "EventHandler.h"

namespace EloBuddy
{
	namespace Native
	{
		MAKE_HOOK<convention_type::stdcall_t, int, GameObject*, int, int> CRepl32UpdatePacket;

		struct CRepl32InfoPkt
		{
			char* Repl32InfoName;
		};

		bool CRepl32Info::ApplyHooks()
		{
			CRepl32UpdatePacket.Apply( MAKE_RVA( Offsets::Game::CRepl32InfoUpdatePacket ), [] ( GameObject* sender, int unkn2, int CReplValues ) -> int
			{
				CRepl32InfoPkt* CReplicationPacket = nullptr;

				__asm
				{
					mov CReplicationPacket, ecx
					pushad
				}

				CRepl32UpdatePacket.CallOriginal( sender, unkn2, CReplValues );

				if (CReplicationPacket != nullptr)
				{
					auto replData = *reinterpret_cast<CRepl32InfoPkt***>(CReplicationPacket);
					if (replData != nullptr)
					{
#ifdef _DEBUG_BUILD
						Console::PrintLn( "ReplicationPacket: %p - Ukn2: %p - Values: %p", replData, unkn2, CReplValues );
#endif
						for (auto i = 1; i < PKT_OFFSET_START && replData[i] != nullptr; i++)
						{
							auto propType = *reinterpret_cast<BYTE*>(reinterpret_cast<DWORD>(replData) + i + 0x104);

							auto propName = reinterpret_cast<char*>(replData [i]);
							auto propOffset = replData [i + PKT_OFFSET_START];

							if (propType == PROP_FLOAT)
							{
								auto propFloat = *reinterpret_cast<float*>(sender + reinterpret_cast<DWORD>(propOffset));
#ifdef _DEBUG_BUILD
								Console::PrintLn( "[%s - FLOAT] %04x => %g", propName, propOffset, propFloat );
#endif
								EventHandler<61, OnGameObjectFloatPropertyChange, GameObject*, const char*, float>::GetInstance()->Trigger( sender, propName, propFloat );
							}

							if (propType == PROP_INT)
							{
								auto propInt = *reinterpret_cast<DWORD*>(sender + reinterpret_cast<DWORD>(propOffset));
#ifdef _DEBUG_BUILD
								Console::PrintLn( "[%s - INT]  %04x => %d -> %d", propName, propOffset, propInt );
#endif

								EventHandler<62, OnGameObjectIntegerPropertyChange, GameObject*, const char*, int>::GetInstance()->Trigger( sender, propName, propInt );
							}

						}
					}
				}

				__asm popad;
			} );

			return CRepl32UpdatePacket.IsApplied();
		}
	}
}