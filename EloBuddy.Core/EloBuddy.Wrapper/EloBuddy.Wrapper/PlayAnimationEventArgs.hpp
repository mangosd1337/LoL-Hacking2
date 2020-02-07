#include "stdafx.h"


using namespace System;
using namespace SharpDX;

namespace EloBuddy
{
	ref class Obj_AI_Base;

	public ref class GameObjectPlayAnimationEventArgs : public System::EventArgs
	{
	private:
		char** m_animation;
		bool m_process;
	public:
		delegate void GameObjectPlayAnimationEvent( Obj_AI_Base^ sender, GameObjectPlayAnimationEventArgs^ args );

		GameObjectPlayAnimationEventArgs( char** animation )
		{
			this->m_animation = animation;
			this->m_process = true;
		}

		property String^ Animation
		{
			String^ get()
			{
				return gcnew String( *m_animation );
			}
			void set(String^ value)
			{
				*m_animation = DEF_INLINE_STRING( value );
			}
		}

		property bool Process
		{
			bool get()
			{
				return this->m_process;
			}
			void set(bool value)
			{
				this->m_process = value;
			}
		}
	};
}