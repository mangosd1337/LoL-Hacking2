#pragma once
#include "Utils.h"
#include "NetClient_Virtual.h"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT NetClient
		{
		public:
			static NetClient* Get();
			NetClient_Virtual* GetVirtual();

			static bool ApplyHooks();
			static void SendToServer( byte* packet, int size, DWORD hashAlgorithm, ENETCHANNEL channel, ESENDPROTOCOL protocol, bool triggerEvent );
		};
	}
}