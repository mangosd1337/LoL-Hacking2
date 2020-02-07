#include "stdafx.h"
#include "ClientFacade.h"

namespace EloBuddy
{
	namespace Native
	{
		ClientFacade* ClientFacade::GetInstance()
		{
			return *reinterpret_cast<ClientFacade**>(MAKE_RVA(Offsets::ClientFacade::NetApiClient));
		}

		int ClientFacade::GetGameState() const
		{
			return *reinterpret_cast<int*>(MAKE_RVA(Offsets::ClientFacade::GameState));
		}
	}
}