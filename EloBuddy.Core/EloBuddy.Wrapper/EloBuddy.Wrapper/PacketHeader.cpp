#include "Stdafx.h"
#include "PacketHeader.h"
#include "GamePacket.h"

namespace EloBuddy
{
	namespace Networking
	{
		PacketHeader::PacketHeader(GamePacket^ packet)
		{
			if (packet->Data->Length < 0xA)
			{
				throw gcnew Exception("Tried to access the header of a Packet that does not contain atleast 10 bytes.");
			}

			this->m_algorithm = gcnew HashAlgorithm( IntPtr( BitConverter::ToInt32( packet->Data, 0 ) ) );
			this->m_header = BitConverter::ToInt16( packet->Data, 4 );
			this->m_networkId = BitConverter::ToUInt32( packet->Data, 6 );
		}
	}
}