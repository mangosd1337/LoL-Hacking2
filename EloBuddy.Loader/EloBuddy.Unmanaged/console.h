#pragma once
#include <string>

class
	Console
{
public:
	static void Create();
	static void Log( const char* fmt ... );
};