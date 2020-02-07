#pragma once
#include "Utils.h"

namespace EloBuddy
{
	namespace Native
	{
		class 
			DLLEXPORT FoundryItemShop
		{
		public:
			static bool ApplyHooks();
			static FoundryItemShop* GetInstance();

			static bool OpenShop();
			static bool CloseShop();
			static void UndoPurchase();
			static bool CanBuy( uint itemId );

			MAKE_GET( IsOpen, bool, Offsets::FoundryItemShop::IsOpen );
		};
	}
}