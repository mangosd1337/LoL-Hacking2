#pragma once

using namespace System;
using namespace System::Runtime::InteropServices;

// -------- PROPERTIES

//Create GET from Native code with DEPOINTER
#define CREATE_GET(NAME, TYPE) property TYPE NAME { TYPE get() { auto ptr = this->GetPtr(); if(ptr != nullptr) { return (TYPE) *ptr->Get##NAME(); } return 0; } }
//Create GET from Native code WITHOUT DEPOINTER
#define CREATE_GET_G(NAME, TYPE) property TYPE NAME { TYPE get() { auto ptr = this->GetPtr(); if(ptr != nullptr) { return (TYPE) ptr->Get##NAME(); } return 0; } }

#define MAKE_PROPERTY(NAME, TYPE) property TYPE NAME { TYPE get(); }
#define MAKE_STATIC_PROPERTY(NAME, TYPE) static property TYPE NAME { TYPE get(); }


#define MAKE_STRING(NAME) property System::String^ NAME { System::String^ get() { if(this->GetPtr() != nullptr && this->GetPtr()->Get##NAME() != nullptr) { return gcnew System::String(this->GetPtr()->Get##NAME()->c_str()); } return "Unknown"; } }
#define MAKE_C_STRING(NAME) property System::String^ NAME { System::String^ get() { if(this->GetPtr() != nullptr && this->GetPtr()->Get##NAME() != nullptr) { return gcnew System::String(this->GetPtr()->Get##NAME()); } return "Unknown"; } }
#define MAKE_CC_STRING(NAME) property System::String^ NAME { System::String^ get() { if(this->GetPtr() != nullptr && *this->GetPtr()->Get##NAME() != nullptr) { return gcnew System::String(*this->GetPtr()->Get##NAME()); } return "Unknown"; } }

#define MAKE_PROPERTY_INLINE(NAME, TYPE, CLASS) property TYPE NAME { TYPE get() { \
	if (CLASS != nullptr) { return (TYPE)*this->##CLASS##->Get##NAME( ); } return 0; \
} }

#define DEF_STRING(STRING) auto str = (char*)(Marshal::StringToHGlobalAnsi(STRING).ToPointer());
#define DEF_INLINE_STRING(STRING) (char*)(Marshal::StringToHGlobalAnsi(STRING).ToPointer())


#define START_TRACE try { 
#define END_TRACE } catch (System::Exception^ ex) {\
	System::Console::WriteLine();\
	System::Console::WriteLine("========================================");\
	System::Console::WriteLine("Exception occured! EloBuddy might crash!");\
	System::Console::WriteLine();\
	System::Console::WriteLine("Type: {0}", ex->GetType()->FullName);\
	System::Console::WriteLine("Message: {0}", ex->Message);\
	System::Console::WriteLine();\
	System::Console::WriteLine("Stracktrace:");\
	System::Console::WriteLine(ex->StackTrace);\
	auto e = ex->InnerException;\
	if (e != nullptr) {\
		System::Console::WriteLine();\
		System::Console::WriteLine("InnerException(s):");\
		do {\
			System::Console::WriteLine("----------------------------------------");\
			System::Console::WriteLine("Type: {0}", e->GetType()->FullName);\
			System::Console::WriteLine("Message: {0}", e->Message);\
			System::Console::WriteLine();\
			System::Console::WriteLine("Stracktrace:");\
			System::Console::WriteLine(e->StackTrace);\
			e = e->InnerException;\
		} while (e != nullptr);\
		System::Console::WriteLine("----------------------------------------");\
	}\
	System::Console::WriteLine("========================================");\
	System::Console::WriteLine();\
}


#define END_TRACE_THROW(EXCEPTION) } catch (System::Exception^) { throw gcnew EXCEPTION(); }

#define MAKE_ARRAY(NAME, TYPE, SIZE) property array<TYPE>^ NAME { array<TYPE>^ get( ) { \
	 auto ptr = this->GetPtr(); \
	 if(ptr != nullptr) { \
		 auto sd = ptr->Get##NAME(); \
		 auto retArray = gcnew array<TYPE>(SIZE); \
		 for (int i = 0; i < SIZE; i++) { \
			retArray [i] = *(TYPE*) (sd + i); \
		 } \
		return retArray; \
	 } \
	return nullptr; } \
};

// -------- AIHeroClient

#define MAKE_HERO_STAT(NAME, NATIVE_NAME, TYPE, OFFSET) \
	TYPE AIHeroClient::##NAME::get( ) { \
		auto ptr = this->GetPtr(); \
		if(ptr != nullptr) { \
			auto heroStatCollection = ptr->GetHeroStatsCollection(); \
			if (heroStatCollection != nullptr) { \
				auto heroStat = heroStatCollection->GetHeroStat(NATIVE_NAME); \
				if (heroStat != nullptr) { \
					return heroStat->GetValue<TYPE>(OFFSET); \
				} \
			} \
		} \
		return -1; \
	} 

// -------- Obj_AI_Base

#define CREATE_CHARACTER_INTERMEDIATE(NAME) \
	property float NAME { \
		float get( ) { \
			auto ptr = this->GetPtr(); \
			if (ptr != nullptr) { return *ptr->GetCharacterIntermediate( )->Get##NAME( ); } \
			return 0; \
		} \
	} \


// -------- EventHandler

#define ATTACH_DOMAIN() System::AppDomain::CurrentDomain->DomainUnload += gcnew System::EventHandler(DomainUnloadEventHandler)

#define MAKE_EVENT_GLOBAL(EVENTNAME, ...) public delegate void EVENTNAME ( __VA_ARGS__ )
#define MAKE_EVENT_INTERNAL(EVENTNAME, NATIVEARGS) \
	[UnmanagedFunctionPointer( CallingConvention::Cdecl )] \
	delegate void On##EVENTNAME##NativeDelegate ##NATIVEARGS; \
	static On##EVENTNAME##NativeDelegate^ m_##EVENTNAME##NativeDelegate; \
	static void On##EVENTNAME##Native ##NATIVEARGS; \
	static System::Collections::Generic::List<EVENTNAME^>^ EVENTNAME##Handlers; \
	static System::IntPtr m_##EVENTNAME##Native;

#define MAKE_EVENT_PUBLIC(EVENTNAME, EVENTHANDLER) static event EVENTHANDLER^ EVENTNAME { \
	void add(EVENTHANDLER^ handler) { EVENTHANDLER##Handlers->Add(handler); } \
	void remove(EVENTHANDLER^ handler) { EVENTHANDLER##Handlers->Remove(handler); } \
}
#define MAKE_EVENT_INTERNAL_PROCESS(EVENTNAME, NATIVEARGS) \
	[UnmanagedFunctionPointer( CallingConvention::Cdecl )] \
	delegate bool On##EVENTNAME##NativeDelegate ##NATIVEARGS; \
	static On##EVENTNAME##NativeDelegate^ m_##EVENTNAME##NativeDelegate; \
	static bool On##EVENTNAME##Native ##NATIVEARGS; \
	static System::Collections::Generic::List<EVENTNAME^>^ EVENTNAME##Handlers; \
	static System::IntPtr m_##EVENTNAME##Native;

#define ATTACH_EVENT(EVENTNAME, ...) \
	EVENTNAME##Handlers = gcnew List<EVENTNAME^>( ); \
	m_##EVENTNAME##NativeDelegate = gcnew On##EVENTNAME##NativeDelegate( On##EVENTNAME##Native ); \
	m_##EVENTNAME##Native = Marshal::GetFunctionPointerForDelegate( m_##EVENTNAME##NativeDelegate); \
	Native::EventHandler<__VA_ARGS__>::GetInstance()->Add(m_##EVENTNAME##Native##.ToPointer()); \

#define DETACH_EVENT(EVENTNAME, ...) Native::EventHandler<__VA_ARGS__>::GetInstance( )->Remove( m_##EVENTNAME##Native##.ToPointer( ) );