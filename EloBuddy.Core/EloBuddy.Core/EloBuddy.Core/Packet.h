#include "Utils.h"

namespace EloBuddy
{
	namespace Native
	{
		class DLLEXPORT Packet
		{
		public:
			byte* PacketData = new byte[];
			int ByteCounter = 0;

			PacketChannel Channel = PacketChannel::C2S;
			PacketProtocolFlags ProtocolFlag = PacketProtocolFlags::Reliable;

			void AddByte(byte);
			void AddByte(int);
			void AddByte(int*);
			void AddByte(float);
			void SetChannel(PacketChannel Channel);
			void SetChannel(int Channel);
			void SetPacketFlag(PacketProtocolFlags Flag);
			void SetPacketFlag(int Flag);
		};
	}
}