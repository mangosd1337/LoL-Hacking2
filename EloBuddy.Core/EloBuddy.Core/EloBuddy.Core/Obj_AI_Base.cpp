#include "stdafx.h"
#include "Obj_AI_Base.h"
#include "Console.h"
#include "AIHeroClient.h"
#include "ObjectManager.h"
#include "r3dRenderer.h"
#include "Actor_Common.h"
#include "Humanizer.h"

namespace EloBuddy
{
	namespace Native
	{
		MAKE_HOOK<convention_type::stdcall_t, void, GameObjectOrder, Vector3f*, GameObject*, DWORD, DWORD, DWORD> EventIssueOrder;
		MAKE_HOOK<convention_type::stdcall_t, bool, SpellCastInfo*> OnCommonBasicAttack;
		MAKE_HOOK<convention_type::stdcall_t, void, int, char*, int, char, float, int> PlayAnimation;
		MAKE_HOOK<convention_type::thiscall_t, int, RecallStruct*, int*> OnBaseTeleport;
		MAKE_HOOK<convention_type::stdcall_t, int, Experience*, signed int> OnLevelUp;
		MAKE_HOOK<convention_type::stdcall_t, double, Vector3f*, int, int, __int64, int> OnPositionUpdate; //Unused
		MAKE_HOOK<convention_type::stdcall_t, void, SpellCastInfo*> OnDoCast;
		MAKE_HOOK<convention_type::cdecl_t, int, int, int, char, char> OnProcessSpell;
		MAKE_HOOK<convention_type::stdcall_t, void, DWORD> OnAggro;

		bool Obj_AI_Base::ApplyHooks()
		{
			EventIssueOrder.Apply( MAKE_RVA( Offsets::GameObjectFunctions::IssueOrder ), [] ( GameObjectOrder order, Vector3f* start, GameObject* target, DWORD isAttackMove, DWORD a2, DWORD a3 ) -> void
			{
				auto processMove = false;

				__asm pushad;
				GameObject* sender = nullptr;

				__asm
				{
					mov sender, ecx
				}

#ifdef _DEBUG_BUILD
				Console::PrintLn( "OnIssueOrder() Sender: %08x - Order: %08x - Start: %g %g %g - GameObj Target: %08x - a1: %08x - a2: %08x - a3: %08x", sender, order, start->GetX(), start->GetY(), start->GetZ(), target, isAttackMove, a2, a3 );
				printf( "========================\r\n\r\n" );
#endif

				processMove = EventHandler<15, OnObjAIBaseIssueOrder, Obj_AI_Base*, uint, Vector3f*, GameObject*, bool>::GetInstance()->TriggerProcess( static_cast<Obj_AI_Base*>(sender), static_cast<uint>(order), start, target, static_cast<bool>(isAttackMove) );

				__asm popad;

				if (processMove && start != nullptr && sender != nullptr)
				{
					__asm mov ecx, sender;
					EventIssueOrder.CallOriginal( order, start, target, isAttackMove, a2, a3 );
				}
			} );

			PlayAnimation.Apply( MAKE_RVA( Offsets::GameObjectFunctions::PlayAnimation ), [] ( int a2, char* animationName, int a4, char a5, float a6, int a7 ) -> void
			{
				Obj_AI_Base* sender = nullptr;
				auto process = true;

				__asm
				{
					mov sender, ecx
				}

				__asm pushad;
				if (sender != nullptr && animationName != nullptr)
				{
					process = EventHandler<18, OnObjAIBasePlayAnimation, Obj_AI_Base*, char**>::GetInstance()->TriggerProcess( sender, &animationName );
				}

				__asm popad;

				if (process)
					return PlayAnimation.CallOriginal( a2, animationName, a4, a5, a6, a7 );
			} );

			OnBaseTeleport.Apply( MAKE_RVA( Offsets::GameObjectFunctions::FOWRecall ), [] ( RecallStruct* recallInfo, int* unkn1 ) -> int
			{
				Obj_AI_Base* sender = nullptr;

				__asm
				{
					mov sender, ecx
				}

				if (sender != nullptr && recallInfo != nullptr)
				{
					auto recallName = recallInfo->GetRecallName();
					auto recallType = recallInfo->GetRecallType();

					EventHandler<16, OnObjAIBaseTeleport, Obj_AI_Base*, char*, char*>::GetInstance()->Trigger( sender, const_cast<char*>(recallName), const_cast<char*>(recallType) );

#ifdef _DEBUG_BUILD
					__asm pushad;
					Console::PrintLn( "OnBaseTeleport() [%s] Name: %s - Type: %s", sender->GetName().c_str(), recallName, recallType );
					printf( "========================\r\n\r\n" );
					__asm popad;
#endif
				}

				__asm
				{
					mov ecx, sender
				}

				return OnBaseTeleport.CallOriginal( recallInfo, unkn1 );
			} );

			OnLevelUp.Apply( MAKE_RVA( Offsets::GameObjectFunctions::OnLevelUp ), [] ( Experience* experience, signed int level ) -> int
			{
				AIHeroClient* sender = nullptr;
				__asm mov sender, ecx;
				sender = static_cast<AIHeroClient*>(sender - static_cast<int>(Offsets::Obj_AIHero::Experience));

				__asm pushad;
				if (sender != nullptr && experience != nullptr)
				{
					EventHandler<44, OnObjAIBaseLevelUp, Obj_AI_Base*, int>::GetInstance()->Trigger( static_cast<Obj_AI_Base*>(sender), level );
				}
				__asm popad;

				sender = static_cast<AIHeroClient*>(sender + static_cast<int>(Offsets::Obj_AIHero::Experience));

				return OnLevelUp.CallOriginal( experience, level );
			} );

			OnDoCast.Apply( MAKE_RVA( Offsets::GameObjectFunctions::OnDoCast ), [] ( SpellCastInfo* castInfo ) -> void
			{
				Obj_AI_Base* sender = nullptr;

				__asm mov sender, ecx;

				__asm pushad;
					EventHandler<71, OnObjAIBaseDoCast, Obj_AI_Base*, SpellCastInfo*>::GetInstance()->Trigger( sender, castInfo );
				__asm popad;

				__asm mov ecx, sender;

				OnDoCast.CallOriginal( castInfo );
			} );

			OnCommonBasicAttack.Apply( MAKE_RVA( Offsets::Spellbook::OnCommonAutoAttack ), [] ( SpellCastInfo* spellCastInfo ) -> bool
			{
				Obj_AI_Base* sender = nullptr;

				__asm
				{
					mov sender, ecx
				}

#ifdef _PBE_BUILD
				sender = static_cast<Obj_AI_Base*>(sender - 0x2908);
#else
				sender = static_cast<Obj_AI_Base*>(sender - 0x2908);
#endif

				if (spellCastInfo != nullptr && spellCastInfo->GetEnd() != nullptr && sender != nullptr)
				{
					__asm pushad;
					EventHandler<73, OnObjAIBaseBasicAttack, Obj_AI_Base*, SpellCastInfo*>::GetInstance()->Trigger( sender, spellCastInfo );
					__asm popad;
				}

#ifdef _PBE_BUILD
				sender = static_cast<Obj_AI_Base*>(sender + 0x2908);
#else
				sender = static_cast<Obj_AI_Base*>(sender + 0x2908);
#endif

				__asm
				{
					mov ecx, sender
				}

				return OnCommonBasicAttack.CallOriginal( spellCastInfo );
			} );

			OnProcessSpell.Apply( MAKE_RVA( Offsets::Spellbook::ProcessCastSpell ), [] ( int unkn1, int unkn2, char IsBasicAttack, char unkn3 ) -> int
			{
				if (!IsBasicAttack)
				{
					SpellCastInfo* castInfo = nullptr;
					Obj_AI_Base* sender = nullptr;

					__asm mov castInfo, esp;
					__asm mov sender, ecx;

					sender = static_cast<Obj_AI_Base*>(sender - static_cast<int>(Offsets::Spellbook::SpellbookInst));
					castInfo = reinterpret_cast<SpellCastInfo*>(reinterpret_cast<DWORD>(castInfo) + static_cast<int>(0x68));

					__asm pushad;
						EventHandler<14, OnObjAIBaseProcessSpellcast, Obj_AI_Base*, SpellCastInfo*>::GetInstance()->Trigger( sender, castInfo );
					__asm popad;

					sender = static_cast<Obj_AI_Base*>(sender + static_cast<int>(Offsets::Spellbook::SpellbookInst));
					castInfo = reinterpret_cast<SpellCastInfo*>(reinterpret_cast<DWORD>(castInfo) - static_cast<int>(0x68));

					__asm
					{
						mov esp, castInfo
						mov ecx, sender
					}
				}

				return OnProcessSpell.CallOriginal( unkn1, unkn2, IsBasicAttack, unkn3 );
			} );

			/*OnAggro.Apply(MAKE_RVA(Offsets::GameObjectFunctions::OnAggro), [] (DWORD targetNetId) -> void
			{
				Obj_AI_Base* sender;
				__asm mov sender, ebx;

				__asm pushad;
				
					if (targetNetId > 0)
					{
						sender = *reinterpret_cast<Obj_AI_Base**>(sender + 0x168);
					}

					if (sender != nullptr)
					{
						Console::PrintLn("TargetNetworkId: %d (flag: %d) - Sender: %s", targetNetId, targetNetId > 0, sender->GetName().c_str());
					}

				__asm popad;
				__asm mov ebx, sender;
			});*/

			return EventIssueOrder.IsApplied()
				&& PlayAnimation.IsApplied()
				&& OnBaseTeleport.IsApplied()
				&& OnDoCast.IsApplied()
				&& OnCommonBasicAttack.IsApplied()
				&& OnLevelUp.IsApplied();
		}

		bool Obj_AI_Base::IssueOrder(Vector3f* position, GameObject* unit, GameObjectOrder order, bool triggerEvent)
		{
			static auto humanizer = Humanizer(40, 100);

			if (!humanizer.CanExecute(static_cast<byte>(order)))
			{
				return false;
			}

			if (position == nullptr || this == nullptr)
			{
				return false;
			}

			if (order == GameObjectOrder::AttackUnit
				&& unit == nullptr)
			{
				return false;
			}

			auto issueOrderFlags1 = 0x0000000;
			auto issueOrderFlags2 = 0x0000000;

			switch (order)
			{
			case GameObjectOrder::HoldPosition:
				EventIssueOrder.CallOriginal(GameObjectOrder::Stop, position, nullptr, 0, 0, 0x0000001);

				issueOrderFlags1 = 0x0000000;
				issueOrderFlags2 = 0x0000001;
				break;
			case GameObjectOrder::MoveTo:
			case GameObjectOrder::AttackTo:
			case GameObjectOrder::AttackUnit:
			case GameObjectOrder::AutoAttack:
			case GameObjectOrder::AutoAttackPet:
				issueOrderFlags1 = 0xffffff00;
				break;
			case GameObjectOrder::Stop:
				issueOrderFlags2 = 0x0000001;
				break;
			case GameObjectOrder::MovePet:
				break;
			}

			__asm
			{
				mov ecx, this;
			}

			if (triggerEvent)
				EventIssueOrder.CallDetour(order, position, unit, 0, issueOrderFlags1, issueOrderFlags2);
			else
				EventIssueOrder.CallOriginal(order, position, unit, 0, issueOrderFlags1, issueOrderFlags2);

			return true;
		}

		bool Obj_AI_Base::UseObject( Obj_AI_Base* object)
		{
			return this->Capture(object);
		}

		bool Obj_AI_Base::GetIsHPBarBeingDrawn()
		{
			return this->GetIsVisible();
		}

		bool Obj_AI_Base::Capture( Obj_AI_Base* object )
		{
			auto UserComponent = this->GetUserComponent();
			auto CaptureTurret = MAKE_RVA( Offsets::GameObjectFunctions::CaptureTurret );
			auto returnValue = false;

			__asm
			{
				push object
				mov ecx, UserComponent
				call [CaptureTurret]

				mov returnValue, al
			}

			return returnValue;
		}

		Vector3f UnitInfoComponent::GetHPBarPosition()
		{
			auto vec = this->GetBaseDrawPosition();

			if (vec->IsValid())
			{
				auto HPBar = *this->GetHealthbar();
				if (HPBar != nullptr)
				{
					float XOffset = *HPBar->GetXOffset();
					float YOffset = *HPBar->GetYOffset();

					auto vecInProjection = Vector3f( vec->GetX(), vec->GetZ(), vec->GetY() );
					auto vecOutProjection = Vector3f( 0, 0, 0 );

					r3dRenderer::GetInstance()->r3dProjectToScreen( &vecInProjection, &vecOutProjection );

					float bX = vecOutProjection.GetX() + 51.975f * XOffset - 68;
					float bY = vecOutProjection.GetY() + 51.975f * YOffset - 7;

					delete vec;
					return Vector3f( bX, bY, 0 );
				}
			}

			delete vec;
			return Vector3f( 0, 0, 0 );
		}

		void Obj_AI_Base::ExportFunctions()
		{
			/*module( LuaEz::GetMainState() )
				[
					class_<Obj_AI_Base, AttackableUnit>( "Obj_AI_Base" )
				];

			DPRINT( "Obj_AI_Base::ExportFunctions() exported" );*/
		}
	}
}