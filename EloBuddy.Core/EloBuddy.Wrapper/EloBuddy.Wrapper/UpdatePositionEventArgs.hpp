#include "stdafx.h"

#include "Obj_AI_Base.hpp"
#include "Experience.h"

using namespace System;
using namespace SharpDX;

namespace EloBuddy
{
	public ref class Obj_AI_UpdatePositionEventArgs : public System::EventArgs
	{
	private:
		Obj_AI_Base^ m_sender;
		Vector3 m_position;
	public:
		delegate void Obj_AI_UpdatePosition( Obj_AI_Base^ sender, Obj_AI_BaseLevelUpEventArgs^ args );

		Obj_AI_UpdatePositionEventArgs( Obj_AI_Base^ sender, Vector3 position )
		{
			this->m_sender = sender;
			this->m_position = position;
		}

		property Obj_AI_Base^ Sender
		{
			Obj_AI_Base^ get()
			{
				return m_sender;
			}
		}

		property Vector3 Position
		{
			Vector3 get()
			{
				return m_position;
			}
		}
	};
}