#include "stdafx.h"
#include "ClientNode.h"
#include "PacketTypedefs.h"

namespace EloBuddy
{
	namespace Native
	{
		MAKE_HOOK<convention_type::fastcall_t, bool, S2C_ENetPacket*> ClientNode_ProcessPacket;

		ClientNode* ClientNode::GetInstance()
		{
			return **reinterpret_cast<ClientNode***>(MAKE_RVA(Offsets::ClientFacade::NetApiNode));
		}

		bool ClientNode::ApplyHooks()
		{
			ClientNode_ProcessPacket.Apply(MAKE_RVA(Offsets::ClientFacade::ProcessWorldEvent), [] (S2C_ENetPacket* packet) -> bool
			{
				if (!EventHandler<20, OnGameProcessPacket, S2C_ENetPacket*, uint, uint, DWORD>::GetInstance()->TriggerProcess(packet, static_cast<uint>(ENETCHANNEL::S2C), static_cast<uint>(ESENDPROTOCOL::NoFlags), *packet->GetHashAlgorithm() - Core::mainModule))
				{
					return false;
				}

				return ClientNode_ProcessPacket.CallOriginal(packet);
			});

			return ClientNode_ProcessPacket.IsApplied();
		}

		void ClientNode::ProcessClientPacket(byte* packet, DWORD hashAlgorithm, int size, ENETCHANNEL channel, bool triggerEvent)
		{
			auto pEnetPacket = reinterpret_cast<S2C_ENetPacket*>(packet);
			auto pHashAlgorithm = hashAlgorithm + Core::mainModule;
			*pEnetPacket->GetHashAlgorithm() = pHashAlgorithm;

			if (triggerEvent)
			{
				ClientNode_ProcessPacket.CallDetour(pEnetPacket);
			}
			else
			{
				ClientNode_ProcessPacket.CallOriginal(pEnetPacket);
			}
		}
	}
}
