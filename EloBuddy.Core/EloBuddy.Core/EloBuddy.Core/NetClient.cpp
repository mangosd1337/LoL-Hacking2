#include "stdafx.h"
#include "NetClient.h"
#include "EventHandler.h"
#include "PacketTypedefs.h"

namespace EloBuddy
{
	namespace Native
	{
		MAKE_HOOK<convention_type::stdcall_t, bool, C2S_ENetPacket*, int, int> NetClient_SendPacket;

		NetClient* NetClient::Get()
		{
			return **reinterpret_cast<NetClient***>(MAKE_RVA(Offsets::ClientFacade::NetApiNode));
		}

		NetClient_Virtual* NetClient::GetVirtual()
		{
			return *reinterpret_cast<NetClient_Virtual**>(MAKE_RVA(Offsets::ClientFacade::NetApiNode));
		}

		bool NetClient::ApplyHooks()
		{
			NetClient_SendPacket.Apply(*reinterpret_cast<DWORD **>(reinterpret_cast<DWORD>(Get()) + 0x34), [] (C2S_ENetPacket* packet, int a2, int a3) -> bool
			{
				if (!EventHandler<19, OnGameSendPacket, C2S_ENetPacket*, uint, uint, DWORD>::GetInstance()->TriggerProcess(packet, static_cast<uint>(ENETCHANNEL::C2S), static_cast<uint>(ESENDPROTOCOL::Reliable), *packet->GetHashAlgorithm() - Core::mainModule))
				{
					return false;
				}

				return NetClient_SendPacket.CallOriginal(packet, a2, a3);
			});

			return NetClient_SendPacket.IsApplied();
		}

		void NetClient::SendToServer(byte* packet, int size, DWORD hashAlgorithm, ENETCHANNEL channel, ESENDPROTOCOL protocol, bool triggerEvent)
		{
			auto pEnetPacket = reinterpret_cast<C2S_ENetPacket*>(packet);
			auto pHashAlgorithm = hashAlgorithm + Core::mainModule;
			*pEnetPacket->GetHashAlgorithm() = pHashAlgorithm;

			if (triggerEvent)
			{
				NetClient_SendPacket.CallDetour(pEnetPacket, (int) channel, (int) protocol);
			}
			else
			{
				NetClient_SendPacket.CallOriginal(pEnetPacket, (int) channel, (int) protocol);
			}
		}
	}
}