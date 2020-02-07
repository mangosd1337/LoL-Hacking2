#pragma once
#include <Windows.h>

#define g_Offset static unsigned long
#define M_RVA(ADDR) ((__int32)GetModuleHandle(NULL) + static_cast<__int32>(ADDR) - 0x400000)

namespace EloBuddy
{
	namespace Native
	{
		class Patchables
		{
		public:
			static bool Initialize();

			// Globals
			g_Offset g_localPlayer;
			g_Offset g_objectManagerMaxSize;
			g_Offset g_objectManagerUsedIndexes;
			g_Offset g_objectManagerMaxIndexes;
			g_Offset g_objectManagerUnitArray;

			// Obj_AI_Base
			g_Offset g_objectAIBaseIssueOrder;
			g_Offset g_objectAIBaseBaseDrawPosition;
			g_Offset g_objectAIBaseComputeCharacterAttackCastDelay;
			g_Offset g_objectAIBaseComputeCharacterAttackDelay;
			g_Offset g_objectAIBaseGetBasicAttack;
			g_Offset g_objectAIBaseSetSkin;

			// Obj_AI_Hero
			g_Offset g_objectAIHeroDoEmote;

			// FoundryItemShop
			g_Offset g_foundryItemShopCanShop1;
			g_Offset g_foundryItemShopCanShop2;

			// BuffScriptInstance
			g_Offset g_GetBaseScriptBuff;

			// BuildInfo
			g_Offset g_BuildInfoDate;
			g_Offset g_BuildInfoTime;
			g_Offset g_BuildInfoVersion;
			g_Offset g_BuildInfoType;

			// Actor_Common
			g_Offset g_ActorCommonCreatePath;
			g_Offset g_ActorCommonCreatePathInner;
			g_Offset g_ActorCommonSmoothPath;

			// HudManager
			g_Offset g_HudManagerInst;

			// MenuGUI
			g_Offset g_MenuGUIInst;
			g_Offset g_CallCurrentPing;

			// MissionInfo
			g_Offset g_MissionInfoInst;

			// NavMesh
			g_Offset g_NavMeshController;
			g_Offset g_NavMeshIsWallOfGrass;
			g_Offset g_NavMeshGetHeightForPosition;

			// pwConsole
			g_Offset g_pwConsoleShowClientSideMessage;
			g_Offset g_pwConsoleProcessMessage;

			// r3dRenderer
			g_Offset g_r3dRendererInstance;
			g_Offset g_r3dRendererDrawCircularRangeIndicator;
			g_Offset g_r3dRendererScreenTo3d;
			g_Offset g_r3dRendererProjectToScreen;

			// RiotClock
			g_Offset g_RiotClockInst;

			// RiotString
			g_Offset g_RiotStringTranslateString;

			// Spellbook
			g_Offset g_SpellbookLevelSpell;
			g_Offset g_SpellbookGetSpellState;

			// pwHud
			g_Offset g_pwHudInstance;

			// TacticalMap
			g_Offset g_TacticalMapToWorldCoord;
			g_Offset g_TacticalMapToMapCoord;

			// NetApiClient
			g_Offset g_netAPIClient;

			// ClientFacade
			g_Offset g_clientFacadeGameState;
			g_Offset g_clientFacadeNode;

		};
	}
}