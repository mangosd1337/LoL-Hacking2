#include "stdafx.h"
#include "Detour.hpp"

#pragma region Meh
#include "Bootstrapper.h"
#include "Game.h"
#include "pwConsole.h"
#include "RiotX3D.h"
#include "ClientNode.h"
#include "ClientFacade.h"
#include "Actor_Common.h"
#include "ObjectManager.h"
#include "AIHeroClient.h"
#include "HeroInventory.h"
#include "MenuGUI.h"
#include "BuffHost.h"
#include "HudGameObjectChat.h"
#include "pwHud.h"
#include "FoundryItemShop.h"
#include "NetClient.h"
#include "HudVote.h"
#include "BuildInfo.h"
#include "AudioManager.h"
#pragma endregion Meh

#ifndef MANAGED_BUILD
	#include <boost/format.hpp>
#endif

#include "Patchables.h"
#include "MissionInfo.h"
#include "r3dRenderLayer.h"

// ReSharper disable CppMemberFunctionMayBeStatic
namespace EloBuddy
{
	namespace Native
	{
		template <int uniqueEventNumber, typename T, typename ... TArgs>
		EventHandler<uniqueEventNumber, T, TArgs...>* EventHandler<uniqueEventNumber, T, TArgs...>::instance = nullptr;

		int Core::mainModule = 0;
		void BootstrapAddons();

		Core::Core(HMODULE h_module)
		{
			VMProtectBeginUltra(__FUNCTION__);

			this->hModule = h_module;

			srand(time(nullptr));
			Patchables::Initialize();

			if (strcmp(BuildInfo::GetBuildType(), LOL_VERSION) != 0)
			{
				Console::PrintLn("Error: Invalid League of Legends version.");
				Console::PrintLn("EloBuddy.Core.dll Version: %s", LOL_VERSION);
				Console::PrintLn("Please redownload EloBuddy or check for updates.");

				return;
			}

			Console::PrintLn("EloBuddy (%s - %s %s) loading... ", BuildInfo::GetBuildVersion(), __TIME__, __DATE__);

			if (!this->ApplyHooks())
			{
				Console::PrintLn("3123911");
			} else
			{
				this->CreateThreadBootstrapAddons();
				this->DisplayWelcomeMessage();
			}

			VMProtectEnd();
		}

		Core::~Core()
		{

		}

		void Core::CreateThreadBootstrapAddons() const
		{
			CreateThread(nullptr, NULL, reinterpret_cast<LPTHREAD_START_ROUTINE>(BootstrapAddons), nullptr, NULL, nullptr);
		}

		void Core::DisplayWelcomeMessage() const
		{
			while (ClientFacade::GetInstance()->GetGameState() != static_cast<int>(GameState::Running))
			{
				Sleep(0xFF);
			}

			auto pwConsole = pwConsole::GetInstance();

			if (pwConsole != nullptr)
			{
				pwConsole->ShowClientSideMessage(
					(boost::format("<font color=\"#40c1ff\">EloBuddy (%s) - built</font> <font color=\"#39FF14\">%s</font> loaded") % BuildInfo::GetBuildVersion() % __DATE__).str().c_str());
				pwConsole->ShowClientSideMessage("<font color\"#ffffff\">visit www.elobuddy.net for addons</font>");
			}

			#pragma region Event Linking

			EventHandler<1, OnWndProc, HWND, UINT, WPARAM, LPARAM>::GetInstance()->Add(nullptr);
			EventHandler<1, OnWndProc, HWND, UINT, WPARAM, LPARAM>::GetInstance()->Remove(nullptr);
			EventHandler<2, OnGameUpdate>::GetInstance()->Add(nullptr);
			EventHandler<2, OnGameUpdate>::GetInstance()->Remove(nullptr);
			EventHandler<3, OnGameStart>::GetInstance()->Add(nullptr);
			EventHandler<3, OnGameStart>::GetInstance()->Remove(nullptr);
			EventHandler<4, OnGameEnd>::GetInstance()->Add(nullptr);
			EventHandler<4, OnGameEnd>::GetInstance()->Remove(nullptr);
			EventHandler<26, OnGameLoad>::GetInstance()->Add(nullptr);
			EventHandler<26, OnGameLoad>::GetInstance()->Remove(nullptr);
			EventHandler<31, OnGamePreTick>::GetInstance()->Add(nullptr);
			EventHandler<31, OnGamePreTick>::GetInstance()->Remove(nullptr);
			EventHandler<32, OnGameTick>::GetInstance()->Add(nullptr);
			EventHandler<32, OnGameTick>::GetInstance()->Remove(nullptr);
			EventHandler<33, OnGamePostTick>::GetInstance()->Add(nullptr);
			EventHandler<33, OnGamePostTick>::GetInstance()->Remove(nullptr);
			EventHandler<34, OnGameAfk>::GetInstance()->Add(nullptr);
			EventHandler<34, OnGameAfk>::GetInstance()->Remove(nullptr);
			EventHandler<35, OnGameDisconnect>::GetInstance()->Add(nullptr);
			EventHandler<35, OnGameDisconnect>::GetInstance()->Remove(nullptr);
			EventHandler<37, OnGameNotify, uint, int>::GetInstance()->Add(nullptr);
			EventHandler<37, OnGameNotify, uint, int>::GetInstance()->Remove(nullptr);
			//Drawing
			EventHandler<5, OnDrawingBeginScene>::GetInstance()->Add(nullptr);
			EventHandler<5, OnDrawingBeginScene>::GetInstance()->Remove(nullptr);
			EventHandler<6, OnDrawingDraw>::GetInstance()->Add(nullptr);
			EventHandler<6, OnDrawingDraw>::GetInstance()->Remove(nullptr);
			EventHandler<7, OnDrawingEndScene>::GetInstance()->Add(nullptr);
			EventHandler<7, OnDrawingEndScene>::GetInstance()->Remove(nullptr);
			EventHandler<8, OnDrawingPostReset>::GetInstance()->Add(nullptr);
			EventHandler<8, OnDrawingPostReset>::GetInstance()->Remove(nullptr);
			EventHandler<9, OnDrawingPreReset>::GetInstance()->Add(nullptr);
			EventHandler<9, OnDrawingPreReset>::GetInstance()->Remove(nullptr);
			EventHandler<10, OnDrawingPresent>::GetInstance()->Add(nullptr);
			EventHandler<10, OnDrawingPresent>::GetInstance()->Remove(nullptr);
			EventHandler<11, OnDrawingSetRenderTarget>::GetInstance()->Add(nullptr);
			EventHandler<11, OnDrawingSetRenderTarget>::GetInstance()->Remove(nullptr);
			EventHandler<45, OnDrawingFlushEndScene>::GetInstance()->Add(nullptr);
			EventHandler<45, OnDrawingFlushEndScene>::GetInstance()->Remove(nullptr);
			EventHandler<80, OnDrawingHealthBars, UnitInfoComponent*, AttackableUnit*>::GetInstance()->Add(nullptr);
			EventHandler<80, OnDrawingHealthBars, UnitInfoComponent*, AttackableUnit*>::GetInstance()->Remove(nullptr);
			//GameObject
			EventHandler<13, OnGameObjectCreate, GameObject*>::GetInstance()->Add(nullptr);
			EventHandler<13, OnGameObjectCreate, GameObject*>::GetInstance()->Remove(nullptr);
			EventHandler<24, OnGameObjectDelete, GameObject*>::GetInstance()->Add(nullptr);
			EventHandler<24, OnGameObjectDelete, GameObject*>::GetInstance()->Remove(nullptr);
			EventHandler<61, OnGameObjectFloatPropertyChange, GameObject*, const char*, float>::GetInstance()->Add(nullptr);
			EventHandler<61, OnGameObjectFloatPropertyChange, GameObject*, const char*, float>::GetInstance()->Remove(nullptr);
			EventHandler<62, OnGameObjectIntegerPropertyChange, GameObject*, const char*, int>::GetInstance()->Add(nullptr);
			EventHandler<62, OnGameObjectIntegerPropertyChange, GameObject*, const char*, int>::GetInstance()->Remove(nullptr);
			//Obj_AI_Base
			EventHandler<14, OnObjAIBaseProcessSpellcast, Obj_AI_Base*, SpellCastInfo*>::GetInstance()->Add(nullptr);
			EventHandler<14, OnObjAIBaseProcessSpellcast, Obj_AI_Base*, SpellCastInfo*>::GetInstance()->Remove(nullptr);
			EventHandler<15, OnObjAIBaseIssueOrder, Obj_AI_Base*, uint, Vector3f*, GameObject*, bool>::GetInstance()->Add(nullptr);
			EventHandler<15, OnObjAIBaseIssueOrder, Obj_AI_Base*, uint, Vector3f*, GameObject*, bool>::GetInstance()->Remove(nullptr);
			EventHandler<16, OnObjAIBaseTeleport, Obj_AI_Base*, char*, char*>::GetInstance()->Add(nullptr);
			EventHandler<16, OnObjAIBaseTeleport, Obj_AI_Base*, char*, char*>::GetInstance()->Remove(nullptr);
			EventHandler<17, OnObjAIBaseNewPath, Obj_AI_Base*, std::vector<Native::Vector3f>*, bool, float>::GetInstance()->Add(nullptr);
			EventHandler<17, OnObjAIBaseNewPath, Obj_AI_Base*, std::vector<Native::Vector3f>*, bool, float>::GetInstance()->Remove(nullptr);
			EventHandler<18, OnObjAIBasePlayAnimation, Obj_AI_Base*, char**>::GetInstance()->Add(nullptr);
			EventHandler<18, OnObjAIBasePlayAnimation, Obj_AI_Base*, char**>::GetInstance()->Remove(nullptr);
			EventHandler<37, OnObjAIBaseAddBuff, Obj_AI_Base*, BuffInstance*>::GetInstance()->Add(nullptr);
			EventHandler<37, OnObjAIBaseAddBuff, Obj_AI_Base*, BuffInstance*>::GetInstance()->Remove(nullptr);
			EventHandler<38, OnObjAIBaseRemoveBuff, Obj_AI_Base*, BuffInstance*>::GetInstance()->Add(nullptr);
			EventHandler<38, OnObjAIBaseRemoveBuff, Obj_AI_Base*, BuffInstance*>::GetInstance()->Remove(nullptr);
			EventHandler<39, OnObjAIBaseUpdateBuff, Obj_AI_Base*, BuffInstance*>::GetInstance()->Add(nullptr);
			EventHandler<39, OnObjAIBaseUpdateBuff, Obj_AI_Base*, BuffInstance*>::GetInstance()->Remove(nullptr);
			EventHandler<44, OnObjAIBaseLevelUp, Obj_AI_Base*, int>::GetInstance()->Add(nullptr);
			EventHandler<44, OnObjAIBaseLevelUp, Obj_AI_Base*, int>::GetInstance()->Remove(nullptr);
			EventHandler<51, OnObjAIBaseUpdateModel, Obj_AI_Base*, char*, int>::GetInstance()->Add(nullptr);
			EventHandler<51, OnObjAIBaseUpdateModel, Obj_AI_Base*, char*, int>::GetInstance()->Remove(nullptr);
			EventHandler<70, OnObjAIBaseUpdatePosition, Obj_AI_Base*, Vector3f*>::GetInstance()->Add(nullptr);
			EventHandler<70, OnObjAIBaseUpdatePosition, Obj_AI_Base*, Vector3f*>::GetInstance()->Remove(nullptr);
			EventHandler<71, OnObjAIBaseDoCast, Obj_AI_Base*, SpellCastInfo*>::GetInstance()->Add(nullptr);
			EventHandler<71, OnObjAIBaseDoCast, Obj_AI_Base*, SpellCastInfo*>::GetInstance()->Remove(nullptr);
			EventHandler<73, OnObjAIBaseBasicAttack, Obj_AI_Base*, SpellCastInfo*>::GetInstance()->Add(nullptr);
			EventHandler<73, OnObjAIBaseBasicAttack, Obj_AI_Base*, SpellCastInfo*>::GetInstance()->Remove(nullptr);
			EventHandler<74, OnObjAIBaseSurrenderVote, Obj_AI_Base*, byte>::GetInstance()->Add(nullptr);
			EventHandler<74, OnObjAIBaseSurrenderVote, Obj_AI_Base*, byte>::GetInstance()->Remove(nullptr);
			//AIHeroClient
			EventHandler<46, OnObjAIHeroDeath, Obj_AI_Base*, float>::GetInstance()->Add(nullptr);
			EventHandler<46, OnObjAIHeroDeath, Obj_AI_Base*, float>::GetInstance()->Remove(nullptr);
			EventHandler<47, OnObjAIHeroSpawn, AIHeroClient*>::GetInstance()->Add(nullptr);
			EventHandler<47, OnObjAIHeroSpawn, AIHeroClient*>::GetInstance()->Remove(nullptr);
			EventHandler<59, OnObjAIHeroApplyCD, AIHeroClient*, SpellDataInst*, uint>::GetInstance()->Add(nullptr);
			EventHandler<59, OnObjAIHeroApplyCD, AIHeroClient*, SpellDataInst*, uint>::GetInstance()->Remove(nullptr);
			//Player
			EventHandler<27, OnPlayerBuyItem, AIHeroClient*, int, ItemNode*>::GetInstance()->Add(nullptr);
			EventHandler<27, OnPlayerBuyItem, AIHeroClient*, int, ItemNode*>::GetInstance()->Remove(nullptr);
			EventHandler<28, OnPlayerSellItem, AIHeroClient*, int, ItemNode*>::GetInstance()->Add(nullptr);
			EventHandler<28, OnPlayerSellItem, AIHeroClient*, int, ItemNode*>::GetInstance()->Remove(nullptr);
			EventHandler<48, OnPlayerSwapItem, AIHeroClient*, uint, uint>::GetInstance()->Add(nullptr);
			EventHandler<48, OnPlayerSwapItem, AIHeroClient*, uint, uint>::GetInstance()->Remove(nullptr);
			EventHandler<72, OnPlayerDoEmote, AIHeroClient*, short>::GetInstance()->Add(nullptr);
			EventHandler<72, OnPlayerDoEmote, AIHeroClient*, short>::GetInstance()->Remove(nullptr);
			//AttackableUnit
			EventHandler<29, OnAttackableUnitModifyShield, AttackableUnit*, float, float>::GetInstance()->Add(nullptr);
			EventHandler<29, OnAttackableUnitModifyShield, AttackableUnit*, float, float>::GetInstance()->Remove(nullptr);
			EventHandler<30, OnAttackableUnitOnDamage, AttackableUnit*, AttackableUnit*, float, DamageLayout*>::GetInstance()->Add(nullptr);
			EventHandler<30, OnAttackableUnitOnDamage, AttackableUnit*, AttackableUnit*, float, DamageLayout*>::GetInstance()->Remove(nullptr);
			//ENet
			EventHandler<19, OnGameSendPacket, C2S_ENetPacket*, uint, uint, DWORD>::GetInstance()->Add(nullptr);
			EventHandler<19, OnGameSendPacket, C2S_ENetPacket*, uint, uint, DWORD>::GetInstance()->Remove(nullptr);
			EventHandler<20, OnGameProcessPacket, S2C_ENetPacket*, uint, uint, DWORD>::GetInstance()->Add(nullptr);
			EventHandler<20, OnGameProcessPacket, S2C_ENetPacket*, uint, uint, DWORD>::GetInstance()->Remove(nullptr);
			//Spellbook
			EventHandler<21, OnSpellbookCastSpell, Obj_AI_Base*, Spellbook*, Vector3f*, Vector3f*, uint, int>::GetInstance()->Add(nullptr);
			EventHandler<21, OnSpellbookCastSpell, Obj_AI_Base*, Spellbook*, Vector3f*, Vector3f*, uint, int>::GetInstance()->Remove(nullptr);
			EventHandler<22, OnSpellbookStopCast, Obj_AI_Base*, bool, bool, bool, bool, int, int>::GetInstance()->Add(nullptr);
			EventHandler<22, OnSpellbookStopCast, Obj_AI_Base*, bool, bool, bool, bool, int, int>::GetInstance()->Remove(nullptr);
			EventHandler<23, OnSpellbookUpdateChargeableSpell, Spellbook*, int, Vector3f*, bool>::GetInstance()->Add(nullptr);
			EventHandler<23, OnSpellbookUpdateChargeableSpell, Spellbook*, int, Vector3f*, bool>::GetInstance()->Remove(nullptr);
			//TacticalMap
			EventHandler<36, OnTacticalMapPing, Vector3f*, GameObject*, GameObject*, uint>::GetInstance()->Add(nullptr);
			EventHandler<36, OnTacticalMapPing, Vector3f*, GameObject*, GameObject*, uint>::GetInstance()->Remove(nullptr);
			//Chat
			EventHandler<40, OnChatInput, char**>::GetInstance()->Add(nullptr);
			EventHandler<40, OnChatInput, char**>::GetInstance()->Remove(nullptr);
			EventHandler<41, OnChatMessage, AIHeroClient*, char**>::GetInstance()->Add(nullptr);
			EventHandler<41, OnChatMessage, AIHeroClient*, char**>::GetInstance()->Remove(nullptr);
			EventHandler<42, OnChatClientSideMessage, char**>::GetInstance()->Add(nullptr);
			EventHandler<42, OnChatClientSideMessage, char**>::GetInstance()->Remove(nullptr);
			EventHandler<43, OnChatSendWhisper, char**, char**>::GetInstance()->Add(nullptr);
			EventHandler<43, OnChatSendWhisper, char**, char**>::GetInstance()->Remove(nullptr);
			//Hud
			EventHandler<49, OnHudTargetChange, GameObject*>::GetInstance()->Add(nullptr);
			EventHandler<49, OnHudTargetChange, GameObject*>::GetInstance()->Remove(nullptr);
			//AudioManager
			EventHandler<50, OnAudioManagerPlaySound, std::string>::GetInstance()->Add(nullptr);
			EventHandler<50, OnAudioManagerPlaySound, std::string>::GetInstance()->Remove(nullptr);
			//Shop
			EventHandler<52, OnShopOpen>::GetInstance()->Add(nullptr);
			EventHandler<52, OnShopOpen>::GetInstance()->Remove(nullptr);
			EventHandler<53, OnCloseShop>::GetInstance()->Add(nullptr);
			EventHandler<53, OnCloseShop>::GetInstance()->Remove(nullptr);
			EventHandler<54, OnUndoPurchase>::GetInstance()->Add(nullptr);
			EventHandler<54, OnUndoPurchase>::GetInstance()->Remove(nullptr);
			//r3dCamera
			EventHandler<55, OnCameraSnap>::GetInstance()->Add(nullptr);
			EventHandler<55, OnCameraSnap>::GetInstance()->Remove(nullptr);
			EventHandler<56, OnCameraToggleLock>::GetInstance()->Add(nullptr);
			EventHandler<56, OnCameraToggleLock>::GetInstance()->Remove(nullptr);
			EventHandler<57, OnCameraUpdate, float, float>::GetInstance()->Add(nullptr);
			EventHandler<57, OnCameraUpdate, float, float>::GetInstance()->Remove(nullptr);
			EventHandler<58, OnCameraZoom>::GetInstance()->Add(nullptr);
			EventHandler<58, OnCameraZoom>::GetInstance()->Remove(nullptr);
			//ObjectManager
			EventHandler<60, OnObjectStackLoad, GameObject*, char**, int*, int*, Vector3f**>::GetInstance()->Add(nullptr);
			EventHandler<60, OnObjectStackLoad, GameObject*, char**, int*, int*, Vector3f**>::GetInstance()->Remove(nullptr);

#pragma endregion 
		}

		HMODULE Core::get_hModule() const
		{
			return this->hModule;
		}

		void Core::set_hModule(HMODULE h)
		{
			this->hModule = h;
		}

		Detour* Core::get_DetourInstance()
		{
			static auto instance = new Detour();
			return instance;
		}

		void BootstrapAddons()
		{
			if (!Bootstrapper::GetInstance()->Initialize())
			{
				Console::PrintLn("[!] Failed to launch .NET addons in a safe environment.");
			}
			else
			{
				Sleep(150u);

				if (ClientFacade::GetInstance()->GetGameState() == static_cast<int>(GameMode::Running))
					EventHandler<3, OnGameStart>::GetInstance()->Trigger();

				if (ClientFacade::GetInstance()->GetGameState() == static_cast<int>(GameMode::Connecting))
					EventHandler<26, OnGameLoad>::GetInstance()->Trigger();
			}
		}

		bool Core::ApplyHooks() const
		{
			VERIFY_HOOK(RiotX3D::ApplyHooks, "RiotX3D");
			VERIFY_HOOK(Game::GetInstance()->ApplyHooks, "Game");
			VERIFY_HOOK(Actor_Common::ApplyHooks, "Actor_Common");
			VERIFY_HOOK(HeroInventory::ApplyHooks, "HeroInventory");
			VERIFY_HOOK(AttackableUnit::ApplyHooks, "AttackableUnit");
			VERIFY_HOOK(pwConsole::ApplyHooks, "pwConsole");
			VERIFY_HOOK(Obj_AI_Base::ApplyHooks, "Obj_AI_Base");
			VERIFY_HOOK(AIHeroClient::ApplyHooks, "AIHeroClient");
			VERIFY_HOOK(Spellbook::ApplyHooks, "Spellbook");
			VERIFY_HOOK(MenuGUI::ApplyHooks, "MenuGUI");
			VERIFY_HOOK(ObjectManager::ApplyHooks, "ObjectManager");
			VERIFY_HOOK(BuffHost::ApplyHooks, "BuffHost");
			VERIFY_HOOK(HudGameObjectChat::ApplyHooks, "HudGameObjectChat");
			VERIFY_HOOK(pwHud::ApplyHooks, "pwHud");
			VERIFY_HOOK(AudioManager::ApplyHooks, "AudioManager");
			VERIFY_HOOK(NetClient::ApplyHooks, "NetClient");
			VERIFY_HOOK(ClientNode::ApplyHooks, "ClientNode");
			VERIFY_HOOK(HudVote::ApplyHooks, "HudVote");
			VERIFY_HOOK(MissionInfo::ApplyHooks, "MissionInfo");
			VERIFY_HOOK(r3dRenderLayer::ApplyHooks, "r3dRenderLayer");

			//VERIFY_HOOK( CharacterDataStack::ApplyHooks, "CharacterDataStack" );
			//VERIFY_HOOK( r3dCamera::ApplyHooks, "r3dCamera" );
			//VERIFY_HOOK( PacketLayer::ApplyHooks, "PacketLayer");
			//VERIFY_HOOK( CRepl32Info::ApplyHooks, "CRepl32Info");
			//VERIFY_HOOK( RiotRadsIO::ApplyHooks, "RiotRadsIO" );

			return true;
		}
	}
}

