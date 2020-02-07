#include "stdafx.h"

namespace EloBuddy
{
	ref class GameObject;

	public ref class OnCreateObjectEventArgs : public System::EventArgs
	{
	private:
		GameObject^ m_sender;
		char** m_modelName;
		int* m_skinId;
		int* m_chromaId;
		Native::Vector3f** m_position;
	public:
		delegate void OnCreateObjectEvent( OnCreateObjectEventArgs^ args );

		OnCreateObjectEventArgs( GameObject^ sender, char** modelName, int* skinId, int* chromaId, Native::Vector3f** position )
		{
			this->m_sender = sender;
			this->m_modelName = modelName;
			this->m_skinId = skinId;
			this->m_chromaId = chromaId;
			this->m_position = position;
		}

		property Vector3 Position
		{
			Vector3 get()
			{
				auto pos = *m_position;
				return Vector3( pos->GetX(), pos->GetZ(), pos->GetY() );
			}
			void set(Vector3 value)
			{
				*m_position = &Native::Vector3f( value.X, value.Z, value.Y );
			}
		}

		property String^ Model
		{
			String^ get()
			{
				return gcnew String( *m_modelName );
			}
			void set(String^ value)
			{
				*m_modelName = DEF_INLINE_STRING( value );
			}
		}

		property int SkinId
		{
			int get()
			{
				return *m_skinId;
			}
			void set(int value)
			{
				*m_skinId = value;
			}
		}

		property GameObjectTeam Team
		{
			GameObjectTeam get()
			{
				return (GameObjectTeam) *m_chromaId;
			}
			void set( GameObjectTeam value )
			{
				*m_chromaId = static_cast<int>(value);
			}
		}
	};
}