#include "stdafx.h"
#include "StaticEnums.h"

using namespace System;
using namespace SharpDX;

namespace EloBuddy
{
	ref class GameObject;

	public ref class TacticalMapPingEventArgs : public System::EventArgs
	{
	private:
		Vector2 m_position;
		GameObject^ m_target;
		GameObject^ m_source;
		PingCategory m_pingType;
		bool m_process;
	public:
		delegate void TacticalMapPingEvent( TacticalMapPingEventArgs^ args );

		TacticalMapPingEventArgs( Vector2 position, GameObject^ target, GameObject^ source, PingCategory pingType )
		{
			m_position = position;
			m_target = target;
			m_source = source;
			m_pingType = pingType;
			m_process = true;
		}

		property Vector2 Position
		{
			Vector2 get()
			{
				return m_position;
			}
		}

		property GameObject^ Target
		{
			GameObject^ get()
			{
				return m_target;
			}
		}

		property GameObject^ Source
		{
			GameObject^ get()
			{
				return m_source;
			}
		}

		property PingCategory PingType
		{
			PingCategory get()
			{
				return m_pingType;
			}
		}

		property bool Process
		{
			bool get()
			{
				return this->m_process;
			}
			void set( bool value )
			{
				this->m_process = value;
			}
		}
	};
}