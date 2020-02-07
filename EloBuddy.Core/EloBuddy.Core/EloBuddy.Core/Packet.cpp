#include "stdafx.h"
#include "Packet.h"

namespace EloBuddy
{
	namespace Native
	{
		void
		Packet::SetChannel(PacketChannel Channel)
		{
			Packet::Channel = Channel;
		}

		void
		Packet::SetChannel(int Channel)
		{
			Packet::SetChannel((PacketChannel) (Channel));
		}

		void
		Packet::SetPacketFlag(PacketProtocolFlags Flag)
		{
			Packet::ProtocolFlag = Flag;
		}

		void
		Packet::SetPacketFlag(int Flag)
		{
			Packet::SetPacketFlag((PacketProtocolFlags)Flag);
		}

		void
		Packet::AddByte(byte Byte)
		{
			Packet::PacketData[Packet::ByteCounter] = (byte)Byte;
			Packet::ByteCounter++;
		}

		void 
		Packet::AddByte(int Value)
		{
			for (int i = 0; i < 4; i++)
			{
				Packet::PacketData[Packet::ByteCounter] = (Value >> (i * 8));
				Packet::ByteCounter++;
			}
		}

		void 
		Packet::AddByte(int* Value)
		{
			int value = (int)Value;

			for (int i = 0; i < 4; i++)
			{
				Packet::PacketData[Packet::ByteCounter] = (value >> (i * 8));
				Packet::ByteCounter++;
			}
		}


		void
		Packet::AddByte(float Value)
		{
			byte* data = (byte*)(&Value);

			for (int i = 0; i != sizeof(float); i++)
			{
				Packet::PacketData[Packet::ByteCounter] = data[i];
				Packet::ByteCounter++;
			}
		}
	}
}