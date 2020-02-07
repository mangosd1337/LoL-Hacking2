#include "stdafx.h"
#include "../../EloBuddy.Core/EloBuddy.Core/Obj_AI_Base.h"

#include "ObjectManager.hpp"

using namespace System;
using namespace SharpDX;

namespace EloBuddy
{
	ref class AttackableUnit;

	public ref class OnDrawHealthbarEventArgs : public System::EventArgs
	{
	private:
		Native::UnitInfoComponent* m_infoComponent;
		Native::AttackableUnit* m_sender;
		bool m_process;
	public:
		OnDrawHealthbarEventArgs( Native::UnitInfoComponent* infoComponent, Native::AttackableUnit* sender )
		{
			m_infoComponent = infoComponent;
			m_sender = sender;
			m_process = true;
		}

		property Vector2 Position
		{
			Vector2 get()
			{
				auto hpBarPos = this->m_infoComponent->GetHPBarPosition();
				return Vector2( hpBarPos.GetX(), hpBarPos.GetY() );
			}
		}

		property float YOffset
		{
			float get()
			{
				auto hpBar = *this->m_infoComponent->GetHealthbar();
				if (hpBar != nullptr)
				{
					return *hpBar->GetYOffset();
				}
				return 0.0f;
			}
		}

		/*property bool Process
		{
			bool get()
			{
				return this->m_process;
			}
			void set(bool value)
			{
				this->m_process = value;
			}
		}*/
	};
}