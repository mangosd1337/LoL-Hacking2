#include "Stdafx.h"
#include "RiotString.h"

namespace EloBuddy
{
	String^ RiotString::Translate( String^ hashedString )
	{
		auto ansi = Marshal::StringToHGlobalAnsi( hashedString );
		auto translated = gcnew String( Native::RiotString::TranslateString( (char*)ansi.ToPointer() ) );
		Marshal::FreeHGlobal( ansi );

		return translated;
	}
}