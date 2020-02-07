#pragma once
#include "Utils.h"

namespace EloBuddy
{
	namespace Native
	{
		class r3dTexture;

		class
			DLLEXPORT r3dRenderLayer
		{
		public:
			static bool ApplyHooks();

			static r3dTexture* LoadTexture( std::string* texture );
		};
	}
}