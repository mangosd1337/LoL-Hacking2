#include "stdafx.h"

#include "Obj_AI_Base.hpp"
#include "Experience.h"

using namespace System;
using namespace SharpDX;

namespace EloBuddy
{
	public ref class Obj_AI_BaseLevelUpEventArgs : public System::EventArgs
	{
	private:
		Obj_AI_Base^ m_sender;
		int m_level;
	public:
		delegate void Obj_AI_BaseLevel( Obj_AI_Base^ sender, Obj_AI_BaseLevelUpEventArgs^ args );

		Obj_AI_BaseLevelUpEventArgs( Obj_AI_Base^ sender, int level )
		{
			this->m_sender = sender;
			this->m_level = level;
		}

		property int Level
		{
			int get()
			{
				return m_level;
			}
		}
	};
}