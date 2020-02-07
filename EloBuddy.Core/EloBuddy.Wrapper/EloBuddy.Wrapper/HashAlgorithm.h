#pragma once
#include "../../EloBuddy.Core/EloBuddy.Core/NetClient.h"

using namespace System;

namespace EloBuddy
{
	namespace Networking
	{
		public ref class HashAlgorithm
		{
		private:
			IntPtr m_address;
		public:
			HashAlgorithm(IntPtr address)
			{
				m_address = address;
			}

			property IntPtr Address
			{
				IntPtr get()
				{
					return this->m_address;
				}
				void set(IntPtr addr)
				{
					this->m_address = addr;
				}
			}

			array<byte>^ Simulate()
			{
				return nullptr;
			}
		};
	}
}