#pragma once
#include "Utils.h"

namespace EloBuddy
{
	namespace Native
	{
		class Crypto
		{
		public:
			std::string Encrypt( std::string const & input );
			std::string Decrypt( std::string const & input );
		};
	}
}