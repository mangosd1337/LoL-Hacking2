class Offsets
{
public:
	#include "ObjectsCN.h"

	enum class ClientFacade
	{
		NetApiClient = 0x01679ff8,// Possible mismatch
		NetApiNode = 0x03309844,// Possible mismatch
		GameState = 0x01679814,
		ProcessWorldEvent = 0x0076b950,
	};


	enum class Game
	{
		Hud_OnDisconnect = 0x00f5c3e0,// Possible mismatch
		Hud_OnAfk = 0x03595e53,// Possible mismatch
		ClientMainLoop = 0x00f20f70,// Possible mismatch
		DispatchEvent = 0x00c680bf,// Possible mismatch

		BuildDate = 0x0159D6D0,// Possible mismatch
		BuildTime = BuildDate,
		BuildVersion = BuildTime + 0xC,
		BuildType = BuildVersion + 0xC,

		MovementCheck = 0x00000000,// Possible mismatch
		IsWindowFocused = 0x00000000,// Possible mismatch

		OnWndProc = 0x00000000,// Possible mismatch
		CRepl32InfoUpdatePacket = 0x00000000,// Possible mismatch

		PingNOP1 = 0x00000000,// Possible mismatch
		PingNOP2 = 0x00000000,// Possible mismatch
	};

	enum class MissionInfo
	{
		MissionInfoInst = 0x016720AC,// Possible mismatch
		DrawTurretRange = 0x0063e3f0,// Possible mismatch
	};

	enum class RiotClock
	{
		RiotClockInst = 0x03309e0c,// Possible mismatch
	};

	/*
	* Object related
	*/

	enum class ObjectManager
	{
		LocalPlayer = 0x01672498,// Possible mismatch

		ObjectList = 0x32F6198,// Possible mismatch
		MaxSize = ObjectList + 0x4,
		UsedIndexes = ObjectList + 0x8,
		HighestObjectId = ObjectList + 0xC,
		HighestPlayerObjectId = ObjectList + 0x10,

		CreateObject = 0x00608690,// Possible mismatch
		AssignNetworkId = 0x0360aeef,// Possible mismatch
		DestroyObject = 0x3505EFC,// Possible mismatch
		ApplySkin = 0x00cd1e80,
	};


	enum class Actor_Common
	{
		SetPath = 0x00581990,
		CreatePath = 0x005d9ae0,// Possible mismatch
		NavMesh_CreatePath = 0x0111d592,
		SmoothPath = 0x00000000,// Possible mismatch
	};


	enum class NavMesh
	{
		NavMeshController = 0x01a5b12c,// Possible mismatch
		GetHeightForPosition = 0x00f2b12e,
		IsWallOfGrass = 0x00669b91,// Possible mismatch
	};


	enum class GameObjectFunctions
	{
		OnDamage = 0x0081d430,// Possible mismatch
		GetBaseDrawPosition = 0x00cf1d20,// Possible mismatch
		FOWRecall = 0xffffffff,
		OnLevelUp = 0x00ea7780,
		SetSkin = 0x005f4b80,// Possible mismatch
		IssueOrder = 0x0358ca41,// Possible mismatch
		PlayAnimation = 0x00c2e940,
		OnDoCast = 0x00955050,// Possible mismatch
		CaptureTurret = 0x00bab160,
	};


	enum class SpellHelper
	{
		ComputeCharacterAttackCastDelay = 0x00dc3030,
		ComputeCharacterAttackDelay = 0x00b1f1b0,// Possible mismatch
		GetBasicAttack = 0x0085e610,
	};



	/*
	* BuffManager
	*/

	enum class BuffManager
	{
		BuffManagerInst = 0x2B78,
		BaseScriptBuff = 0x03513c94, //ALE-CA89343D
	};


	/*
	* Spellbook
	*/

	enum class Spellbook
	{
		//Functions
		SpellbookInst = 0x2438,
		Client_DoCastSpell = 0x006c1470,
		Client_LevelSpell = 0x00e2df70,
		Client_GetSpellstate = 0x01099b30,
		Client_UpdateChargeableSpell = 0x3599019,
		Client_ForceStop = 0x007e3de0,// Possible mismatch
		Client_SpellSlotCanBeUpgraded = 0x00a9d950,// Possible mismatch

		ProcessCastSpell = 0x0109a150,// Possible mismatch
		OnCommonAutoAttack = 0x0109a350,// Possible mismatch
		OnApplyCD = 0x0105f350,// Possible mismatch
	};

	/*
	* HeroInventory
	*/

	enum class HeroInventory
	{
		Inventory = 0x11C8,
		BuyItem = 0x009edfa0,
		SellItem = 0x00b29100,// Possible mismatch
		SwapItem = 0x00a59de0,// Possible mismatch
		InventoryPointer = 0x00000198,// Possible mismatch
	};


	/*
	* PWHUD
	*/
	enum class pwHud
	{
		pwHud_Instance = 0x016791f4,
		pwHud_OnDraw = 0x0063f320,
		SetUISelectedObjId = 0xffffffff,

		NOP_pwHud_DisableDrawing = 0x00e1e018,// Possible mismatch
		NOP_pwHud_DisableHPBarDrawing = 0x0063f46d,// Possible mismatch
		NOP_MenuGUI_DisableDrawing = 0x01086f21,// Possible mismatch
		NOP_MenuGUI_DisableMiniMap = 0x01086ec0,// Possible mismatch
	};


	enum class MenuGUI
	{
		MenuGUI_Instance = 0x032f6f18,
		DoEmote = 0xF1F3A0,
		PingMiniMap = 0x00fb2bb0,// Possible mismatch
		CallCurrentPing = 0x00e5c100,// Possible mismatch
		DoMasteryBadge = 0x00de8ba0,// Possible mismatch
	};


	enum class pwConsole
	{
		ShowClientSideMessage = 0x0097ad50,// Possible mismatch
		OnInput = 0x00dda380,
		ProcessCommand = 0x00a016c0,
		pwClose = 0x00bf6b10,// Possible mismatch
		pwOpen = 0x005802e0,
	};


	enum class HudManager
	{
		HudManagerInst = 0x016721c8,
	};



	/*
	* Hero Stats
	*/

	enum class HeroStats
	{
		HeroStatsInst = 0x36C0,// Possible mismatch
		LoadHeroStat = 0xA8D970,
		GetHeroStats = 0x008dd9a0,
	};

	enum class r3dRenderer
	{
		FPS = 0x3309C40,
		r3dRendererInstance = 0x01676d4c,// Possible mismatch
		DrawCircularRangeIndicator = 0x00570ad0,// Possible mismatch
		r3dScreenTo3D = 0x00d2ea60,// Possible mismatch
		r3dProjectToScreen = 0x00d18270,
	};



	enum class TacticalMap
	{
		ToWorldCoord = 0x00c86d80,// Possible mismatch
		ToMapCoord = 0x00f722e0,
		/*
		ToWorldCoord = 0xF437F0,// Possible mismatch f3 0f 11 47 08 8b 08 8b 01 8b 00
		ToMapCoord = 0x005f2430,*/
	};



	enum class RiotAsset
	{
		AssetExists = 0x00b31590,
	};


	enum class BuffHost
	{
		BuffAddRemove = 0x00625740,
	};


	enum class Experience
	{
		XPToNextLevel = 0x00e27510,// Possible mismatch
		XPToCurrentLevel = 0x0094F4D0,// Possible mismatch
	};


	enum class HudGameChatObject
	{
		DisplayChat = 0x00eb6270,
		SetScale = 0x006a9cf0,// Possible mismatch
	};


	enum class RiotString
	{
		TranslateString = 0x0065c860,
	};


	enum class AudioManager
	{
		PlaySound = 0x009714d0,
	};


	enum class FoundryItemShop
	{
		OnUndo = 0x00000000,// Possible mismatch
		ToggleShop = 0x0080e5f0,// Possible mismatch
		ActionCheckVT1 = 0x00b0cc60,// Possible mismatch
		ActionCheckVT2 = 0x009dd2c0,// Possible mismatch

		IsOpen = 0x30
	};

	enum class r3dRenderLayer
	{
		LoadTexture = 0x00667c00,// Possible mismatch
		IRedTexturePacket1 = 0x1676F1C,// Possible mismatch
	};

	enum class RiotX3D
	{
		Reset = 0x006D16D0,// Possible mismatch
		BeginScene = 0x00a5c500,// Possible mismatch
		Present = 0x00691660,// Possible mismatch
		EndScene = 0x00993d10,// Possible mismatch
		SetRenderTarget = 0x008f34b0,
	};


	enum class TeemoClient
	{
		ComputeChecksum = 0x00F9F3B0,
		B_ModulesVerified = 0x03309b50,// Possible mismatch
	};

	enum class HudVote
	{
		OnSurrenderVote = 0x0090ADA0
	};
};