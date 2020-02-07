#include "stdafx.h"

using namespace System;
using namespace SharpDX;

namespace EloBuddy
{
	ref class Obj_AI_Base;

	public ref class GameObjectTeleportEventArgs : public System::EventArgs
	{
	private:
		String^ m_recallName;
		String^ m_recallType;
	public:
		delegate void GameObjectTeleportEvent( Obj_AI_Base^ sender, GameObjectTeleportEventArgs^ args );

		GameObjectTeleportEventArgs( String^ recallName, String^ recallType )
		{
			this->m_recallName = recallName;
			this->m_recallType = recallType;
		}

		property String^ RecallName
		{
			String^ get()
			{
				return this->m_recallName;
			}
		}

		property String^ RecallType
		{
			String^ get()
			{
				return this->m_recallType;
			}
		}
	};
}