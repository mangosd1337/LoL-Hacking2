#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/RiotAsset.h"
#include "Macros.hpp"
#include "../../EloBuddy.Core/EloBuddy.Core/RiotString.h"

using namespace System;

namespace EloBuddy
{
	public ref class RiotString
	{
	public:
		static String^ Translate( String^ hashedString );
	};
}