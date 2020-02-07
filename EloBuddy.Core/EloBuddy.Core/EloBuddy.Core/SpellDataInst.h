#pragma once
#include "Utils.h"
#include "SpellData.h"

namespace EloBuddy
{
	namespace Native
	{
		#pragma pack(push, 1)
		class
			DLLEXPORT SpellDataInst
		{
		public:
			MAKE_GET( Level, int, Offsets::SpellDataInst::Level );
			MAKE_GET( CooldownExpires, float, Offsets::SpellDataInst::CooldownExpires );
			MAKE_GET( Ammo, int, Offsets::SpellDataInst::Ammo );
			MAKE_GET( AmmoRechargeStart, int, Offsets::SpellDataInst::AmmoRechargeStart );
			MAKE_GET( ToggleState, int, Offsets::SpellDataInst::ToggleState );
			MAKE_GET( Cooldown, float, Offsets::SpellDataInst::Cooldown );

			SpellData* __stdcall GetSData();

			std::string* GetName()
			{
				return this->GetSData()->GetName();
			}
		};
	}
}
