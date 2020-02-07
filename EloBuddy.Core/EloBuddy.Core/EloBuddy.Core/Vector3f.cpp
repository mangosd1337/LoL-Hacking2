#include "stdafx.h"
#include "Vector3f.h"

namespace EloBuddy
{
	namespace Native
	{
		Vector3f::Vector3f()
		{

		}

		Vector3f::Vector3f(float xx, float yy, float zz) : X(xx), Y(yy), Z(zz) {}

		Vector3f Vector3f::SwitchYZ() const
		{
			return Vector3f(X, Z, Y);
		}

		bool Vector3f::IsValid() const
		{
			return X != 0 && Y != 0 && Z != 0;
		}

		Vector3f::operator float*()
		{
			return &X;
		}

		float Vector3f::GetX() const
		{
			return X;
		}

		float Vector3f::GetY() const
		{
			return Y;
		}

		float Vector3f::GetZ() const
		{
			return Z;
		}

		float Vector3f::DistanceTo(const Vector3f& v) const
		{
			return sqrt(pow(v.X - X, 2) + pow(v.Z - Z, 2) + pow(v.Y - Y, 2));
		}

		Vector3f Vector3f::Randomize()
		{
			auto xRand = X + (rand() % -50 + 100);
			auto zRand = Z + (rand() % -30 + 70);

			return Vector3f(xRand, Y, zRand);
		}
	}
}