#include "stdafx.h"
#include "Game.hpp"
#include "Drawing.h"

#define HEADER_SIZE 0xA

namespace EloBuddy
{
	static Game::Game()
	{
		ATTACH_DOMAIN();

		ATTACH_EVENT
		(
			GameWndProc,
			1, Native::OnWndProc, HWND, UINT, WPARAM, LPARAM
		);
		ATTACH_EVENT
		(
			GameUpdate,
			2, Native::OnGameUpdate
		);
		ATTACH_EVENT
		(
			GameEnd,
			4, Native::OnGameEnd
		);
		ATTACH_EVENT
		(
			GameLoad,
			26, Native::OnGameLoad
		);
		ATTACH_EVENT
		(
			GameSendPacket,
			19, Native::OnGameSendPacket, C2S_ENetPacket*, uint, uint, DWORD
		);
		ATTACH_EVENT
		(
			GameProcessPacket,
			20, Native::OnGameProcessPacket, S2C_ENetPacket*, uint, uint, DWORD
		);
		ATTACH_EVENT
		(
			GamePreTick,
			31, Native::OnGamePreTick
		);
		ATTACH_EVENT
		(
			GameTick,
			32, Native::OnGameTick
		);
		ATTACH_EVENT
		(
			GamePostTick,
			33, Native::OnGamePostTick
		);
		ATTACH_EVENT
		(
			GameAfk,
			34, Native::OnGameAfk
		);
		ATTACH_EVENT
		(
			GameDisconnect,
			35, Native::OnGameDisconnect
		);
		ATTACH_EVENT
		(
			GameNotify,
			37, Native::OnGameNotify, uint, int
		);
	}

	void Game::DomainUnloadEventHandler( Object^, EventArgs^ )
	{
		DETACH_EVENT
		(
			GameWndProc,
			1, Native::OnWndProc, HWND, UINT, WPARAM, LPARAM
		);
		DETACH_EVENT
		(
			GameUpdate,
			2, Native::OnGameUpdate
		);
		DETACH_EVENT
		(
			GameEnd,
			4, Native::OnGameEnd
		);
		DETACH_EVENT
		(
			GameLoad,
			26, Native::OnGameLoad
		);
		DETACH_EVENT
		(
			GameSendPacket,
			19, Native::OnGameSendPacket, C2S_ENetPacket*, uint, uint, DWORD
		);
		DETACH_EVENT
		(
			GameProcessPacket,
			20, Native::OnGameProcessPacket, S2C_ENetPacket*, uint, uint, DWORD
		);
		DETACH_EVENT
		(
			GamePreTick,
			31, Native::OnGamePreTick
		);
		DETACH_EVENT
		(
			GameTick,
			32, Native::OnGameTick
		);
		DETACH_EVENT
		(
			GamePostTick,
			33, Native::OnGamePostTick
		);
		DETACH_EVENT
		(
			GameAfk,
			34, Native::OnGameAfk
		);
		DETACH_EVENT
		(
			GameDisconnect,
			35, Native::OnGameDisconnect
		);
		DETACH_EVENT
		(
			GameNotify,
			37, Native::OnGameNotify, uint, int
		);
	}

	bool Game::OnGameWndProcNative( HWND HWnd, UINT message, WPARAM WParam, LPARAM LParam )
	{
		bool process = true;

		START_TRACE
			auto args = gcnew WndEventArgs( HWnd, message, WParam, LParam );
			for each (auto eventHandle in GameWndProcHandlers->ToArray( ))
			{
				START_TRACE
					eventHandle( args );
				END_TRACE
			}

			if (!args->Process)
				process = false;
		END_TRACE

		return process;
	}

	void Game::OnGameUpdateNative()
	{
		START_TRACE
			for each (auto eventHandle in GameUpdateHandlers->ToArray( ))
			{
				START_TRACE
					eventHandle( EventArgs::Empty );
				END_TRACE
			}
		END_TRACE
	}

	void Game::OnGamePreTickNative()
	{
		START_TRACE
			for each (auto eventHandle in GamePreTickHandlers->ToArray())
			{
				START_TRACE
					eventHandle( EventArgs::Empty );
				END_TRACE
			}
		END_TRACE
	}

	void Game::OnGameTickNative( )
	{
		START_TRACE
			for each (auto eventHandle in GameTickHandlers->ToArray())
			{
				START_TRACE
					eventHandle( EventArgs::Empty );
				END_TRACE
			}
		END_TRACE
	}

	void Game::OnGamePostTickNative( )
	{
		START_TRACE
			for each (auto eventHandle in GamePostTickHandlers->ToArray())
			{	
				START_TRACE
					eventHandle( EventArgs::Empty );
				END_TRACE
			}
		END_TRACE
	}

	void Game::OnGameEndNative( int winningTeam )
	{
		START_TRACE
			for each (auto eventHandle in GameEndHandlers->ToArray( ))
			{
				START_TRACE
					eventHandle( gcnew GameEndEventArgs( winningTeam ) );
				END_TRACE
			}
		END_TRACE
	}

	void Game::OnGameLoadNative()
	{
		START_TRACE
			for each (auto eventHandle in GameLoadHandlers->ToArray( ))
			{
				START_TRACE
					eventHandle( EventArgs::Empty );
				END_TRACE
			}
		END_TRACE
	}

	bool Game::OnGameSendPacketNative( C2S_ENetPacket* packet, uint channel, uint protocol, DWORD hashAlgorithm )
	{
		bool process = true;

		START_TRACE
			auto packetSize = *packet->GetSize();

			if (packetSize > 0xFFF || packetSize < 0xA)
			{
				return true;
			}

			// local vars
			auto packetOpCode = packet->GetCommand();

			// Raw packet
			auto rawPacketArray = gcnew array<byte>( packetSize );
			auto srcRawPacket = IntPtr( reinterpret_cast<void*>(packetOpCode) );
			Marshal::Copy( srcRawPacket, rawPacketArray, 4, packetSize - 4 );

			// Data only, 0xA and beyond
			auto packetArray = gcnew array<byte>( packetSize - HEADER_SIZE );
			auto srcDataPacket = IntPtr( reinterpret_cast<void*>(packet->GetData()) );
			Marshal::Copy( srcDataPacket, packetArray, 0, packetSize - HEADER_SIZE );

			// Copy fixed hashAlgorithm
			Array::Copy( BitConverter::GetBytes(static_cast<int>(hashAlgorithm)), rawPacketArray, 4 );

			// Trigger event
			if (m_callBackDictionary->ContainsKey( *packetOpCode ))
			{
				for each(auto packetCallback in m_callBackDictionary [*packetOpCode]->ToArray())
				{
					if (packetCallback->m_callbackType == PacketCallbackType::BothWays
						|| packetCallback->m_callbackType == PacketCallbackType::Send)
					{
						packetCallback->m_action(gcnew GamePacket(rawPacketArray));
					}
				}
			}

			auto args = gcnew GamePacketEventArgs( *packet->GetCommand(), *packet->GetNetworkId(), packetArray, rawPacketArray, IntPtr( (int) hashAlgorithm), static_cast<PacketChannel>(channel), static_cast<PacketProtocolFlags>(protocol) );
			for each (auto eventHandle in GameSendPacketHandlers->ToArray( ))
			{
				START_TRACE
					eventHandle( args );

					if (!args->Process)
					{
						process = false;
					}
				END_TRACE
			}
		END_TRACE

		return process;
	}

	bool Game::OnGameProcessPacketNative( S2C_ENetPacket* packet, uint channel, uint protocol, DWORD hashAlgorithm )
	{
		bool process = true;

		START_TRACE
			auto packetSize = *packet->GetSize();

			if (packetSize > 0xFFF || packetSize < 0xA)
			{
				return true;
			}

			// local vars
			auto packetOpCode = packet->GetCommand();

			// Raw packet
			auto rawPacketArray = gcnew array<byte>( packetSize );
			auto srcRawPacket = IntPtr( reinterpret_cast<void*>(packetOpCode) );
			Marshal::Copy( srcRawPacket, rawPacketArray, 4, packetSize - 4  );

			// Data only, 0xA and beyond
			auto packetArray = gcnew array<byte>( packetSize - HEADER_SIZE );
			auto srcDataPacket = IntPtr( reinterpret_cast<void*>(packet->GetData()) );
			Marshal::Copy( srcDataPacket, packetArray, 0, packetSize - HEADER_SIZE );

			// Copy fixed hashAlgorithm
			Array::Copy( BitConverter::GetBytes( static_cast<int>(hashAlgorithm) ), rawPacketArray, 4 );

			// Trigger event
			if (m_callBackDictionary->ContainsKey( *packetOpCode ))
			{
				for each(auto packetCallback in m_callBackDictionary [*packetOpCode]->ToArray())
				{
					if (packetCallback->m_callbackType == PacketCallbackType::BothWays
						|| packetCallback->m_callbackType == PacketCallbackType::Send)
					{
						packetCallback->m_action( gcnew GamePacket( rawPacketArray ) );
					}
				}
			}

			auto args = gcnew GamePacketEventArgs( *packet->GetCommand(), 0, packetArray, rawPacketArray, IntPtr( (int) hashAlgorithm ), static_cast<PacketChannel>(channel), static_cast<PacketProtocolFlags>(protocol) );
			for each (auto eventHandle in GameProcessPacketHandlers->ToArray( ))
			{
				START_TRACE
					eventHandle( args );

					if (!args->Process)
					{
						process = false;
					}
				END_TRACE
			}
		END_TRACE

		return process;
	}

	bool Game::OnGameAfkNative()
	{
		bool process = false;

		START_TRACE
			auto args = gcnew GameAfkEventArgs();
			for each (auto eventHandle in GameAfkHandlers->ToArray())
			{
				START_TRACE
					eventHandle( args );
				END_TRACE
			}

			if (args->Process)
				process = true;
		END_TRACE

		return process;
	}

	void Game::OnGameDisconnectNative()
	{
		START_TRACE
			for each (auto eventHandle in GameDisconnectHandlers->ToArray())
			{
				START_TRACE
					eventHandle( EventArgs::Empty );
				END_TRACE
			}
		END_TRACE
	}

	void Game::SendPacket( array<byte>^ packetData, PacketChannel channel, PacketProtocolFlags flag )
	{
		Game::SendPacket( packetData, channel, flag, true );
	}

	void Game::SendPacket( array<byte>^ packetData, PacketChannel channel, PacketProtocolFlags flag, bool triggerEvent )
	{
		auto clientNode = NetClient::Get();
		if (clientNode != nullptr && packetData->Length > 0xA)
		{
			auto nativePacket = new byte [packetData->Length];
			for (int i = 0; i < packetData->Length; i++)
			{
				nativePacket [i] = packetData [i];
			}

			auto hashAlgorithm = BitConverter::ToInt32( packetData, 0 );

			clientNode->SendToServer( nativePacket, packetData->Length, hashAlgorithm, (ENETCHANNEL) channel, (ESENDPROTOCOL) flag, triggerEvent );
			delete nativePacket;
		}
	}

	void Game::ProcessPacket( array<byte>^ packetData, PacketChannel channel )
	{
		Game::ProcessPacket( packetData, channel, true );
	}

	void Game::ProcessPacket( array<byte>^ packetData, PacketChannel channel, bool triggerEvent )
	{
		auto clientNode = ClientNode::GetInstance();
		if (clientNode != nullptr && packetData->Length > 0xA)
		{
			auto nativePacket = new byte [packetData->Length];
			for (int i = 0; i < packetData->Length; i++)
			{
				nativePacket [i] = packetData [i];
			}

			auto hashAlgorithm = BitConverter::ToInt32( packetData, 0 );

			clientNode->ProcessClientPacket( nativePacket, packetData->Length, hashAlgorithm, (ENETCHANNEL) channel, triggerEvent );
			delete nativePacket;
		}
	}

	void Game::OnGameNotifyNative(uint networkId, int eventId)
	{
		START_TRACE
			auto args = gcnew GameNotifyEventArgs( static_cast<GameEventId>(eventId), networkId );

			for each (auto eventHandle in GameNotifyHandlers->ToArray())
			{
				START_TRACE
					eventHandle( args );
				END_TRACE
			}
		END_TRACE
	}

	Vector2 Game::CursorPos2D::get()
	{
		auto pwHud = Native::pwHud::GetInstance();
		if (pwHud != nullptr)
		{
			auto hudManager = pwHud->GetHudManager();
			if (hudManager != nullptr)
			{
				auto nativeVec = hudManager->GetCursorPos2D();
				return Vector2( nativeVec.GetX(), nativeVec.GetY() );
			}
		}

		return Vector2::Zero;
	}

	Vector3 Game::CursorPos::get()
	{
		auto pwHud = Native::pwHud::GetInstance();
		if (pwHud != nullptr)
		{
			auto hudManager = pwHud->GetHudManager();
			if (hudManager != nullptr)
			{
				auto nativeVec = hudManager->GetVirtualCursorPos();
				return Vector3( nativeVec->GetX(), nativeVec->GetZ(), nativeVec->GetY() );
			}
		}

		return Vector3::Zero;
	}

	Vector3 Game::ActiveCursorPos::get()
	{
		auto pwHud = Native::pwHud::GetInstance();
		if (pwHud != nullptr)
		{
			auto hudManager = pwHud->GetHudManager();
			if (hudManager != nullptr)
			{
				auto nativeVec = hudManager->GetActiveVirtualCursorPos();
				return Vector3( nativeVec->GetX(), nativeVec->GetZ(), nativeVec->GetY() );
			}
		}

		return Vector3::Zero;
	}

	String^ Game::Version::get()
	{
		return gcnew String("6.4.0.250"); //temp
		return gcnew String( Native::BuildInfo::GetBuildVersion() );
	}

	String^ Game::BuildDate::get()
	{
		return gcnew String( Native::BuildInfo::GetBuildDate() );
	}

	String^ Game::BuildTime::get()
	{
		return gcnew String( Native::BuildInfo::GetBuildTime() );
	}

	String^ Game::BuildType::get()
	{
		return gcnew String( Native::BuildInfo::GetBuildType() );
	}

	bool Game::LuaDoString( String^ luaString )
	{
		auto returnValue = false;

		/*if (!String::IsNullOrEmpty(luaString))
		{
			auto lua = Lua::GetInstance();
			if (lua != nullptr)
			{
				auto string = Marshal::StringToHGlobalAnsi(luaString);
				auto fnc = lua->MakeFnc(std::string((char*) (string.ToPointer())));
				returnValue = lua->Execute(fnc);
				Marshal::FreeHGlobal(string);
			}
		}*/

		return returnValue;
	}

	bool Game::QuitGame()
	{
		auto game = Native::Game::GetInstance();

		if (game != nullptr)
		{
			return game->QuitGame();
		}

		return false;
	}

	void Game::AddPacketCallback( short opCode, System::Action<GamePacket^>^ action )
	{
		if (!m_callBackDictionary->ContainsKey(opCode))
		{
			m_callBackDictionary->Add( opCode, gcnew List<PacketCallback^>() );
		}

		m_callBackDictionary [opCode]->Add( gcnew PacketCallback (
			PacketCallbackType::BothWays,
			action
		));
	}

	void Game::AddPacketCallback( short opCode, System::Action<GamePacket^>^ action, PacketCallbackType type)
	{
		if (!m_callBackDictionary->ContainsKey( opCode ))
		{
			m_callBackDictionary->Add( opCode, gcnew List<PacketCallback^>() );
		}

		m_callBackDictionary [opCode]->Add( gcnew PacketCallback(
			type,
			action
		));
	}

	float Game::FPS::get()
	{
		auto game = Native::Game::GetInstance();
		if (game != nullptr)
		{
			return static_cast<float>( (1.0 / game->GetFPS()) + 0.5);
		}
		return 0;
	}
}