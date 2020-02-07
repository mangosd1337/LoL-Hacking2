#pragma once
#include "GameObject.h"
#include "Obj_AI_Base.h"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT SpellMissileData
		{
		public:
			SpellData* GetSData()
			{
				return reinterpret_cast<SpellData*>(this + 0xD8);
			}

			MAKE_GET( SpellCaster, Obj_AI_Base, Offsets::SpellMissileData::SpellCaster );
			MAKE_GET( CastInfo, SpellCastInfo, Offsets::SpellMissileData::CastInfo );
		};

		class
			DLLEXPORT Obj_SpellMissile : GameObject
		{
		public:
			MAKE_GET( LaunchPos, Vector3f, Offsets::Obj_SpellMissile::LaunchPos );
			MAKE_GET( DestPos, Vector3f, Offsets::Obj_SpellMissile::DestPos );
			MAKE_GET( MissilePath, std::vector<Vector3f>, Offsets::Obj_SpellMissile::Path );
			MAKE_GET( MissileData, SpellMissileData, Offsets::Obj_SpellMissile::MissileData );

			SpellData* GetSData()
			{
				return reinterpret_cast<SpellData*>(*reinterpret_cast<DWORD**>(this + static_cast<int>(Offsets::Obj_SpellMissile::SData)) + 0xD);
			}

			ushort GetSpellCaster()
			{
				__try
				{
					return (*reinterpret_cast<ushort*>(this + static_cast<int>(Offsets::Obj_SpellMissile::SpellCaster))) & 0xFFFF;
				}
				__except (EXCEPTION_EXECUTE_HANDLER)
				{
					return 0;
				}
			}

			ushort Virtual_GetTarget()
			{
				__try
				{
					return (*reinterpret_cast<ushort*>(this + static_cast<int>(Offsets::Obj_SpellMissile::TargetId))) & 0xFFFF;
				}
				__except (EXCEPTION_EXECUTE_HANDLER)
				{
					return 0;
				}
			}

			Vector3f* GetPositionAfterTime( float elapsedTime )
			{
				return new Vector3f( 0, 0, 0 );
			}
		};
	}
}
