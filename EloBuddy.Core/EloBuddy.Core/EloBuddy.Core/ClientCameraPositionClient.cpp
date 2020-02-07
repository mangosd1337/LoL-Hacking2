#include "stdafx.h"
#include "ClientCameraPositionClient.hpp"
#include "Offsets.h"

namespace EloBuddy
{
	namespace Native
	{
		ClientCameraPositionClient* ClientCameraPositionClient::getInstance()
		{
			static ClientCameraPositionClient* instance = (ClientCameraPositionClient*)(MAKE_RVA(m_ClientCamera::ClientCameraPositionClient));
			return instance;
		}
	}
}