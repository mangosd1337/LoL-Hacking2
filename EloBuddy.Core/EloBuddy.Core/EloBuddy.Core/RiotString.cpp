#include "stdafx.h"
#include "RiotString.h"

namespace EloBuddy
{
	namespace Native
	{
		char* RiotString::TranslateString(char* hash)
		{
			__try
			{
				auto pfRiotTranslateString = MAKE_RVA(Offsets::RiotString::TranslateString);

				__asm
				{
					mov ecx, hash
					call [pfRiotTranslateString]
				}
			}
			__except (EXCEPTION_EXECUTE_HANDLER)
			{
				return "Unknown (Exception)";
			}
		}
	}
}