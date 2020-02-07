#pragma once
#include "Macros.h"

namespace EloBuddy
{
	namespace Native
	{
		class DLLEXPORT Vector3f
		{
		protected:
			float X, Y, Z;
		public:
			Vector3f();
			Vector3f( float xx, float yy, float zz );

			Vector3f SwitchYZ() const;
			bool IsValid() const;
			operator float*();

			float GetX() const;
			float GetY() const;
			float GetZ() const;
			float DistanceTo( const Vector3f & v ) const;

			Vector3f Randomize();
		};
	}
}