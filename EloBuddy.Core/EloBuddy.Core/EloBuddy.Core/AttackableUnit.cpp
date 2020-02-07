#include "stdafx.h"
#include "AttackableUnit.h"
#include "Utils.h"
#include "Offsets.h"
#include "EventHandler.h"

namespace EloBuddy
{
	namespace Native
	{
		MAKE_HOOK<convention_type::stdcall_t, char, AttackableUnit*, byte, byte, float, AttackableUnit*, int> OnDamageEvent;

		bool AttackableUnit::ApplyHooks()
		{
			//ToDo: Cleanup
			OnDamageEvent.Apply( MAKE_RVA( Offsets::GameObjectFunctions::OnDamage ), [] ( AttackableUnit* target, byte a2, byte a4, float damage, AttackableUnit* sender, int a7 ) -> char
			{
				DamageLayout* dmgLayout = nullptr;
				
				__asm
				{
					mov dmgLayout, eax
					pushad
				}

				if (sender != nullptr && target != nullptr && dmgLayout != nullptr )
				{
					EventHandler<30, OnAttackableUnitOnDamage, AttackableUnit*, AttackableUnit*, float, DamageLayout* >::GetInstance()->Trigger( sender, target, damage, dmgLayout );
				}

				//Console::PrintLn("a2: %d - a3: %d a7: %d", a2, a4, a7);

				__asm
				{
					popad
					mov dmgLayout, eax
				}

				return OnDamageEvent.CallOriginal( target, a2, a4, damage, sender, a7 );
			} );

			return OnDamageEvent.IsApplied();
		}

		void AttackableUnit::ExportFunctions()
		{
			/*module( LuaEz::GetMainState() )
				[
					class_<AttackableUnit, GameObject>( "AttackableUnit" )
				];

			DPRINT( "AttackableUnit::ExportFunctions() exported" );*/
		}
	}
}