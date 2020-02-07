#include "stdafx.h"

#include "GameObject.hpp"

namespace EloBuddy
{
	ref class GameObject;

	public ref class GameObjectIntegerPropertyChangeEventArgs : public System::EventArgs
	{
	private:
		const char* m_prop;
		int m_value;
	public:
		delegate void GameObjectIntegerPropertyChangeEvent( GameObject^ sender, System::EventArgs^ args );

		GameObjectIntegerPropertyChangeEventArgs( const char* prop, int value )
		{
			this->m_prop = prop;
			this->m_value = value;
		}

		property String^ Property
		{
			String^ get()
			{
				return gcnew String( m_prop );
			}
		}

		property int Value
		{
			int get()
			{
				return m_value;
			}
		}
	};
}