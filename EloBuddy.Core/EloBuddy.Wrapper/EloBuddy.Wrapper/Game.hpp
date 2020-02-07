#include "Stdafx.h"
#pragma once

#include <Windows.h>

#include "../../EloBuddy.Core/EloBuddy.Core/Game.h"
#include "../../EloBuddy.Core/EloBuddy.Core/Core.h"
#include "../../EloBuddy.Core/EloBuddy.Core/RiotClock.h"
#include "../../EloBuddy.Core/EloBuddy.Core/ClientFacade.h"
#include "../../EloBuddy.Core/EloBuddy.Core/ClientCameraPositionClient.hpp"
#include "../../EloBuddy.Core/EloBuddy.Core/EventHandler.h"
#include "../../EloBuddy.Core/EloBuddy.Core/pwConsole.h"
#include "../../EloBuddy.Core/EloBuddy.Core/StaticEnums.h"
#include "../../EloBuddy.Core/EloBuddy.Core/ClientNode.h"
#include "../../EloBuddy.Core/EloBuddy.Core/MissionInfo.h"
#include "../../EloBuddy.Core/EloBuddy.Core/pwHud.h"
#include "../../EloBuddy.Core/EloBuddy.Core/BuildInfo.h"
#include "../../EloBuddy.Core/EloBuddy.Core/Lua.h"
#include "../../EloBuddy.Core/EloBuddy.Core/PacketTypedefs.h"
#include "../../EloBuddy.Core/EloBuddy.Core/NetClient.h"

#include "Macros.hpp"
#include "StaticEnums.h"

//EventArg files
#include "GameWndEventArgs.hpp"
#include "GameUpdateEventArgs.hpp"
#include "GameStartEventArgs.hpp"
#include "GamePacketEventArgs.hpp"
#include "GameEndEventArgs.hpp"
#include "GameAfkEventArgs.hpp"
#include "GameNotifyEventArgs.hpp"

using namespace EloBuddy::Native;
using namespace System;
using namespace System::Text;
using namespace System::Runtime::InteropServices;
using namespace System::Collections::Generic;
using namespace System::Threading;

namespace EloBuddy
{
	MAKE_EVENT_GLOBAL( GameWndProc, WndEventArgs^ args );
	MAKE_EVENT_GLOBAL( GameUpdate, System::EventArgs^ args );
	MAKE_EVENT_GLOBAL( GameEnd, GameEndEventArgs^ args );
	MAKE_EVENT_GLOBAL( GameLoad, System::EventArgs^ args );
	MAKE_EVENT_GLOBAL( GameSendPacket, GamePacketEventArgs^ args );
	MAKE_EVENT_GLOBAL( GameProcessPacket, GamePacketEventArgs^ args );
	MAKE_EVENT_GLOBAL( GamePreTick, EventArgs^ args );
	MAKE_EVENT_GLOBAL( GameTick, EventArgs^ args );
	MAKE_EVENT_GLOBAL( GamePostTick, EventArgs^ args );
	MAKE_EVENT_GLOBAL( GameAfk, GameAfkEventArgs^ args );
	MAKE_EVENT_GLOBAL( GameDisconnect, EventArgs^ args );
	MAKE_EVENT_GLOBAL( GameNotify, GameNotifyEventArgs^ args );

	public ref class PacketCallback
	{
	public:
		PacketCallbackType m_callbackType;
		System::Action<GamePacket^>^ m_action;

		PacketCallback( PacketCallbackType type, System::Action<GamePacket^>^ action)
		{
			this->m_callbackType = type;
			this->m_action = action;
		}
	};

	public ref class Game
	{
	internal:
		MAKE_EVENT_INTERNAL_PROCESS( GameWndProc, (HWND, UINT, WPARAM, LPARAM) );
		MAKE_EVENT_INTERNAL( GameUpdate, () );
		MAKE_EVENT_INTERNAL( GameEnd, (int) );
		MAKE_EVENT_INTERNAL( GameLoad, () );
		MAKE_EVENT_INTERNAL_PROCESS( GameSendPacket, (C2S_ENetPacket*, uint, uint, DWORD) );
		MAKE_EVENT_INTERNAL_PROCESS( GameProcessPacket, (S2C_ENetPacket*, uint, uint, DWORD) );
		MAKE_EVENT_INTERNAL( GamePreTick, () );
		MAKE_EVENT_INTERNAL( GameTick, () );
		MAKE_EVENT_INTERNAL( GamePostTick, () );
		MAKE_EVENT_INTERNAL_PROCESS( GameAfk, () );
		MAKE_EVENT_INTERNAL( GameDisconnect, () );
		MAKE_EVENT_INTERNAL( GameNotify, (uint, int) );

		static int m_lastTick;

		static Dictionary<short, List<PacketCallback^>^>^ m_callBackDictionary = gcnew Dictionary<short, List<PacketCallback^>^>();
	private:
	public:
		MAKE_EVENT_PUBLIC( OnWndProc, GameWndProc );
		MAKE_EVENT_PUBLIC( OnUpdate, GameUpdate );
		MAKE_EVENT_PUBLIC( OnEnd, GameEnd );
		MAKE_EVENT_PUBLIC( OnLoad, GameLoad );
		MAKE_EVENT_PUBLIC( OnSendPacket, GameSendPacket );
		MAKE_EVENT_PUBLIC( OnProcessPacket, GameProcessPacket );
		MAKE_EVENT_PUBLIC( OnPreTick, GamePreTick );
		MAKE_EVENT_PUBLIC( OnTick, GameTick );
		MAKE_EVENT_PUBLIC( OnPostTick, GamePostTick );
		MAKE_EVENT_PUBLIC( OnAfk, GameAfk );
		MAKE_EVENT_PUBLIC( OnDisconnect, GameDisconnect );
		MAKE_EVENT_PUBLIC( OnNotify, GameNotify );

		static Game::Game();
		static void DomainUnloadEventHandler( Object^, EventArgs^ );

		static void SendPacket( array<byte>^ packetData, PacketChannel channel, PacketProtocolFlags flag );
		static void SendPacket( array<byte>^ packetData, PacketChannel channel, PacketProtocolFlags flag, bool triggerEvent );
		static void ProcessPacket( array<byte>^ packetData, PacketChannel channel );
		static void ProcessPacket( array<byte>^ packetData, PacketChannel channel, bool triggerEvent );

		static void AddPacketCallback( short opCode, System::Action<GamePacket^>^ action );
		static void AddPacketCallback( short opCode, System::Action<GamePacket^>^ action, PacketCallbackType callbackType );

		static property float Time
		{
			float get()
			{
				auto riotClock = Native::RiotClock::GetInstance();
				if (riotClock != nullptr)
				{
					return riotClock->GetTime();
				}
				return 0;
			}
		}

		static property int TicksPerSecond
		{
			int get()
			{
				return Native::Game::GetInstance()->GetTPS();
			}
			void set( int ticks )
			{
				Native::Game::GetInstance()->SetTPS( ticks );
			}
		}

		static void Drop()
		{
			Native::Game::FromBehind_LeagueSharp_Sucks();
		}

		static property String^ IP
		{
			String^ get()
			{
				auto clientFacade = Native::ClientFacade::GetInstance();
				if (clientFacade != nullptr)
				{
					return gcnew System::String( clientFacade->GetIP()->c_str() );
				}
				return "Unknown";
			}
		}

		static property String^ Region
		{
			String^ get()
			{
				auto clientFacade = Native::ClientFacade::GetInstance();
				if (clientFacade != nullptr)
				{
					return gcnew String( clientFacade->GetRegion()->c_str() );
				}
				return "Unknown";
			}
		}

		static property int Port
		{
			int get()
			{
				auto clientFacade = Native::ClientFacade::GetInstance();
				if (clientFacade != nullptr)
				{
					return *clientFacade->GetPort();
				}
				return 0;
			}
		}

		static property GameMapId MapId
		{
			GameMapId get()
			{
				auto missionInfo = Native::MissionInfo::GetInstance();
				if (missionInfo != nullptr)
				{
					return (GameMapId) (*missionInfo->GetMapId());
				}

				return GameMapId::SummonersRift;
			}
		}

		static property GameMode Mode
		{
			GameMode get()
			{
				auto clientFacade = Native::ClientFacade::GetInstance();
				if (clientFacade != nullptr)
				{
					return (GameMode) clientFacade->GetGameState();
				}
				return GameMode::Running;
			}
		}

		static property GameType Type
		{
			GameType get()
			{
				auto missionInfo = Native::MissionInfo::GetInstance();
				if (missionInfo != nullptr)
				{
					return (GameType) *missionInfo->GetGameType();
				}
				return GameType::Normal;
			}
		}

		static property int Ping
		{
			int get()
			{
				auto netClient = Native::NetClient::Get();
				if (netClient != nullptr)
				{
					auto virtualNetClient = netClient->GetVirtual();
					if (virtualNetClient != nullptr)
					{
						return virtualNetClient->GetPing();
					}
				}
				return 0;
			}
		}

		static property bool IsCustomGame
		{
			bool get()
			{
				auto missionInfo = Native::MissionInfo::GetInstance();
				if (missionInfo != nullptr)
				{
					return *missionInfo->GetGameId() == 0;
				}
				return false;
			}
		}

		static property uint GameId
		{
			uint get()
			{
				auto clientFacade = Native::ClientFacade::GetInstance();
				if (clientFacade != nullptr)
				{
					return *clientFacade->GetGameId();
				}
				return 0;
			}
		}

		static bool LuaDoString( String^ lua );
		static bool QuitGame();

		static property bool IsWindowFocused
		{
			bool get()
			{
				auto pwHud = Native::pwHud::GetInstance();
				if (pwHud != nullptr)
				{
					return *pwHud->GetIsWindowFocused();
				}
				return false;
			}
		}

		MAKE_STATIC_PROPERTY( Version, String^ );
		MAKE_STATIC_PROPERTY( BuildDate, String^ );
		MAKE_STATIC_PROPERTY( BuildTime, String^ );
		MAKE_STATIC_PROPERTY( BuildType, String^ );
		MAKE_STATIC_PROPERTY( CursorPos2D, SharpDX::Vector2 );
		MAKE_STATIC_PROPERTY( CursorPos, SharpDX::Vector3 );
		MAKE_STATIC_PROPERTY( ActiveCursorPos, SharpDX::Vector3 );
		MAKE_STATIC_PROPERTY(FPS, float);
	};
}