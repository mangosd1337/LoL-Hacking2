#include "stdafx.h"
#include "Game.h"
#include "EventHandler.h"

#include "ObjectManager.h"
#include "AIHeroClient.h"
#include "Hacks.h"

#include "BuildInfo.h"
#include "MissionInfo.h"
#include "pwHud.h"
#include <future>
#include "Bootstrapper.h"
#include "Memory.h"

#include "Lua.h"

namespace EloBuddy
{
	namespace Native
	{
		MAKE_HOOK<convention_type::cdecl_t, void, void*> Game_OnAfk;
		MAKE_HOOK<convention_type::cdecl_t, void, void*> Game_OnDisconnect;
		MAKE_HOOK<convention_type::stdcall_t, void, int, void*> Game_ClientMainLoop;
		MAKE_HOOK<convention_type::stdcall_t, int, void*> Game_OnStart;
		MAKE_HOOK<convention_type::stdcall_t, void, int, uint, int*> Game_DispatchEvent;
		MAKE_HOOK<convention_type::cdecl_t, void, void*> Game_ProcessInput;

		WNDPROC m_wndProc;
		LRESULT WINAPI hkWndProc(HWND, UINT, WPARAM, LPARAM);

		DWORD m_lastInputBlock;
		bool m_blockInput;

		Game* Game::GetInstance()
		{
			static auto inst = new Game();
			return inst;
		}

		bool Game::ApplyHooks() const
		{
			auto hWindow = FindWindow(nullptr, "League of Legends (TM) Client");
			m_wndProc = reinterpret_cast<WNDPROC>(SetWindowLongPtr(FindWindow(nullptr, "League of Legends (TM) Client"), GWL_WNDPROC, static_cast<LONG>(reinterpret_cast<LONG_PTR>(hkWndProc))));

			Game_OnAfk.Apply(MAKE_RVA(Offsets::Game::Hud_OnAfk), [] (void* HudPopup) -> void
			{
				__asm pushad;
				auto player = ObjectManager::GetPlayer();

				if (player && Hacks::GetAntiAFK())
				{
					player->IssueOrder(&player->GetPosition().SwitchYZ(), nullptr, GameObjectOrder::MoveTo, false);
				}
				__asm popad;
			});

			Game_DispatchEvent.Apply(MAKE_RVA(Offsets::Game::DispatchEvent), [] (int eventId, uint networkId, int* unkn1) -> void
			{
				__asm pushad;
#ifdef _DEBUG_BUILD
				Console::PrintLn("EventId: %d - A2: %d - A3: %d", eventId, networkId, unkn1);
#endif
				EventHandler<37, OnGameNotify, uint, int>::GetInstance()->Trigger(networkId, eventId);
				__asm popad;

				Game_DispatchEvent.CallOriginal(eventId, networkId, unkn1);
			});

			Game_ClientMainLoop.Apply(MAKE_RVA(Offsets::Game::ClientMainLoop), [] (int unkn1, void* ClientGameMetrics) -> void
			{
				__asm pushad;
					EventHandler<2, OnGameUpdate>::GetInstance()->Trigger();

					auto const gameInst = GetInstance();
					gameInst->TickHandler();
				__asm popad;

				Game_ClientMainLoop.CallOriginal(unkn1, ClientGameMetrics);
			});

			/*Game_ProcessInput.Apply(MAKE_RVA(0x00E52E6E), [] (void* p) -> void
			{
				if (!m_blockInput)
				{
					 Game_ProcessInput.CallOriginal(p);
				}
			});*/

			Memory::NOP(MAKE_RVA(Offsets::Game::PingNOP1), 2);
			Memory::NOP(MAKE_RVA(Offsets::Game::PingNOP2), 6);

			return Game_OnAfk.IsApplied()
				&& Game_ClientMainLoop.IsApplied()
				&& Game_DispatchEvent.IsApplied();
		}

		LRESULT WINAPI hkWndProc(HWND hwnd, uint msg, WPARAM WParam, LPARAM LParam)
		{
			auto process = EventHandler<1, OnWndProc, HWND, UINT, WPARAM, LPARAM>::GetInstance()->TriggerProcess(hwnd, msg, WParam, LParam);
			LRESULT returnValue;

			if (msg == WM_KEYUP)
			{
				if (WParam == VK_F5)
				{
					Bootstrapper::GetInstance()->Trigger(BootstrapEventType::Load);
				}

				if (WParam == VK_F1)
				{
					//auto lua = new Lua();
				}

				//if (WParam == VK_F2)
				//{
				//	Console::PrintLn("Creating Object");

				//	char* objectName = "KeepoHD";
				//	auto pCreateObject = MAKE_RVA(0x56E750);
				//	auto pVec = new Vector3f(526, -246, 4161);

				//	auto player = ObjectManager::GetPlayer();
				//	auto charInfo = *(DWORD*)((*player->GetCharData()) + 0x18);

				//	auto myPlayerXD = malloc(0x500000);
				//	memcpy(myPlayerXD, player, 0x500000);

				//	__asm
				//	{
				//		mov edx, objectName
				//		mov ecx, myPlayerXD
				//		push 0
				//		push 0
				//		push pVec
				//		push charInfo
				//		call [pCreateObject]
				//	}

				//}
			}

			if (!process)
			{
				m_blockInput = true;
				m_lastInputBlock = GetTickCount();
			} else
			{
				/*if (GetTickCount() - m_lastInputBlock > 1000)
				{
					m_blockInput = false;
				}*/

				return CallWindowProc(m_wndProc, hwnd, msg, WParam, LParam);
			}

			return 1;
		}

		void Game::FromBehind_LeagueSharp_Sucks() {};

		void Game::SetTPS(int ticks)
		{
			m_ticksPerSecond = ticks;
		}

		int Game::GetTPS() const
		{
			return m_ticksPerSecond;
		}

		void Game::TickHandler()
		{
			if (this->m_ticksPerSecond == 0)
			{
				m_ticksPerSecond = 30;
			}

			if (GetTickCount() - m_lastTick >= 1000 / m_ticksPerSecond)
			{
				m_lastTick = GetTickCount();

				EventHandler<31, OnGamePreTick>::GetInstance()->Trigger();
				EventHandler<32, OnGameTick>::GetInstance()->Trigger();
				EventHandler<33, OnGamePostTick>::GetInstance()->Trigger();
			}
		}

		void Game::ExportFunctions()
		{
			/*module(LuaEz::GetMainState(), "Game")
				[
					//Properties
					def<const char*()>("BuildDate", []
					{
						return static_cast<const char*>(BuildInfo::GetBuildDate());
					}),

						def<const char*()>("BuildTime", []
					{
						return static_cast<const char*>(BuildInfo::GetBuildTime());
					}),

						def<const char*()>("BuildType", []
					{
						return static_cast<const char*>(BuildInfo::GetBuildType());
					}),

						def<const char*()>("Version", []
					{
						return static_cast<const char*>(BuildInfo::GetBuildVersion());
					}),

						def<const char*()>("IP", []
					{
						return static_cast<const char*>(ClientFacade::GetInstance()->GetIP()->c_str());
					}),

						def<const char*()>("Region", []
					{
						return static_cast<const char*>(ClientFacade::GetInstance()->GetRegion()->c_str());
					}),

						def<uint()>("GameId", []
					{
						return *ClientFacade::GetInstance()->GetGameId();
					}),

						def<int()>("Ping", []
					{
						return 0;
					}),

						def<int()>("Port", []
					{
						return *ClientFacade::GetInstance()->GetPort();
					}),

						def<int()>("TicksPerSecond", []
					{
						return Game::GetInstance()->GetTPS();
					}),

						//Cursor Positions XYZ
						def<float()>("CursorPosX", []
					{
						auto pwHud = pwHud::GetInstance();
						if (pwHud != nullptr)
						{
							auto hudManager = pwHud->GetHudManager();
							if (hudManager != nullptr)
							{
								return hudManager->GetVirtualCursorPos()->GetX();
							}
						}

						return static_cast<float>(0);
					}),

						def<float()>("CursorPosY", []
					{
						auto pwHud = pwHud::GetInstance();
						if (pwHud != nullptr)
						{
							auto hudManager = pwHud->GetHudManager();
							if (hudManager != nullptr)
							{
								return hudManager->GetVirtualCursorPos()->GetY();
							}
						}

						return static_cast<float>(0);
					}),

						def<float()>("CursorPosZ", []
					{
						auto pwHud = pwHud::GetInstance();
						if (pwHud != nullptr)
						{
							auto hudManager = pwHud->GetHudManager();
							if (hudManager != nullptr)
							{
								return hudManager->GetVirtualCursorPos()->GetZ();
							}
						}

						return static_cast<float>(0);
					}),

						def<float()>("CursorPos2DX", []
					{
						auto pwHud = pwHud::GetInstance();
						if (pwHud != nullptr)
						{
							auto hudManager = pwHud->GetHudManager();
							if (hudManager != nullptr)
							{
								return hudManager->GetCursorPos2D().GetX();
							}
						}

						return static_cast<float>(0);
					}),

						def<float()>("CursorPos2DY", []
					{
						auto pwHud = pwHud::GetInstance();
						if (pwHud != nullptr)
						{
							auto hudManager = pwHud->GetHudManager();
							if (hudManager != nullptr)
							{
								return hudManager->GetCursorPos2D().GetX();
							}
						}

						return static_cast<float>(0);
					}),

						def<float()>("CursorPos2DZ", []
					{
						auto pwHud = pwHud::GetInstance();
						if (pwHud != nullptr)
						{
							auto hudManager = pwHud->GetHudManager();
							if (hudManager != nullptr)
							{
								return hudManager->GetCursorPos2D().GetZ();
							}
						}

						return static_cast<float>(0);
					})
				];

			DPRINT("Game::ExportFunctions() exported");*/
		}

		bool Game::QuitGame()
		{
			return false;
		}

		float Game::GetFPS() const
		{
			return *reinterpret_cast<float*>(MAKE_RVA(Offsets::r3dRenderer::FPS));
		}
	}
}
