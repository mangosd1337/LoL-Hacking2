class Offsets
{
public:
	#include "ObjectsEU.h"

	enum class ClientFacade
	{
		NetApiClient = 0x0156c420,// Possible mismatch
		NetApiNode = 0x0320584c,
		GameState = 0x0156b778,
		ProcessWorldEvent = 0x00eec0a0,
	};


	enum class Game
	{
		Hud_OnDisconnect = 0x00a64840,
		Hud_OnAfk = 0x00b4aec0,
		ClientMainLoop = 0x005fb850,// Possible mismatch
		DispatchEvent = 0x00E2B150,// Possible mismatch

		BuildDate = 0x0149D8A0,// Possible mismatch
		BuildTime = BuildDate,
		BuildVersion = BuildTime + 0xC,
		BuildType = BuildVersion + 0xC,

		MovementCheck = 0x00000000,// Possible mismatch
		IsWindowFocused = 0x00000000,// Possible mismatch

		OnWndProc = 0x00000000,// Possible mismatch
		CRepl32InfoUpdatePacket = 0x00000000,// Possible mismatch

		PingNOP1 = 0x006016B7,// Possible mismatch
		PingNOP2 = 0x0060139B,// Possible mismatch
	};

	enum class MissionInfo
	{
		MissionInfoInst = 0x1574F48,// Possible mismatch
		DrawTurretRange = 0x0063e3f0,// Possible mismatch
	};

	enum class RiotClock
	{
		RiotClockInst = 0x3205E0C,// Possible mismatch
	};

	/*
	* Object related
	*/

	enum class ObjectManager
	{
		LocalPlayer = 0x015752ac,// Possible mismatch

		ObjectList = 0x031f0828,
		MaxSize = ObjectList + 0x4,
		UsedIndexes = ObjectList + 0x8,
		HighestObjectId = ObjectList + 0xC,
		HighestPlayerObjectId = ObjectList + 0x10,

		CreateObject = 0x00b38f00,// Possible mismatch
		AssignNetworkId = 0x00e109c0,
		DestroyObject = 0x00e15980,
		ApplySkin = 0x008b9cc0,
	};


	enum class Actor_Common
	{
		SetPath = 0x00e1ff60,// Possible mismatch
		CreatePath = 0x0088f050,// Possible mismatch
		NavMesh_CreatePath = 0x0104af70,
		SmoothPath = 0x00b35d50,
	};


	enum class NavMesh
	{
		NavMeshController = 0x01954e78,
		GetHeightForPosition = 0x00b7b9aa,
		IsWallOfGrass = 0x0084836f,// Possible mismatch
	};


	enum class GameObjectFunctions
	{
		OnDamage = 0x00cbb1e0,
		GetBaseDrawPosition = 0x00e47c60,// Possible mismatch
		FOWRecall = 0x00A723C0,
		OnLevelUp = 0x006489e0,
		SetSkin = 0x00bd5b60,// Possible mismatch
		IssueOrder = 0x00d3b2a0,
		PlayAnimation = 0x00619190,
		OnDoCast = 0x00cb04c0,// Possible mismatch
		CaptureTurret = 0x00895c10,// Possible mismatch
	};


	enum class SpellHelper
	{
		ComputeCharacterAttackCastDelay = 0x0099b590,
		ComputeCharacterAttackDelay = 0x00596250,// Possible mismatch
		GetBasicAttack = 0x00d8f1a0,
	};



	/*
	* BuffManager
	*/

	enum class BuffManager
	{
		BuffManagerInst = 0x2B78,
		BaseScriptBuff = 0x0068afa0, //ALE-CA89343D
	};


	/*
	* Spellbook
	*/

	enum class Spellbook
	{
		//Functions
		SpellbookInst = 0x2438,
		Client_DoCastSpell = 0x00668770,
		Client_LevelSpell = 0x00940460,
		Client_GetSpellstate = 0x00fd7060,
		Client_UpdateChargeableSpell = 0x009dfc80,// Possible mismatch
		Client_ForceStop = 0x0066c510,
		Client_SpellSlotCanBeUpgraded = 0x008fbaa0,// Possible mismatch

		ProcessCastSpell = 0x00fd7650,
		OnCommonAutoAttack = 0x00fd7770,// Possible mismatch
		OnApplyCD = 0x00a60670,
	};

	/*
	* HeroInventory
	*/

	enum class HeroInventory
	{
		Inventory = 0x11C8,
		BuyItem = 0x00c0d6c0,
		SellItem = 0x009b1010,// Possible mismatch
		SwapItem = 0x0064e5e0,// Possible mismatch
		InventoryPointer = 0x00000198,// Possible mismatch
	};


	/*
	* PWHUD
	*/
	enum class pwHud
	{
		pwHud_Instance = 0x01572ee8,
		pwHud_OnDraw = 0x00cca2e0,
		SetUISelectedObjId = 0xffffffff,

		NOP_pwHud_DisableDrawing = 0x00b15b0f,// Possible mismatch
		NOP_pwHud_DisableHPBarDrawing = 0x00cca42d,// Possible mismatch
		NOP_MenuGUI_DisableDrawing = 0x00fc6248,// Possible mismatch
		NOP_MenuGUI_DisableMiniMap = 0x00fc61e7,// Possible mismatch
	};


	enum class MenuGUI
	{
		MenuGUI_Instance = 0x031f16f0,
		DoEmote = 0x00cdde30,// Possible mismatch
		PingMiniMap = 0x00ef5340,// Possible mismatch
		CallCurrentPing = 0x00efbc60,// Possible mismatch
		DoMasteryBadge = 0x00c88d80,// Possible mismatch
	};


	enum class pwConsole
	{
		ShowClientSideMessage = 0x0068e980,// Possible mismatch
		OnInput = 0x009034f0,// Possible mismatch
		ProcessCommand = 0x00a70d30,
		pwClose = 0x00b09da0,// Possible mismatch
		pwOpen = 0x0092c500,
	};


	enum class HudManager
	{
		HudManagerInst = 0x01570c6c,
	};



	/*
	* Hero Stats
	*/

	enum class HeroStats
	{
		HeroStatsInst = 0x36C0,// Possible mismatch
		LoadHeroStat = 0x6428D0,
		GetHeroStats = 0x007165d0,
	};

	enum class r3dRenderer
	{
		FPS = 0x3205C4C,
		r3dRendererInstance = 0x01571a3c,// Possible mismatch
		DrawCircularRangeIndicator = 0x007adc40,// Possible mismatch
		r3dScreenTo3D = 0x009b0830,// Possible mismatch
		r3dProjectToScreen = 0x008ff560,
	};



	enum class TacticalMap
	{
		ToWorldCoord = 0x00f127a0,// Possible mismatch
		ToMapCoord = 0x00610b90,
		/*
		ToWorldCoord = 0xF437F0,// Possible mismatch f3 0f 11 47 08 8b 08 8b 01 8b 00
		ToMapCoord = 0x005f2430,*/
	};



	enum class RiotAsset
	{
		AssetExists = 0x00ba12d0,
	};


	enum class BuffHost
	{
		BuffAddRemove = 0x00bd1260,
	};


	enum class Experience
	{
		XPToNextLevel = 0x009c2f30,// Possible mismatch
		XPToCurrentLevel = 0x005ba150,// Possible mismatch
	};


	enum class HudGameChatObject
	{
		DisplayChat = 0x00cd5f60,
		SetScale = 0x00cba240,// Possible mismatch
	};


	enum class RiotString
	{
		TranslateString = 0x00ba93f0,
	};


	enum class AudioManager
	{
		PlaySound = 0x00a596b0,
	};


	enum class FoundryItemShop
	{
		OnUndo = 0x00000000,// Possible mismatch
		ToggleShop = 0x005e5840,// Possible mismatch
		ActionCheckVT1 = 0x00bda650,// Possible mismatch
		ActionCheckVT2 = 0x00b84de0,// Possible mismatch

		IsOpen = 0x30
	};

	enum class r3dRenderLayer
	{
		LoadTexture = 0x00eaa0d0,// Possible mismatch
		IRedTexturePacket1 = 0x15722D8,// Possible mismatch
	};

	enum class RiotX3D
	{
		Reset = 0x00b48c70,// Possible mismatch
		BeginScene = 0x00f01590,// Possible mismatch
		Present = 0x0071dfb0,
		EndScene = 0x007ee220,// Possible mismatch
		SetRenderTarget = 0x00674a90,
	};


	enum class TeemoClient
	{
		ComputeChecksum = 0x00b82170,
		B_ModulesVerified = 0x03205b58,// Possible mismatch
	};

	enum class HudVote
	{
		OnSurrenderVote = 0x0090ADA0
	};
};