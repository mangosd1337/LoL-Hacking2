#include "stdafx.h"
#include "Bootstrapper.h"
#include "ClientFacade.h"
#include "EventHandler.h"
#include <future>

namespace EloBuddy
{
	namespace Native
	{
		Bootstrapper* Bootstrapper::GetInstance()
		{
			static auto* instance = new Bootstrapper();
			return instance;
		}

		bool Bootstrapper::Initialize()
		{
			__try
			{
				if (!this->LoadMemoryLayout())
				{
					Console::PrintLn("[!] Failed to load memory layout, falling back on paths.");
					m_isFallback = true;
				}
				else
				{
					m_isFallback = false;
				}

				if (!this->InjectWrapper())
				{
					Console::PrintLn("[!] Failed to inject EloBuddy.dll, error: 0x%08x", GetLastError());
					return false;
				}

				if (!this->HostClr())
				{
					Console::PrintLn("[!] Failed to host CLR, error: 0x%08x", GetLastError());
					return false;
				}

				return this->LoadSandbox();
			}
			__except (1)
			{
				Console::PrintLn("[!] Bootstraper exception, error: 0x%08x", GetLastError());
				return false;
			}
		}

		bool Bootstrapper::LoadMemoryLayout()
		{
			__try
			{
				auto hMapFile = OpenFileMappingA(FILE_MAP_READ, FALSE, "Local\\EloBuddy");

				if (hMapFile != nullptr)
				{
					auto pMemoryFile = static_cast<BootstrapMemoryLayout*>(MapViewOfFile(hMapFile, FILE_MAP_READ, 0, 0, 1024));

					if (pMemoryFile != nullptr)
					{
						auto lpMemoryLayout = malloc(sizeof(BootstrapMemoryLayout));
						memcpy(lpMemoryLayout, pMemoryFile, sizeof(BootstrapMemoryLayout));
						m_bsMemoryLayout = static_cast<BootstrapMemoryLayout*>(lpMemoryLayout);

						UnmapViewOfFile(pMemoryFile);
						CloseHandle(hMapFile);

						return true;
					}

					Console::PrintLn("[!] MapViewOfFile error: 0x%08x", GetLastError());
					CloseHandle(hMapFile);
				}
				else
				{
					Console::PrintLn("[!] OpenFileMapping error: 0x%08x", GetLastError());
				}
			}
			__except (1)
			{
				Console::PrintLn("[!] FileMapping exception, error: 0x%08x", GetLastError());
				return false;
			}

			return false;
		}

		void Bootstrapper::Trigger(BootstrapEventType eventType)
		{
			switch (eventType)
			{
			case BootstrapEventType::Load:
				if (ClientFacade::GetInstance()->GetGameState() == static_cast<int>(GameMode::Running))
					EventHandler<3, OnGameStart>::GetInstance()->Trigger();

				if (ClientFacade::GetInstance()->GetGameState() == static_cast<int>(GameMode::Connecting))
					EventHandler<26, OnGameLoad>::GetInstance()->Trigger();
				break;
			}
		}

		bool Bootstrapper::HostClr()
		{
			CLRCreateInstance(CLSID_CLRMetaHost, IID_ICLRMetaHost, reinterpret_cast<LPVOID*>(&this->pMetaHost));
			this->pMetaHost->GetRuntime(L"v4.0.30319", IID_PPV_ARGS(&pRuntimeInfo));
			pRuntimeInfo->GetInterface(CLSID_CLRRuntimeHost, IID_PPV_ARGS(&this->pRuntimeHost));

			auto hr = this->pRuntimeHost->Start();

			return SUCCEEDED(hr);
		}

		bool Bootstrapper::InjectWrapper()
		{
			auto path = m_isFallback
				            ? Utils::GetDllPath(TEXT("EloBuddy.dll"))
				            : m_bsMemoryLayout->EloBuddyDllPath;

			return LoadLibraryW(path) != nullptr;
		}

		bool Bootstrapper::LoadSandbox()
		{
			VMProtectBeginVirtualization(__FUNCTION__);

			auto path = m_isFallback
				            ? Utils::GetDllPath(TEXT("EloBuddy.Sandbox.dll"))
				            : m_bsMemoryLayout->SandboxDllPath;

			DWORD dwRetCode = 0;
			auto hr = this->pRuntimeHost->ExecuteInDefaultAppDomain(
				              path,
				              L"EloBuddy.Sandbox.Sandbox",
				              L"Bootstrap",
				              L"EloBuddy.Testing.exe",
				              &dwRetCode
			              );

			if (FAILED(hr))
			{
				Console::PrintLn("[!] Sandbox failed: %08x", hr);
			}

			VMProtectEnd();

			return SUCCEEDED(hr);
		}

		void Bootstrapper::SetMemoryLayout(BootstrapMemoryLayout* layout)
		{
			this->m_bsMemoryLayout = layout;
		}

		BootstrapMemoryLayout* Bootstrapper::GetMemoryLayout()
		{
			return this->m_bsMemoryLayout;
		}
	}
}

