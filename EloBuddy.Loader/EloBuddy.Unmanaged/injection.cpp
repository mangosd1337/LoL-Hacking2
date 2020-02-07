// EloBuddy.Unmanaged.cpp : Defines the exported functions for the DLL application.
//

#include "stdafx.h"
#include "injection.h"
#include <stdio.h>
#include <Windows.h>
#include "console.h"
#include <TlHelp32.h> 

unsigned char m_stubLoader [22] =
{
	0x68, 0xef, 0xbe, 0xad, 0xde,   // push {oldEIP}
	0x9c,                           // pushf
	0x60,                           // pusha
	0x68, 0xef, 0xbe, 0xad, 0xde,   // push {pDllPath}
	0xb8, 0xef, 0xbe, 0xad, 0xde,   // mov eax, {pLoadLibrary}
	0xff, 0xd0,                     // call eax
	0x61,                           // popa
	0x9d,                           // popf       
	0xc3                            // ret
};


Injection::Injection( DWORD procId, LPCWSTR path )
{
	this->m_processId = procId;
	this->m_dllPath = path;
	this->m_hProcess = OpenProcess( PROCESS_ALL_ACCESS, false, procId );
	this->m_mainThreadId = this->GetMainThread();
}

Injection::~Injection()
{
	this->m_processId = 0;
}

bool Injection::Inject()
{
	CONTEXT context 
	{ 
		CONTEXT_CONTROL 
	};

	unsigned long oldEIP;
	auto cszDllPath = (wcslen( m_dllPath ) + 1) * sizeof( WCHAR );

	Console::Log( "ProcessId: %d - ProcessHandle: %08x - MainThreadId: %d", this->m_processId, this->m_hProcess, this->m_mainThreadId );

	auto pLoadLibraryAddress = GetProcAddress( GetModuleHandleA( "kernel32.dll" ), "LoadLibraryW" );

	auto stubLen = sizeof( m_stubLoader );
	auto m_threadHandle = OpenThread( THREAD_GET_CONTEXT | THREAD_SET_CONTEXT | THREAD_SUSPEND_RESUME, FALSE, this->m_mainThreadId );

	if (!m_threadHandle)
	{
		return this->CancelInjection( "Failed to open MainThread" );
	}

	auto pszDllPath = VirtualAllocEx( this->m_hProcess, nullptr, cszDllPath, MEM_COMMIT, PAGE_READWRITE );
	auto pStubMemory = VirtualAllocEx( this->m_hProcess, nullptr, stubLen, MEM_COMMIT, PAGE_EXECUTE_READWRITE );

	if (!pszDllPath || !pStubMemory)
	{
		return this->CancelInjection( "Failed to allocate memory" );
	}

	if (SuspendThread( m_threadHandle ) == 0xFFFFFFFF)
	{
		return this->CancelInjection( "Failed to suspend thread" );
	}
	
	if(!GetThreadContext( m_threadHandle, &context ))
	{
		return this->CancelInjection( "Failed to get ThreadContext" );
	}

	oldEIP = context.Eip;

	memcpy( static_cast<void*>(m_stubLoader + 1), &oldEIP, 4 );
	memcpy( static_cast<void*>(m_stubLoader + 8), &pszDllPath, 4 );
	memcpy( static_cast<void*>(m_stubLoader + 13), &pLoadLibraryAddress, 4 );

	if (!WriteProcessMemory( this->m_hProcess, pszDllPath, static_cast<LPCVOID>(m_dllPath), cszDllPath, nullptr)
		|| !WriteProcessMemory( this->m_hProcess, pStubMemory, m_stubLoader, stubLen, nullptr ))
	{
		return this->CancelInjection( "WriteProcessMemory failed" );
	}

	FlushInstructionCache( this->m_hProcess, pStubMemory, stubLen );

	context.ContextFlags = CONTEXT_CONTROL;
	context.Eip = reinterpret_cast<DWORD>(pStubMemory);

	if (!SetThreadContext( m_threadHandle, &context ) || ResumeThread( m_threadHandle ) == 0xFFFFFFFF)
	{
		return this->CancelInjection( "SetThreadContext / ResumeThread failed" );
	}

	return CloseHandle(m_threadHandle) && CloseHandle(this->m_hProcess);
}

bool Injection::CancelInjection(char* reason)
{
	Console::Log("Error: %s", reason);
	return false;
}

DWORD Injection::GetMainThread()
{
	DWORD dwMainThreadID = 0;
	auto ullMinCreateTime = MAXULONGLONG;

	auto hThreadSnap = CreateToolhelp32Snapshot( TH32CS_SNAPTHREAD, 0 );
	if (hThreadSnap != INVALID_HANDLE_VALUE) 
	{
		THREADENTRY32 th32;
		th32.dwSize = sizeof( THREADENTRY32 );
		auto bOK = TRUE;
		for (bOK = Thread32First( hThreadSnap, &th32 ); bOK;
			bOK = Thread32Next( hThreadSnap, &th32 )) 
		{
			if (th32.th32OwnerProcessID == m_processId) 
			{
				auto hThread = OpenThread( THREAD_QUERY_INFORMATION, TRUE, th32.th32ThreadID );
				if (hThread) 
				{
					FILETIME afTimes [4] = { 0 };
					if (GetThreadTimes( hThread,
						&afTimes [0], &afTimes [1], &afTimes [2], &afTimes [3] ))
					{
						auto ullTest = MAKEULONGLONG( afTimes [0].dwLowDateTime,
							afTimes [0].dwHighDateTime );
						if (ullTest && ullTest < ullMinCreateTime) 
						{
							ullMinCreateTime = ullTest;
							dwMainThreadID = th32.th32ThreadID;
						}
					}
					CloseHandle( hThread );
				}
			}
		}
		CloseHandle( hThreadSnap );
	}

	return dwMainThreadID;
}