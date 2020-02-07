#pragma once
#include "GameObject.h"
#include "Obj_AI_Base.h"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT MissileClientData
		{
		public:
			SpellData* GetSData()
			{
				if (this != nullptr)
				{
					return reinterpret_cast<SpellData*>(this);
				}

				return nullptr;
			}
		};

		class
			DLLEXPORT MissileClient : GameObject
		{
		public:
			MAKE_GET( LaunchPos, Vector3f, Offsets::Obj_SpellMissile::LaunchPos );
			MAKE_GET( DestPos, Vector3f, Offsets::Obj_SpellMissile::DestPos );
			MAKE_GET( MissileData, MissileClientData*, Offsets::MissileClient::MissileClientData );

			short* GetSpellCaster()
			{
				return reinterpret_cast<short*>(this + static_cast<int>(Offsets::MissileClient::SpellCaster));
			}

			short* GetTarget()
			{
				return reinterpret_cast<short*>(this + static_cast<int>(Offsets::MissileClient::TargetId));
			}
		};
	}
}
