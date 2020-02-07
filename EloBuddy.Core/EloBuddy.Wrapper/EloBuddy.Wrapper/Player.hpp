#pragma once
#include "../../EloBuddy.Core/EloBuddy.Core/AIHeroClient.h"
#include "../../EloBuddy.Core/EloBuddy.Core/Vector3f.h"
#include "../../EloBuddy.Core/EloBuddy.Core/pwHud.h"
#include "../../EloBuddy.Core/EloBuddy.Core/CharacterDataStack.h"

#include "AIHeroClient.hpp"

#define MAKE_STATIC_PROP(NAME, TYPE) static property TYPE NAME { TYPE get() { return m_instance->NAME; } }
#define MAKE_STATIC_SB(NAME, TYPE) static property TYPE NAME { TYPE get() { return m_instance->Spellbook->NAME; }}

using namespace System;
using namespace SharpDX;
using namespace System::Collections::Generic;

#include "PlayerIssueOrderEventArgs.hpp"
#include "PlayerSwapItemEventArgs.hpp"
#include "PlayerDoEmoteEventArgs.hpp"

namespace EloBuddy
{
	MAKE_EVENT_GLOBAL( Player_ProcessIssueOrder, Obj_AI_Base^ sender, PlayerIssueOrderEventArgs^ args );
	MAKE_EVENT_GLOBAL( Player_SwapItem, AIHeroClient^ sender, PlayerSwapItemEventArgs^ args );
	MAKE_EVENT_GLOBAL( Player_DoEmote, AIHeroClient^ sender, PlayerDoEmoteEventArgs^ args );

	public ref class Player : public AIHeroClient {
	private:
		static Native::AIHeroClient* self;
		static AIHeroClient^ m_instance;
	internal:
		MAKE_EVENT_INTERNAL_PROCESS( Player_ProcessIssueOrder, (Native::Obj_AI_Base*, uint, Native::Vector3f*, Native::GameObject*, bool) );
		MAKE_EVENT_INTERNAL_PROCESS( Player_SwapItem, (Native::AIHeroClient*, uint, uint) );
		MAKE_EVENT_INTERNAL_PROCESS( Player_DoEmote, (Native::AIHeroClient*, short) );
	public:
		MAKE_EVENT_PUBLIC( OnIssueOrder, Player_ProcessIssueOrder );
		MAKE_EVENT_PUBLIC( OnSwapItem, Player_SwapItem );
		MAKE_EVENT_PUBLIC( OnEmote, Player_DoEmote );

		static Player();
		Player( ) {};
		static void DomainUnloadEventHandler( System::Object^, System::EventArgs^ );

		static property AIHeroClient^ Instance
		{
			AIHeroClient^ get() { return m_instance; }
		}

		static bool Equals( GameObject ^o )
		{
			START_TRACE
				return (m_instance->NetworkId == o->NetworkId);
			END_TRACE

			return false;
		}

		static bool SwapItem( int sourceSlotId, int targetSlotId );

		static bool UseObject( GameObject ^ obj );

		static bool IssueOrder( GameObjectOrder order, Vector3 targetPos );
		static bool IssueOrder( GameObjectOrder order, GameObject^ targetUnit );
		static bool IssueOrder( GameObjectOrder order, Vector3 targetPos, bool triggerEvent );
		static bool IssueOrder( GameObjectOrder order, GameObject^ targetUnit, bool triggerEvent );

		static void SetSkin( String^ model, int skinId )
		{
			m_instance->SetModel( model );
			m_instance->SetSkinId( skinId );
		}

		static void SetModel( String^ model )
		{
			m_instance->SetModel( model );
		}

		static void SetSkinId( int skinId )
		{
			m_instance->SetSkinId( skinId );
		}

		static bool DoEmote( Emote emote )
		{
			return self->DoEmote( (short) emote );
		}

		static bool DoMasteryBadge();

		static bool HasBuffOfType( BuffType type )
		{
			return m_instance->HasBuffOfType( type );
		}

		static bool HasBuff( String^ name )
		{
			return m_instance->HasBuff( name );
		}

		static BuffInstance^ GetBuff( String^ name )
		{
			return m_instance->GetBuff( name );
		}

		static SpellState CanUseSpell( SpellSlot slot );

		static bool CastSpell( SpellSlot slot );
		static bool CastSpell( SpellSlot slot, GameObject^ target );
		static bool CastSpell( SpellSlot slot, Vector3 position );
		static bool CastSpell( SpellSlot slot, Vector3 startPosition, Vector3 endPosition );

		static bool CastSpell( SpellSlot slot, bool triggerEvent );
		static bool CastSpell( SpellSlot slot, GameObject^ target, bool triggerEvent );
		static bool CastSpell( SpellSlot slot, Vector3 position, bool triggerEvent );
		static bool CastSpell( SpellSlot slot, Vector3 startPosition, Vector3 endPosition, bool triggerEvent );

		static bool UpdateChargeableSpell( SpellSlot slot, Vector3 position, bool releaseCast );
		static bool UpdateChargeableSpell( SpellSlot slot, Vector3 position, bool releaseCast, bool triggerEvent );

		static void EvolveSpell( SpellSlot slot );
		static SpellDataInst^ GetSpell( SpellSlot slot );
		static bool LevelSpell( SpellSlot slot );

		array<Vector3>^ GetPath( Vector3 end );
		array<Vector3>^ GetPath( Vector3 start, Vector3 end );
		array<Vector3>^ GetPath( Vector3 end, bool smoothPath );
		array<Vector3>^ GetPath( Vector3 start, Vector3 end, bool smoothPath );

		static property List<SpellDataInst^>^ Spells
		{
			List<SpellDataInst^>^ get();
		}

		property String^ Model
		{
			String^ get()
			{
				auto charStack = this->GetPtr()->GetCharacterDataStack();
				if (charStack != nullptr)
				{
					return gcnew String( charStack->GetActiveModel()->c_str() );
				}

				return "Unknown";
			}
		}

		property int SkinId
		{
			int get()
			{
				auto charStack = this->GetPtr()->GetCharacterDataStack();
				if (charStack != nullptr)
				{
					return *charStack->GetActiveSkinId();
				}
				return 0;
			}
		}

		property bool HasTarget
		{
			bool get()
			{
				return Target != nullptr;
			}
		}

		property GameObject^ Target
		{
			GameObject^ get()
			{
				auto pwHud = Native::pwHud::GetInstance();
				if (pwHud != nullptr)
				{
					auto hudManager = pwHud->GetHudManager();
					if (hudManager != nullptr)
					{
						return ObjectManager::GetUnitByIndex( *hudManager->GetTargetIndexId() );
					}
				}

				return nullptr;
			}
		}
	};
}