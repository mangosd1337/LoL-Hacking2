#pragma once

#include "Offsets.h"
#include "includes.h"

#define MAKE_FUNCTION(x,y,n,a,...) x(y*n)(__VA_ARGS__) = ( x(y*)(__VA_ARGS__))(a);
#ifndef _Base
#define _Base
#define BASE (__int32)(GetModuleHandle(NULL))
#endif

