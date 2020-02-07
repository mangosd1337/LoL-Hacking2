#include "stdafx.h"
#include "StaticEnums.h"

using namespace System;

namespace EloBuddy
{
	ref class AIHeroClient;

	public ref class ChatMessageEventArgs : public System::EventArgs
	{
	private:
		bool m_process;
		char** m_message;
		AIHeroClient^ m_sender;
	public:
		ChatMessageEventArgs( AIHeroClient^ sender, char** message )
		{
			m_sender = sender;
			m_message = message;
			this->m_process = true;
		}

		property AIHeroClient^ Sender
		{
			AIHeroClient^ get()
			{
				return m_sender;
			}
		}

		property String^ Message
		{
			String^ get()
			{
				return gcnew String( *m_message );
			}
			void set(String^ msg)
			{
				*m_message = DEF_INLINE_STRING( msg );
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

	public ref class ChatWhisperEventArgs : public System::EventArgs
	{
	private:
		bool m_process;
		char** m_message;
		char** m_sender;
	public:
		ChatWhisperEventArgs( char** sender, char** message )
		{
			m_sender = sender;
			m_message = message;
			this->m_process = true;
		}

		property String^ Sender
		{
			String^ get()
			{
				return gcnew String( *m_sender );
			}
			void set( String^ msg )
			{
				*m_sender = DEF_INLINE_STRING( msg );
			}
		}

		property String^ Message
		{
			String^ get()
			{
				return gcnew String( *m_message );
			}
			void set( String^ msg )
			{
				*m_message = DEF_INLINE_STRING( msg );
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

	public ref class ChatClientSideMessageEventArgs : public System::EventArgs
	{
	private:
		bool m_process;
		char** m_message;
	public:
		ChatClientSideMessageEventArgs( char** message )
		{
			m_message = message;
			this->m_process = true;
		}

		property String^ Message
		{
			String^ get()
			{
				return gcnew String( *m_message );
			}
			void set( String^ msg )
			{
				*m_message = DEF_INLINE_STRING( msg );
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

	public ref class ChatInputEventArgs : public System::EventArgs
	{
	private:
		bool m_process;
		char** m_input;
	public:
		delegate void ChatInputEvent( ChatInputEventArgs^ args );

		ChatInputEventArgs( char** input)
		{
			this->m_input = input;
			this->m_process = true;
		}

		property String^ Input
		{
			String^ get()
			{
				return gcnew String( *this->m_input );
			}
			void set(String^ value)
			{
				*m_input = DEF_INLINE_STRING( value );
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