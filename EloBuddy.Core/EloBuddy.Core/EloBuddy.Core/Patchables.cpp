#include "stdafx.h"
#include "Patchables.h"
#include "Console.h"
#include "Offsets.h"

namespace EloBuddy
{
	namespace Native
	{
		bool Patchables::Initialize()
		{
			VMProtectBeginUltra("staticoffsets");

			// ObjectManager 
			g_localPlayer = M_RVA(Offsets::ObjectManager::LocalPlayer);
			g_objectManagerMaxSize = M_RVA(Offsets::ObjectManager::MaxSize);
			g_objectManagerUsedIndexes = M_RVA(Offsets::ObjectManager::UsedIndexes);
			g_objectManagerMaxIndexes = M_RVA(Offsets::ObjectManager::MaxSize);
			g_objectManagerUnitArray = M_RVA(Offsets::ObjectManager::ObjectList);

			// Obj_AI_Base
			g_objectAIBaseIssueOrder = M_RVA(Offsets::GameObjectFunctions::IssueOrder);
			g_objectAIBaseBaseDrawPosition = M_RVA(Offsets::GameObjectFunctions::GetBaseDrawPosition);
			g_objectAIBaseComputeCharacterAttackCastDelay = M_RVA(Offsets::SpellHelper::ComputeCharacterAttackCastDelay);
			g_objectAIBaseComputeCharacterAttackDelay = M_RVA(Offsets::SpellHelper::ComputeCharacterAttackDelay);
			g_objectAIBaseGetBasicAttack = M_RVA(Offsets::SpellHelper::GetBasicAttack);
			g_objectAIBaseSetSkin = M_RVA(Offsets::GameObjectFunctions::SetSkin);

			// Obj_AI_Hero
			g_objectAIHeroDoEmote = M_RVA(Offsets::MenuGUI::DoEmote);

			// FoundryItemShop
			g_foundryItemShopCanShop1 = M_RVA(Offsets::FoundryItemShop::ActionCheckVT1);
			g_foundryItemShopCanShop2 = M_RVA(Offsets::FoundryItemShop::ActionCheckVT2);

			// BaseScriptBuff
			g_GetBaseScriptBuff = M_RVA(Offsets::BuffManager::BaseScriptBuff);

			// BuildInfo
			g_BuildInfoDate = M_RVA(Offsets::Game::BuildDate);
			g_BuildInfoTime = M_RVA(Offsets::Game::BuildTime);
			g_BuildInfoVersion = M_RVA(Offsets::Game::BuildVersion);
			g_BuildInfoType = M_RVA(Offsets::Game::BuildType);

			// Actor Common
			g_ActorCommonCreatePath = M_RVA(Offsets::Actor_Common::CreatePath);
			g_ActorCommonCreatePathInner = M_RVA(Offsets::Actor_Common::NavMesh_CreatePath);
			g_ActorCommonSmoothPath = M_RVA(Offsets::Actor_Common::SmoothPath);

			// NetApiClient
			g_netAPIClient = M_RVA(Offsets::ClientFacade::NetApiClient);

			// HudManager
			g_HudManagerInst = M_RVA(Offsets::HudManager::HudManagerInst);

			// MenuGUI
			g_MenuGUIInst = M_RVA(Offsets::MenuGUI::MenuGUI_Instance);
			g_CallCurrentPing = M_RVA(Offsets::MenuGUI::CallCurrentPing);

			// MissionInfo
			g_MissionInfoInst = M_RVA(Offsets::MissionInfo::MissionInfoInst);

			// NavMesh
			g_NavMeshController = M_RVA(Offsets::NavMesh::NavMeshController);
			g_NavMeshIsWallOfGrass = M_RVA(Offsets::NavMesh::IsWallOfGrass);
			g_NavMeshGetHeightForPosition = M_RVA(Offsets::NavMesh::GetHeightForPosition);

			// pwConsole
			g_pwConsoleShowClientSideMessage = M_RVA(Offsets::pwConsole::ShowClientSideMessage);
			g_pwConsoleProcessMessage = M_RVA(Offsets::pwConsole::ProcessCommand);

			// pwHud
			g_pwHudInstance = M_RVA(Offsets::pwHud::pwHud_Instance);

			// r3dRenderer
			g_r3dRendererInstance = M_RVA(Offsets::r3dRenderer::r3dRendererInstance);
			g_r3dRendererDrawCircularRangeIndicator = M_RVA(Offsets::r3dRenderer::DrawCircularRangeIndicator);
			g_r3dRendererScreenTo3d = M_RVA(Offsets::r3dRenderer::r3dScreenTo3D);
			g_r3dRendererProjectToScreen = M_RVA(Offsets::r3dRenderer::r3dProjectToScreen);

			// RiotClock
			g_RiotClockInst = M_RVA(Offsets::RiotClock::RiotClockInst);

			// RiotString
			g_RiotStringTranslateString = M_RVA(Offsets::RiotString::TranslateString);

			// Spellbook
			g_SpellbookLevelSpell = M_RVA(Offsets::Spellbook::Client_LevelSpell);
			g_SpellbookGetSpellState = M_RVA(Offsets::Spellbook::Client_GetSpellstate);

			// TacticalMap
			g_TacticalMapToWorldCoord = M_RVA(Offsets::TacticalMap::ToWorldCoord);
			g_TacticalMapToMapCoord = M_RVA(Offsets::TacticalMap::ToMapCoord);

			// ClientFacade
			g_clientFacadeGameState = M_RVA(Offsets::ClientFacade::GameState);
			g_clientFacadeNode = M_RVA(Offsets::ClientFacade::NetApiNode);
			
			VMProtectEnd();

			return true;
		}

#pragma region Linker
		// ObjectManager 
		unsigned long Patchables::g_localPlayer;
		unsigned long Patchables::g_objectManagerMaxSize;
		unsigned long Patchables::g_objectManagerUsedIndexes;
		unsigned long Patchables::g_objectManagerMaxIndexes;
		unsigned long Patchables::g_objectManagerUnitArray;

		// Obj_AI_Base
		unsigned long Patchables::g_objectAIBaseIssueOrder;
		unsigned long Patchables::g_objectAIBaseBaseDrawPosition;
		unsigned long Patchables::g_objectAIBaseComputeCharacterAttackCastDelay;
		unsigned long Patchables::g_objectAIBaseComputeCharacterAttackDelay;
		unsigned long Patchables::g_objectAIBaseGetBasicAttack;
		unsigned long Patchables::g_objectAIBaseSetSkin;

		// Obj_AI_Hero
		unsigned long Patchables::g_objectAIHeroDoEmote;

		// FoundryItemShop
		unsigned long Patchables::g_foundryItemShopCanShop1;
		unsigned long Patchables::g_foundryItemShopCanShop2;

		// BaseScriptBuff
		unsigned long Patchables::g_GetBaseScriptBuff;

		// BuildInfo
		unsigned long Patchables::g_BuildInfoDate;
		unsigned long Patchables::g_BuildInfoTime;
		unsigned long Patchables::g_BuildInfoVersion;
		unsigned long Patchables::g_BuildInfoType;

		// Actor Common
		unsigned long Patchables::g_ActorCommonCreatePath;
		unsigned long Patchables::g_ActorCommonCreatePathInner;
		unsigned long Patchables::g_ActorCommonSmoothPath;

		// NetApiClient
		unsigned long Patchables::g_netAPIClient;

		// HudManager
		unsigned long Patchables::g_HudManagerInst;

		// MenuGUI
		unsigned long Patchables::g_MenuGUIInst;
		unsigned long Patchables::g_CallCurrentPing;

		// MissionInfo
		unsigned long Patchables::g_MissionInfoInst;

		// NavMesh
		unsigned long Patchables::g_NavMeshController;
		unsigned long Patchables::g_NavMeshIsWallOfGrass;
		unsigned long Patchables::g_NavMeshGetHeightForPosition;

		// pwConsole
		unsigned long Patchables::g_pwConsoleShowClientSideMessage;
		unsigned long Patchables::g_pwConsoleProcessMessage;

		// pwHud
		unsigned long Patchables::g_pwHudInstance;

		// r3dRenderer
		unsigned long Patchables::g_r3dRendererInstance;
		unsigned long Patchables::g_r3dRendererDrawCircularRangeIndicator;
		unsigned long Patchables::g_r3dRendererScreenTo3d;
		unsigned long Patchables::g_r3dRendererProjectToScreen;

		// RiotClock
		unsigned long Patchables::g_RiotClockInst;

		// RiotString
		unsigned long Patchables::g_RiotStringTranslateString;

		// Spellbook
		unsigned long Patchables::g_SpellbookLevelSpell;
		unsigned long Patchables::g_SpellbookGetSpellState;

		// TacticalMap
		unsigned long Patchables::g_TacticalMapToWorldCoord;
		unsigned long Patchables::g_TacticalMapToMapCoord;

		// ClientFacade
		unsigned long Patchables::g_clientFacadeGameState;
		unsigned long Patchables::g_clientFacadeNode;
#pragma endregion
	}
}