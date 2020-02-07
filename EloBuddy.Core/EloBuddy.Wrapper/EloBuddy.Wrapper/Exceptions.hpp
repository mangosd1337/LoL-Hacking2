#include "Stdafx.h"
#pragma once

namespace EloBuddy
{
	public ref class GameObjectNotFoundException : public System::Exception {};
	public ref class BuffInstanceNotFoundException : public System::Exception {};
	public ref class SpellDataNotFoundException : public System::Exception {};
	public ref class SpellDataInstNotFoundException : public System::Exception {};
	public ref class SpellbookNotFoundException : public System::Exception {};
	public ref class InventorySlotNotFoundException : public System::Exception {};
}