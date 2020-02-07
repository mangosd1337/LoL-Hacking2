#pragma once
#include "Stdafx.h"

#include "GamePacket.h"

using namespace EloBuddy::Networking;

typedef unsigned char byte;
typedef unsigned int uint;

namespace EloBuddy
{
	public ref class GamePacketEventArgs : public System::EventArgs
	{
	private:
		PacketChannel m_channel;
		PacketProtocolFlags m_flag;
		uint m_networkId;
		short m_opCode;
		array<byte>^ m_packetData;
		array<byte>^ m_rawPacket;
		IntPtr m_hashAlgorithm;
		bool m_process;
	public:
		delegate void GamePacketEvent( GamePacketEventArgs^ args );

		GamePacketEventArgs( short opCode, uint networkId, array<byte>^ packetData, array<byte>^ rawPacketData, IntPtr hashAlgorithm, PacketChannel channel, PacketProtocolFlags flag )
		{
			this->m_channel = channel;
			this->m_flag = flag;
			this->m_opCode = opCode;
			this->m_networkId = networkId;
			this->m_process = true;
			this->m_packetData = packetData;
			this->m_rawPacket = rawPacketData;
			this->m_hashAlgorithm = hashAlgorithm;
		}

		property uint NetworkId
		{
			uint get()
			{
				return this->m_networkId;
			}
		}

		property short OpCode
		{
			short get()
			{
				return this->m_opCode;
			}
		}

		property array<byte>^ PacketData
		{
			array<byte>^ get()
			{
				return this->m_packetData;
			}
		}

		property array<byte>^ RawPacketData
		{
			array<byte>^ get()
			{
				return this->m_rawPacket;
			}
		}

		property IntPtr HashAlgorithm
		{
			IntPtr get()
			{
				return this->m_hashAlgorithm;
			}
		}

		property PacketChannel Channel
		{
			PacketChannel get()
			{
				return this->m_channel;
			}
		}

		property PacketProtocolFlags ProtocolFlag
		{
			PacketProtocolFlags get()
			{
				return this->m_flag;
			}
		}

		property GamePacket^ GamePacket
		{
			EloBuddy::Networking::GamePacket^ get()
			{
				return gcnew EloBuddy::Networking::GamePacket( this->m_rawPacket );
			}
		}

		property bool Process
		{
			bool get()
			{
				return this->m_process;
			}
			void set( bool value )
			{
				this->m_process = value;
			}
		}
	};
}