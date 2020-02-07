from idc import BADADDR, INF_BASEADDR, SEARCH_DOWN, FUNCATTR_START, FUNCATTR_END
import idc
import idaapi
import datetime

# todo: 
'''
  CreateObject -> hook at pop esi
  Replace FindFunctionAddr with a proper function
  Create function to load Imports
  Game::DispatchEvent analyzation will fail for 99,9% as IDA can't detect function begin
  Spellbook::ProcessSpell returns wrong address?
'''

def MakeEnum(enumName, offsetArray):
	print ("enum class %s\r\n{" % enumName)
	for offset in offsetArray:
		if len(offset[0]) == 0:
			print ("")
			continue
		if type(offset[1]) is str:
			print ("   %s = %s," % ( offset[0], offset[1]))
			continue

		'''
		fncValue = offset[1] if offset[1] != -1 else 0x0
		'''
		#print "   %s = 0x%08x,%s" % (offset[0], fncValue, ' // Unknown' if fncValue == 0x0 else '')

		fncValue = offset[1] if offset[1] != -1 else 0x0

		locByName = idc.LocByName(offset[0])
		isMismatch = locByName != fncValue

		if locByName == BADADDR:
			locByName = fncValue

		print ("   %s = 0x%08x,%s" % (offset[0], locByName, '// Possible mismatch' if isMismatch else ''))

	print ("};\r\n")


def FindNOPAddr(name, offset):
	address = idc.FindBinary(0, SEARCH_DOWN, "\"" + name + "\"")
	dword = -1
	
	if address == BADADDR:
		return -1
	
	xrefs = XrefsTo(address)
	for xref in xrefs:
		dword = xref.frm + offset

	return dword

def FindFunctionAddrByPattern(displayName, pattern, offset, operandValue):
	address = idc.FindBinary(0, SEARCH_DOWN, pattern)
	if address != BADADDR:
		return BADADDR
	
	return idc.GetOperandValue(address, operandValue)

def FindFunctionAddr(name, offset, operandValue):
	address = idc.FindBinary(0, SEARCH_DOWN, "\"" + name + "\"")
	dword = -1
	
	if address == BADADDR:
		return BADADDR
	
	xrefs = XrefsTo(address)
	for xref in xrefs:
		dword = xref.frm + offset
	
	if dword == BADADDR:
		return BADADDR

	return idc.GetOperandValue(dword, operandValue)
	
def FindFunctionByPattern(pattern, offset):
	address = idc.FindBinary(0, SEARCH_DOWN, pattern)
		
	return address
	
def FindFunctionByPatternStartEA(pattern):
	address = idc.FindBinary(0, SEARCH_DOWN, pattern)
	if address == BADADDR:
		return BADADDR
	

	try:
		return idaapi.get_func(address).startEA
	except Exception:
		return -1
		
def FindFunctionFirstXRef(name):
	address = idc.FindBinary(0, SEARCH_DOWN, "\"" + name + "\"")
	dword = BADADDR
	
	if address == BADADDR:
		return BADADDR
	
	xrefs = XrefsTo(address)
	for xref in xrefs:
		dword = xref.frm
	
	try:
		return idaapi.get_func(dword).startEA
	except Exception:
		return -1
	
def main():	
	print ("[*] League of Legends Client Update Tool")
	print ("[*] By finndev for EloBuddy")
	print ("[*] Started at: %s" % datetime.datetime.now())
	print ("----------------------------")
	
	MakeEnum("ClientFacade", [
		["NetApiClient", FindFunctionAddr("Started System Init\n", -0xB, 1)],
		["NetApiNode", FindFunctionAddr("game_cornerdisplay_fps", -0xDF, 1)],
		["GameState", FindFunctionAddr("Received command to shut down.\n", 0x1D, 0)],
		["ProcessWorldEvent", FindFunctionFirstXRef("Received command to shut down.\n")]
	])
	

	MakeEnum("Game", [
		["Hud_OnDisconnect", FindFunctionFirstXRef("game_messagebox_caption_disconnect")],
		["Hud_OnAfk", FindFunctionFirstXRef("game_messagebox_text_afkwarningcaption")],
		["ClientMainLoop", FindFunctionFirstXRef("Waiting for all players to connect")],
		["DispatchEvent", FindFunctionByPattern("50 ff 52 08 83 c6 04 3b f7", -0x5a)],

		["", 0],

		["BuildDate", 0x0],
		["BuildTime", "BuildDate + 0xC"],
		["BuildVersion", "BuildTime + 0xC"],
		["BuildType", "BuildVersion + 0xC"],

		["MovementCheck", 0x0],
		["IsWindowFocused", 0],

		["", 0],

		["OnWndProc", 0],
		["CRepl32InfoUpdatePacket", 0],

		["", 0],

		["PingNOP1", 0],
		["PingNOP2", 0]
	])

	MakeEnum("MissionInfo", [
		["MissionInfoInst", FindFunctionAddr("GameStartData::GetMissionMode()", -0x14, 1)],
		["DrawTurretRange", FindFunctionByPatternStartEA("6a 09 b9 ?? ?? ?? ?? e8 ?? ?? ?? ?? 85 c0")]
	])

	MakeEnum("RiotClock", [
		["RiotClockInst", FindFunctionAddr("ALE-BACA394A", 0x18, 1)]
	])

	MakeEnum("ObjectManager", [
		["LocalPlayer", FindFunctionAddr(">>> Hero not found in manager!", -0x71, 1)],

		["", 0],

		["ObjectList", FindFunctionAddr("NetWorldObject: %s, netID:%x %s\n", 0x1a, 1)],
		["MaxSize", "ObjectList + 0x4"],
		["UsedIndexes", "ObjectList + 0x8"],
		["HighestObjectId", "ObjectList + 0xC"],
		["HighestPlayerObjectId", "ObjectList + 0x10"],

		["", 0],

		["CreateObject", FindFunctionByPatternStartEA("89 4e 08 a1 ?? ?? ?? ?? 8b 0d ?? ?? ?? ?? 89 34 88")],
		["AssignNetworkId", FindFunctionFirstXRef("GameObject Network ID is being reset for")],
		["DestroyObject", FindFunctionByPatternStartEA("2b f8 83 e7 fc 57 50 51 e8 ?? ?? ?? ?? 83 c4 0c")],
		["ApplySkin", FindFunctionFirstXRef("ALE-382B05A8")]
	])

	MakeEnum("Actor_Common", [
		["SetPath", FindFunctionByPatternStartEA("f3 0f 10 01 f3 0f 5c ?? 0f 54 c1 0f 2f d0")],
		["CreatePath", FindFunctionByPatternStartEA("80 bf 10 03 ?? ?? ?? b9 ?? ?? ?? ?? 51 b8")],
		["NavMesh_CreatePath", FindFunctionFirstXRef("ALE-30FDAB23") ],
		["SmoothPath", FindFunctionFirstXRef("ALE-E175B7A7")]
	])

	MakeEnum("NavMesh", [
		["NavMeshController", FindFunctionAddr("ALE-13DE600D", 0x21, 1)],
		["GetHeightForPosition", FindFunctionByPatternStartEA("8b 79 30 8d 47 ff 3b f0 7d")],
		["IsWallOfGrass", FindFunctionFirstXRef("Infinite loop detected")]
	])

	MakeEnum("GameObjectFunctions", [
		["OnDamage", FindFunctionByPatternStartEA("8b 04 b0 80 78 75 00 0f 85 ?? ?? ?? ?? 68 ?? ?? ?? ?? e8 ?? ?? ?? ?? 83 c4 04")],
		["GetBaseDrawPosition", FindFunctionByPatternStartEA("f3 0f 58 47 28 f3 0f 58 4f 2c f3 0f 58 57 30 8d 44 24 18 50 f3 0f 59 d8 f3 0f 59 e1 f3 0f 59 ea")],
		["FOWRecall", FindFunctionByPatternStartEA("6a ff 6a 00 56 8b cb e8 ?? ?? ?? ?? 8d 8d 80 02 00 00")],
		["OnLevelUp", FindFunctionFirstXRef("ALE-BD06C313")],
		["SetSkin", 0],
		["IssueOrder", FindFunctionByPatternStartEA("ff 10 8b 4d 08 b8 ?? ?? ?? ?? 80 7d 14 00")],
		["PlayAnimation", FindFunctionByPatternStartEA("3c 01 75 ?? 8b 45 1c 83 f8 02")],
		["OnDoCast", FindFunctionByPatternStartEA("89 5c 24 10 a1 ?? ?? ?? ?? a8 01 75 ?? 83 c8 01")],
		["CaptureTurret", FindFunctionByPatternStartEA("83 7c 24 1c 00 0f 84 ?? ?? ?? ?? 8b 7b 0c 89 7c 24 10 8b 7c 24 20 85 ff")]
	])

	MakeEnum("SpellHelper", [
		["ComputeCharacterAttackCastDelay", FindFunctionByPatternStartEA("33 db 8d 42 c0 83 f8 11 77 ?? 83 c2 c0 eb ??")],
		["ComputeCharacterAttackDelay", FindFunctionByPatternStartEA("c3 f3 0f 10 8e b0 0a 00 00 0f 57 d2 8b ce e8 ?? ?? ?? ?? 5f 5e")],
		["GetBasicAttack", FindFunctionByPatternStartEA("c1 fa 09 8b c2 c1 e8 1f 03 c2 3b f0 1b c0 23 c6")]
	])

	MakeEnum("BuffManager", [
		["BuffManagerInst", 0],
		["BaseScriptBuff", FindFunctionFirstXRef("ALE-CA89343D")]
	])

	MakeEnum("Spellbook", [
		["SpellbookInst", 0],
		["Client_DoCastSpell", FindFunctionFirstXRef("ERROR: Client Tried to cast a spell from an")],
		["Client_LevelSpell", FindFunctionFirstXRef("ALE-89BAB541")],
		["Client_GetSpellstate", FindFunctionFirstXRef("ALE-84F0B873")],
		["Client_UpdateChargeableSpell", FindFunctionByPatternStartEA("83 bd d4 ?? ?? ?? ?? 0f 84 ?? ?? ?? ?? 8d 4c 24 18 e8 ?? ?? ?? ?? c7 44 24 40 ?? ?? ?? ??")],
		["Client_ForceStop", FindFunctionByPatternStartEA("8d 54 24 1c 52 c6 03 ?? 8b 01 ff 50 18 8d 4c 24 14")],
		["Client_SpellSlotCanBeUpgraded", FindFunctionByPatternStartEA("39 44 24 14 72 ?? 46 47 83 fe 06 7c ??")],

		["", 0],

		["ProcessCastSpell", FindFunctionByPatternStartEA("8b 4e 04 83 c4 0c c1 e9 02 80 e1 01 6a 08 84 c0")],
		["OnCommonAutoAttack", FindFunctionByPatternStartEA("e8 ?? ?? ?? ?? 8b f0 83 c4 10 8b ce 8b 16 57 ff 52 04 8b 16")], 
		["OnApplyCD", FindFunctionFirstXRef("ALE-42E20CB6")],
	])

	MakeEnum("HeroInventory", [
		["Inventory", 0],
		["BuyItem", FindFunctionFirstXRef("GAME_UI: (HeroInventory) Item %d gold cost is too expensive\n")],
		["SellItem", FindFunctionByPatternStartEA("64 a3 ?? ?? ?? ?? 8b f9 8b 5c 24 3c 51 53 e8 ?? ?? ?? ?? 84 c0 0f 84 ?? ?? ?? ?? 8d 4c 24 1c")],
		["SwapItem", FindFunctionByPatternStartEA("8b f1 8b 5c 24 34 53 ff 74 24 34 e8 ?? ?? ?? ?? 84 c0 0f 84 ?? ?? ?? ??")],
		["InventoryPointer", 0x198]
	])

	MakeEnum("pwHud", [
		["pwHud_Instance", FindFunctionAddr("ALE-6B0E31F1", -0xCD, 1)],
		["pwHud_OnDraw", FindFunctionFirstXRef("ALE-4F389A4A")],
		["SetUISelectedObjId", FindFunctionByPatternStartEA("8b 35 ?? ?? ?? ?? 85 c0 74 ?? 8d 88 28 01 00 00 8b 01 8d 54 24 10 52 ff 50 04 8b 30")],

		["", 0],

		["NOP_pwHud_DisableDrawing", FindNOPAddr("RenderGPU function started failing to get a screen buffer.", 0xEE)],
		["NOP_pwHud_DisableHPBarDrawing", FindNOPAddr("ALE-4F389A4A", 0x10C)],
		["NOP_MenuGUI_DisableDrawing", FindNOPAddr("GAMESTATE_GAMELOOP DrawSysInfo", 0x1a)],
		["NOP_MenuGUI_DisableMiniMap", FindNOPAddr("GAMESTATE_GAMELOOP GUIMenuDraw", -0x1c)]
	])

	MakeEnum("MenuGUI", [
		["MenuGUI_Instance", FindFunctionAddr("game_chat_note_no_notes", 0xB, 1)],
		["DoEmote", FindFunctionByPatternStartEA("50 8d 44 24 1c 64 a3 ?? ?? ?? ?? 8b 35 ?? ?? ?? ?? 85 f6 74 ?? 8a 46 14 84 c0")],
		["PingMiniMap", FindFunctionByPatternStartEA("74 ?? 83 ff 05 74 ?? 33 c0 b9 ?? ?? ?? ?? 83 ff 02")],
		["CallCurrentPing", FindFunctionByPatternStartEA("e8 ?? ?? ?? ?? 8b 4c 24 34 8d 54 24 16 c7 44 24 2c 00 00 00 00 b8 ?? ?? ?? ?? 66 89 44 24 10 be")],
		["DoMasteryBadge", FindFunctionByPatternStartEA("e8 ?? ?? ?? ?? 8b 4c 24 34 8d 54 24 16 c7 44 24 2c 00 00 00 00 b8 ?? ?? ?? ?? 66 89 44 24 10 be")]
	])

	MakeEnum("pwConsole", [
		["ShowClientSideMessage", FindFunctionByPatternStartEA("85 d2 74 ?? 8a 42 48 84 c0 74 ?? 8b 76 04 85 f6 74 ?? 8d 46 04 b9")],
		["OnInput", FindFunctionByPatternStartEA("b1 01 f6 44 24 1b 20 75 ?? f6 44 24 0b 20 75 ?? 32 d2 eb")],
		["ProcessCommand", FindFunctionByPatternStartEA("c6 44 24 40 01 a1 ?? ?? ?? ?? a8 01 75 ?? 83 c8 01 a3")],
		["pwClose", FindFunctionByPatternStartEA("8b 86 ac ?? ?? ?? 85 c0 74 ?? 80 78 30 ?? 74 ?? 5e")],
		["pwOpen", FindFunctionFirstXRef("game_console_chatcommand_allchat_1")]
	])

	MakeEnum("HudManager", [
		["HudManagerInst", FindFunctionAddr("game_announcement_OnResume", -0x12, 1)]
	])

	MakeEnum("HeroStats", [
		["HeroStatsInst", 0],
		["GetHeroStats", FindFunctionFirstXRef("Tried to get value on a stat")]
	])

	MakeEnum("r3dRenderer", [
		["r3dRendererInstance", FindFunctionAddr("PS_GAMMA_WITH_COLOR_OVERRIDE", -0x6, 1)],
		["DrawCircularRangeIndicator", FindFunctionByPatternStartEA("f3 0f 11 54 24 14 f3 0f 11 5c 24 10 f3 0f 58 c1 0f 2f 05 ?? ?? ?? ?? 76 ?? e8")],
		["r3dScreenTo3D", FindFunctionByPatternStartEA("66 0f 6e c0 f3 0f e6 c0 c1 e8 1f 8b 8b a4 03 02 ?? 2b ca")],
		["r3dProjectToScreen", FindFunctionByPatternStartEA("0f 2f 05 ?? ?? ?? ?? 73 ?? 0f 2f da 77")]
	])

	MakeEnum("TacticalMap", [
		["ToWorldCoord", FindFunctionByPatternStartEA("f3 0f 11 47 08 8b 08 8b 01 8b 00")],
		["ToMapCoord", FindFunctionByPatternStartEA("f3 0f 5c cb f3 0f 11 0b f3 0f 10 17")]
	])

	MakeEnum("RiotAsset", [
		["AssetExists", FindFunctionByPatternStartEA("38 44 24 07 74 ?? 38 45 10")]
	])

	MakeEnum("BuffHost", [
		["BuffAddRemove", FindFunctionFirstXRef("SpellToggleSlot")]
	])

	MakeEnum("Experience", [
		["XPToNextLevel", FindFunctionByPatternStartEA("8b 4e 30 8b 7e 10 8b 01 ff 50 04")],
		["XPToCurrentLevel", FindFunctionByPatternStartEA("83 fe 01 75 ?? f3 0f 11 44 24 08 7e ??")]
	])

	MakeEnum("HudGameChatObject", [
		["DisplayChat", FindFunctionFirstXRef("MaxChatBufferSize")],
		["SetScale", FindFunctionFirstXRef("SetChatScaleX")]
	])

	MakeEnum("RiotString", [
		["TranslateString", FindFunctionByPatternStartEA("33 c0 e9 ?? ?? ?? ?? a1 ?? ?? ?? ?? a8 01 75 ?? 83 c8 01")]
	])

	MakeEnum("AudioManager", [
		["PlaySound", FindFunctionFirstXRef("AudioManager::PlaySoundEvent: Failed to play sound event %s.")]
	])

	#todo: add signatures
	MakeEnum("FoundryItemShop", [
		["OnUndo", 0],
		["ToggleShop", 0],
		["ActionCheckVT1", 0],
		["ActionCheckVT2", 0]
	])

	MakeEnum("r3dRenderLayer", [
		["LoadTexture", 0],
		["IRedTexturePackte1", 0]
	])

	MakeEnum("RiotX3D", [
		["RiotX3D_Reset", FindFunctionFirstXRef("Riot::X3D::Legacy::D3D9::D3D9Throws::Direct3DDevice9_Reset")],
		["RiotX3D_BeginScene", FindFunctionFirstXRef("Riot::X3D::Legacy::D3D9::D3D9Throws::Direct3DDevice9_BeginScene")],
		["RiotX3D_Present", FindFunctionFirstXRef("Riot::X3D::Legacy::D3D9::D3D9Throws::Direct3DDevice9_Present")],
		["RiotX3D_EndScene", FindFunctionFirstXRef("Riot::X3D::Legacy::D3D9::D3D9Throws::Direct3DDevice9_EndScene")],
		["RiotX3D_SetRenderTarget", FindFunctionFirstXRef("Riot::X3D::Legacy::D3D9::D3D9Throws::Direct3DDevice9_SetRenderTarget")]
	])

	MakeEnum("TeemoClient", [
		["TeemoClient_ComputeChecksum", FindFunctionByPatternStartEA("66 89 85 94 ff fe ff 68 ?? ?? ?? ?? 50 8d 85 96 ff fe ff 50 e8")],
		["B_ModulesVerified", 0]
	])

	MakeEnum("HudVote", [
		["OnSurrenderVote0", FindFunctionByPatternStartEA("e8 ?? ?? ?? ?? 8b 4c 24 34 8d 54 24 16 c7 44 24 2c 00 00 00 00 b8 ?? ?? ?? ?? 66 89 44 24 10 be")],
	])

	print ('----------------------------')
	print ("[*] Finished")
	
main()