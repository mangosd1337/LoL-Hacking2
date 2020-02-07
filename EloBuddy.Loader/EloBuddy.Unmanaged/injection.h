#pragma once
#include <string>

#ifndef MAKEULONGLONG
#define MAKEULONGLONG(ldw, hdw) ((ULONGLONG(hdw) << 32) | ((ldw) & 0xFFFFFFFF))
#endif

#ifndef MAXULONGLONG
#define MAXULONGLONG ((ULONGLONG)~((ULONGLONG)0))
#endif

class 
	Injection
{
	DWORD m_processId;
	LPCWSTR m_dllPath;

	HANDLE m_hProcess;
	bool m_isInjected;
	DWORD m_mainThreadId;
public:
	Injection( DWORD dwProcId, LPCWSTR path );
	~Injection();

	bool Inject();
	bool CancelInjection( char* reason );

	DWORD GetMainThread();
};