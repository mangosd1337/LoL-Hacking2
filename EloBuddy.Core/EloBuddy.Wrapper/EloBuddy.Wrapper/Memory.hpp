#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/Memory.h"

namespace EloBuddy
{
	public ref class Memory
	{
	public:
		generic<typename T>
		static T Read(int address)
		{
			return (T) Native::Memory::Read<byte>(address);
		}

		/*generic<typename T>
		static void Write(int address, T value)
		{
			Native::Memory::Write<T>(address, value);
		}*/
	};
}