#include "stdafx.h"
#include "injection.h"

#define INJECTION_API extern "C" __declspec(dllexport)

#include "console.h"
#include <iostream>

INJECTION_API bool Inject( int procId, const LPCWSTR dllPath )
{
	Console::Create();

	auto injection = new Injection( procId, dllPath );
	return injection->Inject();
}