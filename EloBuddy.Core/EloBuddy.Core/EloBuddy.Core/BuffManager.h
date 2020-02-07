#pragma once
#include "Macros.h"
#include "StaticEnums.h"
#include "Offsets.h"

namespace EloBuddy
{
	namespace Native
	{
		class BuffInstance;

		#pragma pack(push, 1)
		struct BuffNode
		{
			BuffInstance* buffInst;
			void* _unk;
		};

		class
			DLLEXPORT BuffManager
		{
		public:
			MAKE_GET(Begin, BuffNode*, Offsets::BuffManagerStruct::GetBegin);
			MAKE_GET( End, BuffNode*, Offsets::BuffManagerStruct::GetEnd );
		};
	}
}
