#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/pwConsole.h"
#include "../../EloBuddy.Core/EloBuddy.Core/MenuGUI.h"
#include "../../EloBuddy.Core/EloBuddy.Core/Obj_AI_Base.h"

#include "Macros.hpp"
#include "ChatEventArgs.hpp"
#include "Obj_AI_Base.hpp"

using namespace System;
using namespace System::Text;

namespace EloBuddy
{
	MAKE_EVENT_GLOBAL( ChatInput, ChatInputEventArgs^ args );
	MAKE_EVENT_GLOBAL( ChatMessage, AIHeroClient^ sender, ChatMessageEventArgs^ args );
	MAKE_EVENT_GLOBAL( ChatClientSideMessage, ChatClientSideMessageEventArgs^ args );
	MAKE_EVENT_GLOBAL( ChatSendWhisper, ChatWhisperEventArgs^ args );

	public ref class Chat
	{
	internal:
		MAKE_EVENT_INTERNAL_PROCESS( ChatInput, (char**) );
		MAKE_EVENT_INTERNAL_PROCESS( ChatMessage, (Native::Obj_AI_Base*, char**) );
		MAKE_EVENT_INTERNAL_PROCESS( ChatClientSideMessage, (char**) );
		MAKE_EVENT_INTERNAL_PROCESS( ChatSendWhisper, (char**, char**) );
	public:
		MAKE_EVENT_PUBLIC( OnInput, ChatInput );
		MAKE_EVENT_PUBLIC( OnMessage, ChatMessage );
		MAKE_EVENT_PUBLIC( OnClientSideMessage, ChatClientSideMessage );
		MAKE_EVENT_PUBLIC( OnSendWhisper, ChatSendWhisper );

		static Chat();
		static void DomainUnloadEventHandler( Object^, EventArgs^ );

		static void Print( String^ text );
		static void Print( String^ format, ... array<Object^>^ params );
		static void Print( String^ text, System::Drawing::Color color );
		static void Print( String^ format, System::Drawing::Color color, ... array<Object^>^ params );
		static void Print( Object^ object );

		static void Show();
		static void Close();

		static void Say( System::String^ text );
		static void Say( String^ format, ... array<Object^>^ params );

		MAKE_STATIC_PROPERTY( IsOpen, bool );
		MAKE_STATIC_PROPERTY( IsClosed, bool );
	};
}