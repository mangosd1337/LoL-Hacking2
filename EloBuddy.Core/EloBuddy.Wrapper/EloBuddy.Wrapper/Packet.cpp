#include "stdafx.h"

#include "../../EloBuddy.Core/EloBuddy.Core/Game.h"
#include "../../EloBuddy.Core/EloBuddy.Core/Packet.h"

#include "Packet.h"

namespace EloBuddy
{
	Packet::~Packet()
	{
		Native::Packet* nativePacket = new Native::Packet();

		nativePacket->ByteCounter = this->ByteCounter;
		nativePacket->PacketData = this->PacketData;
		nativePacket->SetPacketFlag((int)this->ProtocolFlag);
		nativePacket->SetChannel((int)this->Channel);

		Native::Game::SendPacket(nativePacket);
	}

	void Packet::AddByte(byte b)
	{
		this->PacketData[this->ByteCounter] = (byte)b;
		this->ByteCounter++;
	}

	void Packet::AddInt(int b)
	{
		for (int i = 0; i < 4; i++)
		{
			this->PacketData[this->ByteCounter] = (b >> (i * 8));
			this->ByteCounter++;
		}
	}

	void Packet::AddFloat(float f)
	{
		byte* data = (byte*)(&f);

		for (int i = 0; i != sizeof(float); i++)
		{
			this->PacketData[this->ByteCounter] = data[i];
			this->ByteCounter++;
		}
	}
}