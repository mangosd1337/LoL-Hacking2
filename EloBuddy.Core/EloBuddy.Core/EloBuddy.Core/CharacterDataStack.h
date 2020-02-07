#pragma once
#include "Utils.h"
#include "RiotAsset.h"
#include "pwConsole.h"
#include "Obj_AI_Base.h"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT CharacterDataStack : public Obj_AI_Base
		{
		public:
			static bool ApplyHooks();

			MAKE_GET( ActiveSkinId, int, Offsets::CharacterDataStack::ActiveSkinId );
			MAKE_GET( ActiveModel, std::string, Offsets::CharacterDataStack::ActiveModel );

			void SetBaseSkinId( int skinId );
			bool SetModel( char* model );
		};

	}
}