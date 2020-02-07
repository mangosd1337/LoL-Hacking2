#pragma once

enum class Packets
{
	C2S_Heartbeat = 0xA7,
	C2S_Heartbeat2 = 0xA8,

	C2S_CastSpell = 0x99,
	C2S_BuyItem = 0x82,
	C2S_SellItem = 0x9,
	C2S_SwapItem = 0x20,
	C2S_MoveTo = 0x72
};