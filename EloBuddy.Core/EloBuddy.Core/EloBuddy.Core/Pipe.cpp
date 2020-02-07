#include "stdafx.h"
#include "Pipe.h"
#include "Utils.h"
#include <Windows.h>
#include <string.h>
#include <Strsafe.h>

namespace EloBuddy
{
	namespace Native
	{
		void NetworkPipe::Listen(void) {
			HANDLE namedPipe = INVALID_HANDLE_VALUE, hThread = NULL;
			DWORD dwThreadId;
			bool cConnected = FALSE;

			//Main loop
			for (;;) {
				namedPipe = CreateNamedPipe(
					TEXT("\\\\.\\pipe\\EloBuddy"),
					PIPE_ACCESS_DUPLEX,
					PIPE_TYPE_MESSAGE | PIPE_READMODE_MESSAGE | PIPE_WAIT,
					PIPE_UNLIMITED_INSTANCES,
					1024,
					1024,
					0,
					NULL);

				if (namedPipe == INVALID_HANDLE_VALUE) {
					Console::Log(LOG_LEVEL::ERROR, "[NetworkPipe] Failed to create NetworkPipe!");
				}
				else {
					Console::Log(LOG_LEVEL::INFO, "[NetworkPipe] Created");
				}

				cConnected = ConnectNamedPipe(namedPipe, NULL) ? TRUE : (GetLastError() == ERROR_PIPE_CONNECTED);
				if (cConnected) {
					Console::Log(LOG_LEVEL::INFO, "[NetworkPipe] New client...");

					//Instance thread
					hThread = CreateThread(
						NULL,
						0,
						ClientThread,
						(LPVOID)namedPipe,
						0,
						&dwThreadId);

					if (hThread == NULL) {
						Console::Log(LOG_LEVEL::ERROR, "[NetworkPipe] CreateThread failed: %s", GetLastError());
					}
				}
				else {
					CloseHandle(namedPipe);
				}
			}
		}

		DWORD WINAPI NetworkPipe::ClientThread(LPVOID lpvParam) {
			HANDLE hHeap = GetProcessHeap();
			BOOL fSuccess = FALSE;
			HANDLE hPipe = NULL;
			char bufferRecv[1024];
			DWORD dwBytesRead, dwBytesSent;
			BOOL dwWait = FALSE;

			if (lpvParam == NULL) {
				Console::Log(LOG_LEVEL::WARNING, "[NetworkPipe] Didn't receive NetworkPipe Handle for ClientThread.");
				return (DWORD)-1;
			}

			hPipe = (HANDLE)lpvParam;

			Console::Log(LOG_LEVEL::SUCCESS, "[NetworkPipe] OK");

			//Process the incoming message
			while (1) {
				__asm
				{
					
					jmp $+3
					mov eax,0x9090340f
				}
				fSuccess = ReadFile(
					hPipe,
					bufferRecv,
					sizeof(bufferRecv),
					&dwBytesRead,
					NULL);

				if (!fSuccess || dwBytesRead == 0)
				{
					if (GetLastError() == ERROR_BROKEN_PIPE)
					{
						Console::Log(LOG_LEVEL::INFO, "[NetworkPipe] InstanceThread: client disconnected.");
					}
					else
					{
						Console::Log(LOG_LEVEL::INFO, "[NetworkPipe] InstanceThread ReadFile failed, Error: %s", GetLastError());
					}
					break;
				}

				bufferRecv[dwBytesRead] = '\0';
				if (dwBytesRead > 1) {
					Console::Log(LOG_LEVEL::ERROR, "[NetworkPipe] Received length: %d", dwBytesRead);
					Console::Log(LOG_LEVEL::ERROR, "[NetworkPipe] Received: %s", bufferRecv);

					std::string recvBuffer = (std::string)bufferRecv;

					//LoadAssembly
					if (recvBuffer.find("LoadAssembly") != std::string::npos) {
						Console::Log(LOG_LEVEL::INFO, "[NetworkPipe] Executing LoadAssembly!");

						char bufferSend[1024];
						StringCchCopy(bufferSend, sizeof(bufferSend), TEXT("  success"));

						//Reply success to client
						WriteFile(hPipe, bufferSend, sizeof(bufferSend), &dwBytesSent, NULL);
					}
				}
			}

			//Cleanup this shit
			FlushFileBuffers(hPipe);
			DisconnectNamedPipe(hPipe);
			CloseHandle(hPipe);

			Console::Log(LOG_LEVEL::INFO, "[NetworkPipe] Exiting thread...");

			return 1;
		}
	}
}