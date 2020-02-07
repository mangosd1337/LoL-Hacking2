#include "stdafx.h"
#include "NavigationPath.h"

namespace EloBuddy
{
	namespace Native
	{
		NavigationPath::NavigationPath() : dwCurPath(0), dwUnkn(0), StartVec(0, 0, 0), EndVec(0, 0, 0), Path(nullptr), PathEnd(nullptr), _byte0268()
		{
			ZeroMemory(this, sizeof(NavigationPath));
			this->Path = new Vector3f [1400];
		}

		NavigationPath::~NavigationPath()
		{
			if (this->Path != nullptr)
			{
				delete[] this->Path;
			}
		}
	}
}
