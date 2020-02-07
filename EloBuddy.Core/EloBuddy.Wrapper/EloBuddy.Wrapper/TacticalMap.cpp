#include "Stdafx.h"
#include "TacticalMap.hpp"

#include "../../EloBuddy.Core/EloBuddy.Core/ObjectManager.h"
#include "../../EloBuddy.Core/EloBuddy.Core/MenuGUI.h"

#include "GameObject.hpp"
#include "ObjectManager.hpp"

namespace EloBuddy
{
	static TacticalMap::TacticalMap()
	{
		ATTACH_DOMAIN();

		ATTACH_EVENT
		(
			TacticalMapPing,
			36, Native::OnTacticalMapPing, Native::Vector3f*, Native::GameObject*, Native::GameObject*, uint
		);
	}

	void TacticalMap::DomainUnloadEventHandler( System::Object^, System::EventArgs^ )
	{
		DETACH_EVENT
		(
			TacticalMapPing,
			36, Native::OnTacticalMapPing, Native::Vector3f*, Native::GameObject*, Native::GameObject*, uint
		);
	}

	bool TacticalMap::OnTacticalMapPingNative(Native::Vector3f* pos, Native::GameObject* srcObject, Native::GameObject* dstObject, uint pingType)
	{
		bool process = true;

		START_TRACE
			GameObject^ managedSource = nullptr;
			GameObject^ managedTarget = nullptr;

			if (srcObject != nullptr)
				managedSource = ObjectManager::CreateObjectFromPointer( srcObject );

			if (dstObject != nullptr)
				managedTarget = ObjectManager::CreateObjectFromPointer( dstObject );

			Vector2 position = Vector2( pos->GetX(), pos->GetY() );

			auto args = gcnew TacticalMapPingEventArgs(position, managedSource, managedTarget, static_cast<PingCategory>(pingType));

			for each (auto eventHandle in TacticalMapPingHandlers->ToArray())
			{
				START_TRACE
					eventHandle( args );

					if (!args->Process)
						process = false;
				END_TRACE
			}
		END_TRACE

		return process;
	}

	Vector2 TacticalMap::WorldToMinimap( Vector3 worldCoord )
	{
		Native::TacticalMap* tacticalMap = Native::TacticalMap::GetInstance();
		float* xOut = new float;
		float* yOut = new float;

		if (tacticalMap != nullptr)
		{
			if (tacticalMap->ToMapCoord(&Native::Vector3f(worldCoord.X, worldCoord.Z, worldCoord.Y), xOut, yOut))
			{
				return Vector2(*xOut, *yOut);
			}
		}

		return Vector2::Zero;
	}

	bool TacticalMap::WorldToMinimap( Vector3 worldCoord, [Out] Vector2% mapCoord )
	{
		Native::TacticalMap* tacticalMap = Native::TacticalMap::GetInstance();
		float* xOut = new float;
		float* yOut = new float;
	

		if (tacticalMap != nullptr)
		{
			if (tacticalMap->ToMapCoord( &Native::Vector3f( worldCoord.X, worldCoord.Z, worldCoord.Y ), xOut, yOut ))
			{
				mapCoord = Vector2( *xOut, *yOut );
				return true;
			}
		}

		return false;
	}

	Vector3 TacticalMap::MinimapToWorld( float x, float y )
	{
		Native::TacticalMap* tacticalMap = Native::TacticalMap::GetInstance();
		if (tacticalMap != nullptr)
		{
			Native::Vector3f* vecOut = tacticalMap->ToWorldCoord( x, y );
			return Vector3( vecOut->GetX(), vecOut->GetY(), vecOut->GetZ() );
		}

		return Vector3::Zero;
	}

	void TacticalMap::SendPing( PingCategory type, GameObject^ target )
	{
		if (target != nullptr)
		{
			Native::MenuGUI::CallCurrentPing( &Native::Vector3f( target->Position.X, target->Position.Y, 0 ), target->NetworkId, static_cast<int>(type) );
		}
	}

	void TacticalMap::SendPing( PingCategory type, Vector2 position )
	{
		Native::MenuGUI::CallCurrentPing( &Native::Vector3f( position.X, position.Y, 0 ), 0, static_cast<int>(type) );
	}

	void TacticalMap::SendPing( PingCategory type, Vector3 position )
	{
		Native::MenuGUI::CallCurrentPing( &Native::Vector3f( position.X, position.Y, 0 ), 0, static_cast<int>(type) );
	}

	void TacticalMap::ShowPing( PingCategory type, GameObject^ target )
	{
		TacticalMap::ShowPing( type, target, false );
	}

	void TacticalMap::ShowPing( PingCategory type, GameObject^ target, bool playSound )
	{
		Native::GameObject* localPlayer = (Native::GameObject*) Native::ObjectManager::GetPlayer();
		if (localPlayer != nullptr)
		{
			Native::GameObject* targetNative = target->GetPtr();
			if (targetNative != nullptr)
			{
				int* indexLocal = localPlayer->GetLocalIndex();
				int* indexTarget = targetNative->GetLocalIndex();

				Native::MenuGUI::PingMiniMap( &Native::Vector3f( target->Position.X, target->Position.Y, 0 ), *indexLocal, *indexTarget, static_cast<int>(type), playSound );
			}
		}
	}

	void TacticalMap::ShowPing( PingCategory type, GameObject^ source, GameObject^ target )
	{
		TacticalMap::ShowPing( type, source, target, false );
	}

	void TacticalMap::ShowPing( PingCategory type, GameObject^ source, GameObject^ target, bool playSound )
	{
		if (source != nullptr && target != nullptr)
		{
			Native::GameObject* sourceNative = source->GetPtr();
			Native::GameObject* targetNative = target->GetPtr();
			if (targetNative != nullptr)
			{
				int* indexSource = sourceNative->GetLocalIndex();
				int* indexTarget = targetNative->GetLocalIndex();

				Native::MenuGUI::PingMiniMap( &Native::Vector3f( target->Position.X, target->Position.Y, 0 ), *indexSource, *indexTarget, static_cast<int>(type), playSound );
			}
		}
	}

	void TacticalMap::ShowPing( PingCategory type, Vector2 position )
	{
		TacticalMap::ShowPing( type, position, false );
	}

	void TacticalMap::ShowPing( PingCategory type, Vector2 position, bool playSound )
	{
		Native::GameObject* localPlayer = (Native::GameObject*) Native::ObjectManager::GetPlayer();
		if (localPlayer != nullptr)
		{
			int* indexLocal = localPlayer->GetLocalIndex();

			Native::MenuGUI::PingMiniMap( &Native::Vector3f( position.X, position.Y, 0 ), *indexLocal, *indexLocal, static_cast<int>(type), playSound );
		}
	}

	void TacticalMap::ShowPing( PingCategory type, Vector3 position )
	{
		TacticalMap::ShowPing( type, position, false );
	}

	void TacticalMap::ShowPing( PingCategory type, Vector3 position, bool playSound )
	{
		Native::GameObject* localPlayer = (Native::GameObject*) Native::ObjectManager::GetPlayer();
		if (localPlayer != nullptr)
		{
			int* indexLocal = localPlayer->GetLocalIndex();

			Native::MenuGUI::PingMiniMap( &Native::Vector3f( position.X, position.Y, 0 ), *indexLocal, *indexLocal, static_cast<int>(type), playSound );
		}
	}
}