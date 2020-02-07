#pragma once
#include "Offsets.h"
#include "Macros.h"
#include <string>
#include <vector>
#include "Vector3f.h"

namespace EloBuddy
{
	namespace Native
	{
		class GameObjectVTable;

		#pragma pack(push, 1)
		struct BBox
		{
			float MinimumX;
			float MinimumY;
			float MinimumZ;
			float MaximumX;
			float MaximumY;
			float MaximumZ;
		};

		class
			DLLEXPORT GameObject
		{
		public:
			GameObjectVTable* GetVirtual();

			UnitType GetType();
			short* GetIndex();
			uint* GetNetworkId();

			MAKE_GET( LocalIndex, int, 0x8 );

			Vector3f GetPosition()
			{
				if (this == nullptr)
				{
					return Vector3f(0, 0, 0);
				}

				auto vec = reinterpret_cast<Vector3f*>(this + static_cast<int>(Offsets::GameObject::Position));
				if (vec == nullptr)
				{
					return Vector3f(0, 0, 0);
				}

				return Vector3f( vec->GetX(), vec->GetZ(), vec->GetY() );
			}

			Vector3f GetServerPosition()
			{
				if (this == nullptr)
				{
					return Vector3f(0, 0, 0);
				}

				auto vec = reinterpret_cast<Vector3f*>(this + static_cast<int>(Offsets::GameObject::ServerPosition));
				if (vec == nullptr)
				{
					return Vector3f(0, 0, 0);
				}

				return Vector3f( vec->GetX(), vec->GetZ(), vec->GetY() );
			}

			bool IsMissile()
			{
				if (this == nullptr)
				{
					return false;
				}

				return GetType() == UnitType::MissileClient;
			}

			std::string GetName();
			void SetName( std::wstring );

			bool* GetIsDead()
			{
				return reinterpret_cast<bool*>(this + static_cast<int>(Offsets::GameObject::IsDead));
			}

			MAKE_GET( Team, uint, Offsets::GameObject::Team );
			MAKE_GET( IsVisible, bool, Offsets::GameObject::VisibleOnScreen );
			MAKE_GET( VisibleOnScreen, bool, Offsets::GameObject::VisibleOnScreen );
			MAKE_GET( BBox, BBox, Offsets::GameObject::BBox );

			//Lua API
			static void ExportFunctions();
		};
	}
}
