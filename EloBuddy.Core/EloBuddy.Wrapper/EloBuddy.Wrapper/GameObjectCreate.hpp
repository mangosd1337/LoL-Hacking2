#include "stdafx.h"

#include "GameObject.hpp"

namespace EloBuddy
{
	ref class GameObject;

	public ref class GameObjectCreateEventArgs : public System::EventArgs
	{
	private:
		GameObject^ obj;
	public:
		delegate void GameObjectCreateEvent(GameObject^ sender, System::EventArgs^ args);

		GameObjectCreateEventArgs(GameObject^ sender)
		{
			this->obj = sender;
		}

		property EloBuddy::GameObject^ GameObject
		{
			EloBuddy::GameObject^ get()
			{
				return this->obj;
			}
		}
	};
}