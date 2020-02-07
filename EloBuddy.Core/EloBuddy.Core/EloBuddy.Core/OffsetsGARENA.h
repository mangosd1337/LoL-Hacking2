class Offsets
{
public:
	#include "ObjectsGARENA.h"

	enum class ClientFacade
	{
		NetApiClient = 0x01676cd8,// Possible mismatch
		NetApiNode = 0x03309834,// Possible mismatch
		GameState = 0x016736a0,
		ProcessWorldEvent = 0x00f37050,
	};


	enum class Game
	{
		Hud_OnDisconnect = 0x00bbf200,// Possible mismatch
		Hud_OnAfk = 0x00b763f0,
		ClientMainLoop = 0x00b72f00,// Possible mismatch
		DispatchEvent = 0x005FDF60,// Possible mismatch

		BuildDate = 0x0159D6E4,// Possible mismatch
		BuildTime = BuildDate + 0xC,
		BuildVersion = BuildTime + 0xC,
		BuildType = BuildVersion + 0xC,

		MovementCheck = 0x00000000,// Possible mismatch
		IsWindowFocused = 0x00000000,// Possible mismatch

		OnWndProc = 0x00000000,// Possible mismatch
		CRepl32InfoUpdatePacket = 0x00000000,// Possible mismatch

		PingNOP1 = 0x00AB2DD5,// Possible mismatch
		PingNOP2 = 0x00AB2ABA,// Possible mismatch
	};

	enum class MissionInfo
	{
		MissionInfoInst = 0x167B684,// Possible mismatch
		DrawTurretRange = 0x00c15af0,// Possible mismatch
	};

	enum class RiotClock
	{
		RiotClockInst = 0x03309dfc,// Possible mismatch
	};

	/*
	* Object related
	*/

	enum class ObjectManager
	{
		LocalPlayer = 0x01675240,// Possible mismatch

		ObjectList = 0x032f6188,
		MaxSize = ObjectList + 0x4,
		UsedIndexes = ObjectList + 0x8,
		HighestObjectId = ObjectList + 0xC,
		HighestPlayerObjectId = ObjectList + 0x10,

		CreateObject = 0x00597340,// Possible mismatch
		AssignNetworkId = 0x009fbae0,
		DestroyObject = 0x008290f0,
		ApplySkin = 0x00606630,
	};


	enum class Actor_Common
	{
		SetPath = 0x00c69a80,
		CreatePath = 0x00f31f70,// Possible mismatch
		NavMesh_CreatePath = 0x0111de22,
		SmoothPath = 0x0087e380,
	};


	enum class NavMesh
	{
		NavMeshController = 0x01a5b11c,// Possible mismatch
		GetHeightForPosition = 0x006d9d9a,
		IsWallOfGrass = 0x006448b5,// Possible mismatch
	};


	enum class GameObjectFunctions
	{
		OnDamage = 0x008832a0,// Possible mismatch
		GetBaseDrawPosition = 0x008101b0,// Possible mismatch
		FOWRecall = 0x00E4D530,
		OnLevelUp = 0x00588fa0,
		SetSkin = 0x00834a80,// Possible mismatch
		IssueOrder = 0x00692d80,
		PlayAnimation = 0x00919e90,
		OnDoCast = 0x00751810,// Possible mismatch
		CaptureTurret = 0x00d11690,
	};


	enum class SpellHelper
	{
		ComputeCharacterAttackCastDelay = 0x009923f0,
		ComputeCharacterAttackDelay = 0x008d8c90,// Possible mismatch
		GetBasicAttack = 0x0058be40,
	};



	/*
	* BuffManager
	*/

	enum class BuffManager
	{
		BuffManagerInst = 0x2B78,
		BaseScriptBuff = 0x007e3cb0, //ALE-CA89343D
	};


	/*
	* Spellbook
	*/

	enum class Spellbook
	{
		//Functions
		SpellbookInst = 0x2438,
		Client_DoCastSpell = 0x00b4f6f0,
		Client_LevelSpell = 0x00606a30,
		Client_GetSpellstate = 0x0109a400,
		Client_UpdateChargeableSpell = 0x0092fa40,// Possible mismatch
		Client_ForceStop = 0x00b0b150,
		Client_SpellSlotCanBeUpgraded = 0x00c2b830,// Possible mismatch

		ProcessCastSpell = 0x0109aa20,// Possible mismatch
		OnCommonAutoAttack = 0x0109ac20,// Possible mismatch
		OnApplyCD = 0x006e9330,
	};

	/*
	* HeroInventory
	*/

	enum class HeroInventory
	{
		Inventory = 0x11C8,
		BuyItem = 0x00e9c850,
		SellItem = 0x00e03360,// Possible mismatch
		SwapItem = 0x006fa120,// Possible mismatch
		InventoryPointer = 0x00000198,// Possible mismatch
	};


	/*
	* PWHUD
	*/
	enum class pwHud
	{
		pwHud_Instance = 0x01673650,
		pwHud_OnDraw = 0x00747a90,
		SetUISelectedObjId = 0xffffffff,

		NOP_pwHud_DisableDrawing = 0x00805998,// Possible mismatch
		NOP_pwHud_DisableHPBarDrawing = 0x00747bdd,// Possible mismatch
		NOP_MenuGUI_DisableDrawing = 0x010877e1,// Possible mismatch
		NOP_MenuGUI_DisableMiniMap = 0x01087780,// Possible mismatch
	};

	enum class MenuGUI
	{
		MenuGUI_Instance = 0x032f6f08,
		DoEmote = 0x882FD0,
		PingMiniMap = 0x00cf61e0,// Possible mismatch
		CallCurrentPing = 0x00709480,// Possible mismatch
		DoMasteryBadge = 0x00f8c740,// Possible mismatch
	};


	enum class pwConsole
	{
		ShowClientSideMessage = 0x00716150,// Possible mismatch
		OnInput = 0x00c81250,
		ProcessCommand = 0x005e6a00,
		pwClose = 0x00ba5ca0,// Possible mismatch
		pwOpen = 0x00e359e0,
	};


	enum class HudManager
	{
		HudManagerInst = 0x01676310,
	};



	/*
	* Hero Stats
	*/

	enum class HeroStats
	{
		HeroStatsInst = 0x36C0,// Possible mismatch
		LoadHeroStat = 0x00bfa9e0,
		GetHeroStats = 0x00bfa9e0,
	};

	enum class r3dRenderer
	{
		FPS = 0x3309C30,
		r3dRendererInstance = 0x01675674,// Possible mismatch
		DrawCircularRangeIndicator = 0x0086a630,// Possible mismatch
		r3dScreenTo3D = 0x005d4b00,// Possible mismatch
		r3dProjectToScreen = 0x00c02ce0,
	};



	enum class TacticalMap
	{
		ToWorldCoord = 0x008d6d90,
		ToMapCoord = 0x0096a270,
		/*
		ToWorldCoord = 0xF437F0,// Possible mismatch f3 0f 11 47 08 8b 08 8b 01 8b 00
		ToMapCoord = 0x005f2430,*/
	};

	enum class RiotAsset
	{
		AssetExists = 0x007b5ae0,
	};


	enum class BuffHost
	{
		BuffAddRemove = 0x00c1b5f0,// Possible mismatch
	};


	enum class Experience
	{
		XPToNextLevel = 0x0064f450,// Possible mismatch
		XPToCurrentLevel = 0x8A2F60,
	};


	enum class HudGameChatObject
	{
		DisplayChat = 0x007c25f0,
		SetScale = 0x00f9b140,// Possible mismatch
	};


	enum class RiotString
	{
		TranslateString = 0x00e62070,
	};


	enum class AudioManager
	{
		PlaySound = 0x00cd82b0,
	};


	enum class FoundryItemShop
	{
		OnUndo = 0x00000000,// Possible mismatch
		ToggleShop = 0x00c907d0,// Possible mismatch
		ActionCheckVT1 = 0x00eb9080,// Possible mismatch
		ActionCheckVT2 = 0x00627aa0,// Possible mismatch

		IsOpen = 0x30
	};

	enum class r3dRenderLayer
	{
		LoadTexture = 0x00b3dd30,// Possible mismatch
		IRedTexturePacket1 = 0x1674C24,// Possible mismatch
	};

	enum class RiotX3D
	{
		Reset = 0x0078ae00,// Possible mismatch
		BeginScene = 0x0065f120,// Possible mismatch
		Present = 0x007f2050,
		EndScene = 0x005dd0d0,// Possible mismatch
		SetRenderTarget = 0x00f4ade0,
	};


	enum class TeemoClient
	{
		ComputeChecksum = 0x00e36cb0,
		B_ModulesVerified = 0x03309b40,// Possible mismatch
	};

	enum class HudVote
	{
		OnSurrenderVote = 0x008A7B20
	};
};