#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/RiotAsset.h"
#include "Macros.hpp"

using namespace System;

namespace EloBuddy
{
	public ref class RiotAsset
	{
	public:
		static bool Exists( String^ asset );
	};
}