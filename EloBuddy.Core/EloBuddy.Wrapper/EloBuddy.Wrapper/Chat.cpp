#include "Stdafx.h"
#include "Chat.h"

namespace EloBuddy
{
	static Chat::Chat()
	{
		ATTACH_DOMAIN();

		ATTACH_EVENT
		(
			ChatInput,
			40, Native::OnChatInput, char**
		);
		ATTACH_EVENT
		(
			ChatMessage,
			41, Native::OnChatMessage, Native::AIHeroClient*, char**
		);
		ATTACH_EVENT
		(
			ChatClientSideMessage,
			42, Native::OnChatClientSideMessage, char**
		);
		ATTACH_EVENT
		(
			ChatSendWhisper,
			43, Native::OnChatSendWhisper, char**, char**
		);
	}

	void Chat::DomainUnloadEventHandler( System::Object^, System::EventArgs^ )
	{
		DETACH_EVENT
		(
			ChatInput,
			40, Native::OnChatInput, char**
		);
		DETACH_EVENT
		(
			ChatMessage,
			41, Native::OnChatMessage, Native::AIHeroClient*, char**
		);
		DETACH_EVENT
		(
			ChatClientSideMessage,
			42, Native::OnChatClientSideMessage, char**
		);
		DETACH_EVENT
		(
			ChatSendWhisper,
			43, Native::OnChatSendWhisper, char**, char**
		);
	}

	bool Chat::OnChatInputNative( char** message )
	{
		bool process = true;

		START_TRACE
			if (strlen(*message) > 0)
			{
				auto args = gcnew ChatInputEventArgs( message );
				for each (auto eventHandle in ChatInputHandlers->ToArray())
				{
					START_TRACE
						eventHandle( args );

						if (!args->Process)
							process = false;
					END_TRACE
				}
			}
		END_TRACE

		return process;
	}

	bool Chat::OnChatMessageNative( Native::Obj_AI_Base* sender, char** message )
	{
		bool process = true;

		START_TRACE
			if (strlen(*message) > 0 && sender != nullptr)
			{
				//auto managedSender = (AIHeroClient^) ObjectManager::CreateObjectFromPointer( sender );
				auto managedSender = ObjectManager::Player;

				auto args = gcnew ChatMessageEventArgs( managedSender, message );
				for each (auto eventHandle in ChatMessageHandlers->ToArray())
				{
					START_TRACE
						eventHandle( managedSender, args );

						if (!args->Process)
							process = false;
					END_TRACE
				}
			}
		END_TRACE

		return process;
	}

	bool Chat::OnChatClientSideMessageNative( char** message )
	{
		bool process = true;

		START_TRACE
			if (strlen(*message) > 0)
			{
				auto args = gcnew ChatClientSideMessageEventArgs( message );
				for each (auto eventHandle in ChatClientSideMessageHandlers->ToArray())
				{
					START_TRACE
						eventHandle( args );

						if (!args->Process)
							process = false;
					END_TRACE
				}
			}
		END_TRACE

		return process;
	}

	bool Chat::OnChatSendWhisperNative( char** sender, char** message )
	{
		bool process = true;

		START_TRACE
			if (strlen( *message ) > 0)
			{
				auto args = gcnew ChatWhisperEventArgs( sender, message );
				for each (auto eventHandle in ChatSendWhisperHandlers->ToArray())
				{
					START_TRACE
						eventHandle( args );

					if (!args->Process)
						process = false;
					END_TRACE
				}
			}
		END_TRACE

		return process;
	}

	void Chat::Print( String^ text )
	{
		auto ansi = Marshal::StringToHGlobalAnsi( text );
		auto pwConsole = Native::pwConsole::GetInstance();
		if (pwConsole != nullptr)
		{
			pwConsole->ShowClientSideMessage( reinterpret_cast<char*>( ansi.ToPointer()) );
		}

		Marshal::FreeHGlobal( ansi );
	}

	void Chat::Print( String^ format, ... array<Object^>^ params )
	{
		auto string = gcnew StringBuilder();
		string->AppendFormat( format, params );
		Chat::Print( string->ToString() );
	}

	//Color overloads
	void Chat::Print( String^ text, System::Drawing::Color color )
	{
		auto formattedText = String::Format( "<font color='#{0}'>{1}</font>",
			color.R.ToString( "X2" ) + color.G.ToString( "X2" ) + color.B.ToString( "X2" ),
			text
			);

		Chat::Print( formattedText );
	}

	void Chat::Print( String^ format, System::Drawing::Color color, ... array<Object^>^ params )
	{
		auto string = gcnew StringBuilder();
		string->AppendFormat( format, params );

		auto formattedText = String::Format( "<font color='#{0}'>{1}</font>",
			color.R.ToString( "X2" ) + color.G.ToString( "X2" ) + color.B.ToString( "X2" ),
			string
			);

		Chat::Print( formattedText );
	}

	void Chat::Print(Object^ object)
	{
		Chat::Print( object->ToString() );
	}

	void Chat::Show()
	{
		auto pwConsole = Native::pwConsole::GetInstance();
		if (pwConsole != nullptr)
		{
			pwConsole->Show();
		}
	}

	void Chat::Close()
	{
		auto pwConsole = Native::pwConsole::GetInstance();
		if (pwConsole != nullptr)
		{
			pwConsole->Close();
		}
	}

	void Chat::Say( String^ text )
	{
		auto ansi = Marshal::StringToHGlobalAnsi( text );
		auto pwConsole = Native::pwConsole::GetInstance();
		auto menuGUI = Native::MenuGUI::GetInstance();

		if (pwConsole != nullptr && menuGUI != nullptr)
		{
			strcpy_s( menuGUI->GetInput(), 1024, (char*) ansi.ToPointer() );
			pwConsole->ProcessCommand();
		}

		Marshal::FreeHGlobal( ansi );
	}

	void Chat::Say( String^ format, ... array<Object^>^ params )
	{
		auto string = gcnew StringBuilder();
		string->AppendFormat( format, params );
		Chat::Say( string->ToString() );
	}

	bool Chat::IsOpen::get()
	{
		auto menuGUI = Native::MenuGUI::GetInstance();
		if (menuGUI)
		{
			return *menuGUI->GetIsChatOpen();
		}
		return false;
	}

	bool Chat::IsClosed::get()
	{
		auto menuGUI = Native::MenuGUI::GetInstance();
		if (menuGUI)
		{
			return !*menuGUI->GetIsChatOpen();
		}
		return false;
	}
}