#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/BuffInstance.h"
#include "../../EloBuddy.Core/EloBuddy.Core/BuffManager.h"

#include "StaticEnums.h"
#include "GameObject.hpp"
#include "ObjectManager.hpp"

using namespace System;

namespace EloBuddy
{
	public ref class BuffInstance {
	private:
		Native::BuffInstance* self;
		ushort m_index;
		uint m_networkId;
	internal:
		Native::BuffInstance* GetBuffPtr();

		Native::BuffInstance* GetPtr()
		{
			return this->GetBuffPtr();
		}
	public:
		BuffInstance( Native::BuffInstance* inst, uint networkId, ushort index );

		property IntPtr MemoryAddress
		{
			IntPtr get()
			{
				return static_cast<IntPtr>(this->GetBuffPtr());
			}
		}

		property int Index
		{
			int get()
			{
				return static_cast<int>(this->m_index);
			}
		}

		property String^ Name
		{
			String^ get()
			{
				auto ptr = this->GetBuffPtr();
				if (ptr != nullptr)
				{
					auto buffScript = ptr->GetScriptBaseBuff();
					if (buffScript != nullptr && buffScript->GetName() != nullptr)
					{
						return gcnew String( buffScript->GetName() );
					}
				}

				return "Unknown";
			}
		}

		property BuffType Type
		{
			BuffType get()
			{
				auto ptr = this->GetBuffPtr();
				if (ptr != nullptr)
				{
					return static_cast<BuffType>(*ptr->GetType());
				}
				return BuffType::Internal;
			}
		}

		property String^ DisplayName
		{
			String^ get()
			{
				auto ptr = this->GetBuffPtr();
				if (ptr != nullptr)
				{
					auto buffScript = ptr->GetScriptBaseBuff();
					if (buffScript != nullptr)
					{
						return gcnew String( buffScript->GetVirtual()->GetDisplayName() );
					}
				}
				
				return "Unknown";
			}
		}

		property String^ SourceName
		{
			String^ get()
			{
				auto ptr = this->GetBuffPtr();
				if (ptr != nullptr)
				{
					auto buffScript = ptr->GetScriptBaseBuff();
					if (buffScript != nullptr)
					{
						auto childBuff = buffScript->GetChildScriptBuff();
						if (childBuff != nullptr)
						{
							return gcnew String( childBuff->GetSourceName() );
						}
					}
				}

				return "Unknown";
			}
		}

		property GameObject^ Caster
		{
			GameObject^ get()
			{
				START_TRACE
					auto ptr = this->GetBuffPtr();
					if (ptr != nullptr)
					{
						auto buffScript = ptr->GetBuffScriptInstance();
						if (buffScript != nullptr)
						{
							auto src = buffScript->GetCaster();
							if (src != nullptr)
							{
								return ObjectManager::CreateObjectFromPointer( src );
							}
						}
					}
				END_TRACE

				return (GameObject^) ObjectManager::Player;
			}
		}

		property bool IsPositive
		{
			bool get()
			{
				auto ptr = this->GetBuffPtr();
				if (ptr == nullptr)
				{
					return false;
				}

				return ptr->IsPositive();
			}
		}

		property bool IsActive
		{
			bool get()
			{
				auto ptr = this->GetBuffPtr();
				if (ptr == nullptr)
				{
					return false;
				}

				return ptr->IsActive();
			}
		}

		property bool IsValid
		{
			bool get()
			{
				START_TRACE
					return (this->GetBuffPtr() != nullptr && this->IsPositive && this->IsActive);
				END_TRACE

				return false;
			}
		}

		property bool IsPermanent
		{
			bool get()
			{
				auto ptr = this->GetBuffPtr();
				if (ptr== nullptr)
				{
					return false;
				}

				return ptr->IsPermanent();
			}
		}

		//Types
		property bool IsSuppression { bool get() { return this->Type == BuffType::Suppression; } }
		property bool IsStunOrSuppressed { bool get( ) { return this->Type == BuffType::Stun || this->Type == BuffType::Suppression; } }
		property bool IsSlow { bool get( ) { return this->Type == BuffType::Slow; } }
		property bool IsSilence { bool get( ) { return this->Type == BuffType::Silence; } }
		property bool IsRoot { bool get( ) { return this->Type == BuffType::Snare; } }
		property bool IsKnockup { bool get( ) { return this->Type == BuffType::Knockup; } }
		property bool IsKnockback { bool get( ) { return this->Type == BuffType::Knockback; } }
		property bool IsFear { bool get( ) { return this->Type == BuffType::Fear; } }
		property bool IsDisarm { bool get( ) { return this->Type == BuffType::Disarm; } }
		property bool IsBlind { bool get( ) { return this->Type == BuffType::Blind; } }
		property bool IsInternal { bool get() { return this->Type == BuffType::Internal; } }

		property int Count
		{
			int get()
			{
				auto buffPtr = this->GetBuffPtr();
				if (buffPtr != nullptr)
				{
					return buffPtr->GetCount();
				}
				return 0;
			}
		}

		CREATE_GET( EndTime, float );
		CREATE_GET( StartTime, float );
		CREATE_GET_G( CountAlt, int );
		CREATE_GET( IsVisible, bool );
	};
}