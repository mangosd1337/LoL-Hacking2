#include "stdafx.h"
#include "Crypto.h"
//#include "VMProtect\VMProtectSDK.h"

namespace EloBuddy
{
	namespace Native
	{
		std::string Crypto::Encrypt(std::string const & input)
		{
			//VMProtectBeginUltra("crypto");
			return std::string("hi");
			//VMProtectEnd();
		}

		std::string Crypto::Decrypt(std::string const & input)
		{
			//VMProtectBeginUltra( "crypto" );
			return std::string("hi");
			//VMProtectEnd();
		}
	}
}