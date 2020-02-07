class Offsets
{
public:
	#include "ObjectsPBE.h"

	enum class ClientFacade
	{
		NetApiClient = 0x0155a1c0,// Possible mismatch
		NetApiNode = 0x031eed30,// Possible mismatch
		GameState = 0x01557974,
		ProcessWorldEvent = 0x00b672a0,
	};


	enum class Game
	{
		Hud_OnDisconnect = 0x00a7e300,
		Hud_OnAfk = 0x00d2f100,
		ClientMainLoop = 0x00c59130,// Possible mismatch
		DispatchEvent = 0x00815E80,// Possible mismatch

		BuildDate = 0x01497574,// Possible mismatch
		BuildTime = BuildDate + 0xC,
		BuildVersion = BuildTime + 0xC,
		BuildType = BuildVersion + 0xC,

		MovementCheck = 0x00000000,// Possible mismatch
		IsWindowFocused = 0x00000000,// Possible mismatch

		OnWndProc = 0x01048495,// Possible mismatch
		CRepl32InfoUpdatePacket = 0x00000000,// Possible mismatch

		PingNOP1 = 0x00AC65A1,// Possible mismatch
		PingNOP2 = 0x00AC62DB,// Possible mismatch
	};

	enum class MissionInfo
	{
		MissionInfoInst = 0x0155C04C,// Possible mismatch
		DrawTurretRange = 0x009be7d0,// Possible mismatch
		PatchDrawTurretRange = 0x009BE871,
	};

	enum class RiotClock
	{
		RiotClockInst = 0x31EF1D0,// Possible mismatch
	};

	/*
	* Object related
	*/

	enum class ObjectManager
	{
		LocalPlayer = 0x015584dc,// Possible mismatch

		ObjectList = 0x031da680,
		MaxSize = ObjectList + 0x4,
		UsedIndexes = ObjectList + 0x8,
		HighestObjectId = ObjectList + 0xC,
		HighestPlayerObjectId = ObjectList + 0x10,

		CreateObject = 0x007eb320,// Possible mismatch
		AssignNetworkId = 0x008d1bf0,
		DestroyObject = 0x0067e070,
		ApplySkin = 0x00b2c310,
	};

	enum class Actor_Common
	{
		SetPath = 0x00ce9460,// Possible mismatch
		CreatePath = 0x00ee88d0,// Possible mismatch
		CreatePathInner = 0x0105806b,// Possible mismatch
		SmoothPath = 0x006a6370,
	};



	enum class NavMesh
	{
		NavMeshController = 0x0193ecb4,
		GetHeightForPosition = 0x00a292e1,
		IsWallOfGrass = 0x007baac2,
	};


	enum class GameObjectFunctions
	{
		OnDamage = 0x00573780,
		GetBaseDrawPosition = 0x00abf840,
		FOWRecall = 0x00dfeb40,// Possible mismatch
		OnLevelUp = 0x00c95e60,
		SetSkin = 0x00d5cde0,// Possible mismatch
		IssueOrder = 0x00c23d10,
		PlayAnimation = 0x00be08d0,
		OnDoCast = 0x00ea4130,
		CaptureTurret = 0x00e43150,// Possible mismatch
	};


	enum class SpellHelper
	{
		ComputeCharacterAttackCastDelay = 0x00ac9260,
		ComputeCharacterAttackDelay = 0x00c5d580,// Possible mismatch
		GetBasicAttack = 0x007dc140,
	};


	/*
	* BuffManager
	*/

	enum class BuffManager
	{
		BuffManagerInst = 0x2b28,
		BaseScriptBuff = 0x006b3270, //ALE-CA89343D
	};


	/*
	* Spellbook
	*/

	enum class Spellbook
	{
		//Functions
		SpellbookInst = 0x23e8,
		Client_DoCastSpell = 0x0074bdf0,
		Client_LevelSpell = 0x00e29bc0,
		Client_GetSpellstate = 0x01020a10,
		Client_UpdateChargeableSpell = 0x00e98970,// Possible mismatch
		Client_ForceStop = 0x00f20d50,// Possible mismatch
		Client_SpellSlotCanBeUpgraded = 0x006009a0,

		ProcessCastSpell = 0x01021060,
		OnCommonAutoAttack = 0x01021180,// Possible mismatch
		OnApplyCD = 0x00c589d0,
	};

	/*
	* HeroInventory
	*/

	enum class HeroInventory
	{
		Inventory = 0x1178,
		BuyItem = 0x00737aa0,
		SellItem = 0x00b45870,// Possible mismatch
		SwapItem = 0x00ac8290,
		InventoryPointer = 0x00000198,// Possible mismatch
	};


	/*
	* PWHUD
	*/

	enum class pwHud
	{
		pwHud_Instance = 0x0155aab4,
		pwHud_OnDraw = 0x00bcda20,
		SetUISelectedObjId = 0x00824c50,// Possible mismatch

		NOP_pwHud_DisableDrawing = 0x0095ad0d,// Possible mismatch
		NOP_pwHud_DisableHPBarDrawing = 0x00bcdb68,// Possible mismatch
		NOP_MenuGUI_DisableDrawing = 0x010125e6,// Possible mismatch
		NOP_MenuGUI_DisableMiniMap = 0x01012585,// Possible mismatch
	};


	enum class MenuGUI
	{
		MenuGUI_Instance = 0x031db6d0,
		DoEmote = 0x6D4CB0,
		PingMiniMap = 0x007a7420,
		CallCurrentPing = 0x00f28c90,// Possible mismatch
		DoMasteryBadge = 0xA53800,// Possible mismatch
	};


	enum class pwConsole
	{
		ShowClientSideMessage = 0x00b0f8e0,
		OnInput = 0x006a9b70,// Possible mismatch
		ProcessCommand = 0x00af43d0,
		pwClose = 0x00be3eb0,
		pwOpen = 0x005a70c0,
	};


	enum class HudManager
	{
		HudManagerInst = 0x0155f6b8,
	};


	/*
	* Hero Stats
	*/

	enum class HeroStats
	{
		HeroStatsInst = 0x3668,// Possible mismatch
		LoadHeroStat = 0xA0B490,
		GetHeroStats = 0x00792d90,
	};

	enum class r3dRenderer
	{
		r3dRendererInstance = 0x015560e8,// Possible mismatch
		DrawCircularRangeIndicator = 0x00815a50,
		r3dScreenTo3D = 0x00c74d40,
		r3dProjectToScreen = 0x006f9ca0,
	};


	enum class TacticalMap
	{
		ToWorldCoord = 0x00a289c0,// Possible mismatch
		ToMapCoord = 0x00c3ed30,
	};


	enum class RiotAsset
	{
		AssetExists = 0x00cbc840,
	};


	enum class BuffHost
	{
		BuffAddRemove = 0x00c85270,
	};



	enum class Experience
	{
		XPToNextLevel = 0x007a2b50,// Possible mismatch
		XPToCurrentLevel = 0x00c51430,// Possible mismatch
	};


	enum class HudGameChatObject
	{
		DisplayChat = 0x007f42a0,
		SetScale = 0x00685610,// Possible mismatch
	};


	enum class RiotString
	{
		TranslateString = 0x0097c7a0,
	};


	enum class AudioManager
	{
		PlaySound = 0x00dbfae0,
	};


	enum class FoundryItemShop
	{
		OnUndo = 0x005CDB10, //undo

		ToggleShop = 0x00f117c0,// Possible mismatch
		ActionCheckVT1 = 0x007b7fe0,// Possible mismatch
		ActionCheckVT2 = 0x005bdad0,// Possible mismatch

		VTOffset = 0xC4,
		IsOpen = 0x30
	};

	enum class r3dRenderLayer
	{
		LoadTexture = 0x00bdaa20,// Possible mismatch
		IRedTexturePacket1 = 0x015c9e30,// Possible mismatch
	};

	enum class RiotX3D
	{
		Reset = 0x007f87a0,// Possible mismatch
		BeginScene = 0x00756b30,// Possible mismatch
		Present = 0x0059e990,
		EndScene = 0x00ba7e10,// Possible mismatch
		SetRenderTarget = 0x00670580,
	};


	enum class TeemoClient
	{
		ComputeChecksum = 0x00d88bf0,
		B_ModulesVerified = 0x031eef70,// Possible mismatch
	};

	enum class HudVote
	{
		OnSurrenderVote = 0x00888850
	};
};