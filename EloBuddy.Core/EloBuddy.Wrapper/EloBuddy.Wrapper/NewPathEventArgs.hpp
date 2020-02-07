#include "stdafx.h"

using namespace System;
using namespace SharpDX;

namespace EloBuddy
{
	ref class Obj_AI_Base;

	public ref class GameObjectNewPathEventArgs : public System::EventArgs
	{
	private:
		array<Vector3>^ m_newPath;
		bool m_isDash;
		float m_speed;
	public:
		delegate void GameObjectNewPathEvent( Obj_AI_Base^ sender, GameObjectNewPathEventArgs^ args );

		GameObjectNewPathEventArgs( array<Vector3>^ newPath, bool isDash, float speed)
		{
			this->m_newPath = newPath;
			this->m_isDash = isDash;
			this->m_speed = speed;
		}

		property bool IsDash
		{
			bool get()
			{
				return this->m_isDash;
			}
		}

		property float Speed
		{
			float get()
			{
				return this->m_speed;
			}
		}

		property array<Vector3>^ Path
		{
			array<Vector3>^ get()
			{
				return this->m_newPath;
			}
		}
	};
}