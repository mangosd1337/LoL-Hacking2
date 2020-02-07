#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/RiotAsset.h"
#include "Macros.hpp"
#include "../../EloBuddy.Core/EloBuddy.Core/RiotString.h"

using namespace System;

namespace EloBuddy
{
	public ref class BugSplat
	{
	internal:
		Exception^ m_exception;
		bool m_sendExceptions;

		bool SendExceptionReport( Exception^ ex );
	};
}