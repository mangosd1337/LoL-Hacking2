#pragma once

#include "GameObject.hpp"


namespace EloBuddy
{
	public enum class WardType
	{
		StealthWard,
		VisionWard,
		WrigglesLantern,
		WardingTotem,
		GreaterTotem,
		GreaterStealthTotem,
		GreaterVisionTotem,
		Unknown
	};

	public ref class Obj_Ward : public Obj_AI_Base {
	public:
		Obj_Ward( ushort index, uint networkId, Native::GameObject* unit ) : Obj_AI_Base( index, networkId, unit ) {};
		Obj_Ward() {};

		property WardType Type
		{
			WardType get()
			{
				return WardType::Unknown;
			}
		}
	};
}