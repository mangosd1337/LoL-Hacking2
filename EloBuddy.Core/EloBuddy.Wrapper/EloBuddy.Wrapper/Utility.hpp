#pragma once

#include "Macros.hpp"
#include "NavMesh.h"
#include "Drawing.h"
#include "NavMesh.h"
#include "NavMeshCell.h"
#include "AllUnits.h"

using namespace System::Runtime::CompilerServices;
using namespace SharpDX;

#define EXTEND(NAME, RETURN_TYPE, METHOD_BODY, ...) [ExtensionAttribute] static RETURN_TYPE NAME (__VA_ARGS__) { METHOD_BODY; }

namespace EloBuddy
{
	[ExtensionAttribute]
	public ref class Utility abstract sealed
	{
	public:
		EXTEND(Add, byte,
			return num1 + num2,
			byte num1, byte num2);

		EXTEND(Dec, byte,
			return num1 - 1,
			byte num1);

		EXTEND(Inc, byte,
			return num1 + 1,
			byte num1);

		EXTEND(Not, byte,
			return ~num,
			byte num);

		EXTEND(Rol, uint,
			return (value << count) | (value >> (32 - count)),
			uint value, int count);

		EXTEND(Rol, int,
			return (value << count) | (value >> (32 - count)),
			int value, int count);

		EXTEND(Rol, ushort,
			return (value << count) | (value >> (16 - count)),
			ushort value, int count);

		EXTEND(Rol, byte,
			return (value << count) | (value >> (8 - count)),
			byte value, int count);

		EXTEND(Ror, uint,
			return (value >> count) | (value << (32 - count)),
			uint value, int count);

		EXTEND(Ror, int,
			return (value >> count) | (value << (32 - count)),
			int value, int count);

		EXTEND(Ror, ushort,
			return (value >> count) | (value < (16 - count)),
			ushort value, int count);

		EXTEND(Ror, byte,
			return (value >> count) | (value << (8 - count)),
			byte value, int count);

		EXTEND(Ror, int,
			return num >> position & ((1 << length) - 1),
			int num, int position, int length);

		EXTEND(Sub, byte,
			return num1 - num2,
			byte num1, byte num2);

		EXTEND(Xor, byte,
			return num1 ^ num2,
			byte num1, byte num2);
	};
}