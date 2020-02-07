#include "stdafx.h"

using namespace System;
using namespace SharpDX;

namespace EloBuddy
{
	ref class Obj_AI_Base;
	ref class BuffInstance;

	public ref class Obj_AI_BaseBuffGainEventArgs : public System::EventArgs
	{
	private:
		property BuffInstance^ m_buff;
	public:
		delegate void Obj_AI_BaseBuffApplyEvent( Obj_AI_Base^ sender, Obj_AI_BaseBuffGainEventArgs^ args );

		Obj_AI_BaseBuffGainEventArgs( BuffInstance^ buff )
		{
			this->m_buff = buff;
		}

		property BuffInstance^ Buff
		{
			BuffInstance^ get()
			{
				return this->m_buff;
			}
		}
	};

	public ref class Obj_AI_BaseBuffLoseEventArgs : public System::EventArgs
	{
	private:
		property BuffInstance^ m_buff;
	public:
		delegate void Obj_AI_BaseBuffApplyEvent( Obj_AI_Base^ sender, Obj_AI_BaseBuffLoseEventArgs^ args );

		Obj_AI_BaseBuffLoseEventArgs( BuffInstance^ buff )
		{
			this->m_buff = buff;
		}

		property BuffInstance^ Buff
		{
			BuffInstance^ get()
			{
				return this->m_buff;
			}
		}
	};

	public ref class Obj_AI_BaseBuffUpdateEventArgs : public System::EventArgs
	{
	private:
		property BuffInstance^ m_buff;
	public:
		delegate void Obj_AI_BaseBuffUpdateEvent( Obj_AI_Base^ sender, Obj_AI_BaseBuffUpdateEventArgs^ args );

		Obj_AI_BaseBuffUpdateEventArgs( BuffInstance^ buff )
		{
			this->m_buff = buff;
		}

		property BuffInstance^ Buff
		{
			BuffInstance^ get()
			{
				return this->m_buff;
			}
		}
	};
}