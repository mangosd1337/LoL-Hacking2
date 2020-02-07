#include "Stdafx.h"
#include "RiotAsset.h"

namespace EloBuddy
{
	bool RiotAsset::Exists(String^ asset)
	{
		auto ansi = Marshal::StringToHGlobalAnsi( asset );
		auto exists= Native::RiotAsset::LoadAsset( (char*) ansi.ToPointer() );
		Marshal::FreeHGlobal( ansi );

		return exists;
	}
}