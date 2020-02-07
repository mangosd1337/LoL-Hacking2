#include "Stdafx.h"

#include "../../EloBuddy.Core/EloBuddy.Core/Game.h"

#include "PacketHeader.h"
#include "GamePacket.h"
#include "GamePacketEventArgs.hpp"
#include "Game.hpp"

namespace EloBuddy
{
	namespace Networking
	{
		GamePacket::GamePacket()
		{
			this->SetPacketHeader();
		}

		GamePacket::GamePacket( IntPtr algorithm, short header, int networkId )
		{
			this->GamePacket::GamePacket();

			this->LoadData( gcnew array<byte> {} );
			this->Write<Int32>( static_cast<Int32>(algorithm) );
			this->Write<short>( header );
			this->Write<Int32>( networkId );
		}

		GamePacket::GamePacket( IntPtr algorithm, short header )
		{
			this->GamePacket::GamePacket();

			this->LoadData( gcnew array<byte> {} );
			this->Write<Int32>( static_cast<Int32>(algorithm) );
			this->Write<short>( header );
			this->Write<Int32>( 0 );
		}

		GamePacket::GamePacket( Int32 algorithm, short header, int networkId )
		{
			this->GamePacket::GamePacket();

			this->LoadData( gcnew array<byte> {} );
			this->Write<Int32>( algorithm );
			this->Write<short>( header );
			this->Write<Int32>( networkId );
		}

		GamePacket::GamePacket( Int32 algorithm, short header )
		{
			this->GamePacket::GamePacket();

			this->LoadData( gcnew array<byte> {} );
			this->Write<Int32>( algorithm );
			this->Write<short>( header );
			this->Write<Int32>( 0 );
		}

		GamePacket::GamePacket( HashAlgorithm^ algorithm, short header, uint networkId )
		{
			this->GamePacket::GamePacket();

			this->LoadData( gcnew array<byte> {} );
			this->Write<Int32>( static_cast<Int32>(algorithm->Address) );
			this->Write<short>( header );
			this->Write<Int32>( networkId );
		}

		GamePacket::GamePacket(PacketHeader^ header)
		{
			this->SetHeader( header );
		}

		GamePacket::GamePacket( array<byte>^ data )
		{
			this->LoadData( data );
		}

		GamePacket::GamePacket( GamePacketEventArgs^ eventArgs )
		{
			//this->LoadData( gcnew array<byte> {} );
			//this->Write<Int32>( static_cast<Int32>(eventArgs->HashAlgorithm) );
			//this->Write<short>( eventArgs->OpCode );
			//this->Write<uint>( eventArgs->NetworkId );
		}

		void GamePacket::SetHeader(PacketHeader^ header)
		{
			this->bw->BaseStream->Position = 0;
			this->Write<Int32>( static_cast<Int32>(header->Algorithm->Address) );
			this->Write<short>( header->OpCode );
			this->Write<uint>( header->NetworkId );
		}

		void GamePacket::Send( PacketChannel channel, PacketProtocolFlags flags )
		{
			Game::SendPacket( this->ms->ToArray(), channel, flags );
		}

		void GamePacket::Send()
		{
			Game::SendPacket( this->ms->ToArray(), PacketChannel::C2S, PacketProtocolFlags::Reliable );
		}

		void GamePacket::Process( PacketChannel channel )
		{
			Game::ProcessPacket( this->ms->ToArray(), channel );
		}

		void GamePacket::Process()
		{
			Game::ProcessPacket( this->ms->ToArray(), PacketChannel::S2C );
		}

		void GamePacket::LoadData( array<byte>^ data )
		{
			this->GamePacket::GamePacket();

			// DO NOT CHANGE
			if (data->Length > 0)
			{
				this->ms = gcnew MemoryStream(data);
			} else
			{
				this->ms = gcnew MemoryStream();
			}

			this->br = gcnew BinaryReader( ms );
			this->bw = gcnew BinaryWriter( ms );

			this->br->BaseStream->Position = 0;
			this->bw->BaseStream->Position = 0;
		}

		void GamePacket::SetPacketHeader()
		{
			this->m_packetChannel = PacketChannel::C2S;
			this->m_packetFlags = PacketProtocolFlags::Reliable;
		}

		void GamePacket::SetPacketHeader( PacketChannel channel, PacketProtocolFlags flags )
		{
			this->m_packetChannel = channel;
			this->m_packetFlags = flags;
		}
	}
}