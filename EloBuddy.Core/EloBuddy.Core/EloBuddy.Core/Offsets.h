#pragma once
#include "includes.h"
#include "StaticEnums.h"

#undef PlaySound

#define _CN_BUILD
//#define _GARENA_BUILD
//#define _EU_BUILD
//#define _PBE_BUILD

//#define _DEBUG_BUILD

namespace EloBuddy
{
	namespace Native
	{
		#ifdef _EU_BUILD
			#define LOL_VERSION "Releases/6.4"
			#include "OffsetsEU.h"
		#endif
		#ifdef _GARENA_BUILD
			#define LOL_VERSION "6.3.0.241"
			#include "OffsetsGARENA.h"
		#endif
		#ifdef _CN_BUILD
			#define LOL_VERSION "Releases/6.3"
			#include "OffsetsCN.h"
		#endif
	}
}
