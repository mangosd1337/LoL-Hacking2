#pragma once
#include "../../EloBuddy.Core/EloBuddy.Core/NetClient.h"

#include "HashAlgorithm.h"
using namespace System;

namespace EloBuddy
{
	namespace Networking
	{
		ref class GamePacket;

		public ref struct PacketHeader
		{
		private:
			HashAlgorithm^ m_algorithm;
			short m_header;
			uint m_networkId;
		public:
			PacketHeader( GamePacket^ packet );

			property HashAlgorithm^ Algorithm
			{
				HashAlgorithm^ get()
				{
					return m_algorithm;
				}
				void set(HashAlgorithm^ algorithm)
				{
					this->m_algorithm = algorithm;
				}
			}

			property short OpCode
			{
				short get()
				{
					return m_header;
				}
				void set(short header)
				{
					this->m_header = header;
				}
			}

			property uint NetworkId
			{
				uint get()
				{
					return m_networkId;
				}
				void set(uint netId)
				{
					this->m_networkId = netId;
				}
			}
		};
	}
}