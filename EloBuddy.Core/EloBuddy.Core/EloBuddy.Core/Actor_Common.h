#pragma once
#include "Vector3f.h"
#include "NavigationPath.h"

namespace EloBuddy
{
	namespace Native
	{
		class GameObject;

		class
			DLLEXPORT Actor_Common
		{
		private:
		public:
			static bool ApplyHooks();

			bool CreatePath( GameObject* unit, const Vector3f& destination, const NavigationPath& pathOut );
			bool CreatePath( const Vector3f& start, const Vector3f& destination, const NavigationPath& pathOut );
			void SmoothPath( NavigationPath* path );

			bool GetHasNavPath();
			int* GetNavMesh();

			MAKE_GET( LastAction, float, 0x128 );
			MAKE_GET( ServerPosition, Vector3f, 0x204 );
			MAKE_GET( ClickedPosition, Vector3f, 0x210 );
		};
	}
}
