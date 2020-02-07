#include "Stdafx.h"
#include "RiotAsset.h"

namespace EloBuddy
{
	namespace Native
	{
		bool RiotAsset::LoadAsset(char* asset)
		{
			return
				reinterpret_cast<int(__stdcall*)(char* asset, char* unkn, int hideDisplayError)>
				MAKE_RVA(Offsets::RiotAsset::AssetExists)
				(asset, asset, 1);
		}
	}
}