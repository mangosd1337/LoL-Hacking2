#include "stdafx.h"

#include "GameObject.hpp"

namespace EloBuddy
{
	ref class GameObject;

	public ref class GameObjectFloatPropertyChangeEventArgs : public System::EventArgs
	{
	private:
		const char* m_prop;
		float m_value;
	public:
		delegate void GameObjectFloatPropertyChangeEvent( GameObject^ sender, System::EventArgs^ args );

		GameObjectFloatPropertyChangeEventArgs( const char* prop, float value )
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

		property float Value
		{
			float get()
			{
				return m_value;
			}
		}
	};
}