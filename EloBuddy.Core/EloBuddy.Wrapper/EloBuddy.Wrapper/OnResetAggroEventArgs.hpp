#include "stdafx.h"
#include "ObjectManager.hpp"

using namespace System;
using namespace SharpDX;

namespace EloBuddy
{
	ref class Obj_AI_Base;

	public ref class ObjAIBaseOnResetAggroEventArgs : public System::EventArgs
	{
	private:
		Obj_AI_Base^ m_sender;
		uint m_targetNetworkId;
	public:
		delegate void ObjAIBaseOnResetAggroEvent(Obj_AI_Base^ sender, ObjAIBaseOnResetAggroEventArgs^ args);

		ObjAIBaseOnResetAggroEventArgs(Obj_AI_Base^ sender, uint targetNetworkId)
		{
			this->m_sender = sender;
			this->m_targetNetworkId = targetNetworkId;
		}

		property Obj_AI_Base^ Sender
		{
			Obj_AI_Base^ get()
			{
				return m_sender;
			}
		}

		property Obj_AI_Base^ Target
		{
			Obj_AI_Base^ get()
			{
				if (m_targetNetworkId > 0)
				{
					auto managedObject = ObjectManager::GetUnitByNetworkId<Obj_AI_Base>(m_targetNetworkId);
					return managedObject;
				}

				return nullptr;
			}
		}
	};
}