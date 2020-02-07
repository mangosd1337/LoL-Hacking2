#pragma once

//-----------GAMEOBJECT-----------//

enum class GameObject
{
	Type = 0x4,
	Team = 0x14,
	Name = 0x20,
	Position = 0x5C,
	ServerPosition = 0x5C,
	BoundingRadius = 0x90,
	BBox = 0x94,
	IsDead = 0x118,
	VisibleOnScreen = 0xD8,
	NetworkId = 0xFC,
};

//-----------ATTACKABLEUNIT-----------//

enum class AttackableUnit
{
	Health = 0x154,
	MaxHealth = Health + 0x10,
	Mana = 0x20c,
	MaxMana = Mana + 0x10,

	IsBot = 0x1170,
	HasBotAI = IsBot + 0x1,

	IsInVulnerable = 0x2D0,
	IsPhysicalImmune = IsInVulnerable + 0x10,
	MagicImmune = IsPhysicalImmune + 0x10,
	IsLifestealImmune = MagicImmune + 0x10,

	IsZombie = 0x12,

	ArmorMaterial = 0x330,
	WeaponMaterial = 0x348,

	AllShield = 0x194,
	AttackShield = 0x1A4,
	MagicShield = 0x1B4,

	OverrideCollisionRadius = 0x3a8, //dword ptr [edi+3C4h]
	OverrideCollisionHeight = OverrideCollisionRadius + 0x4, //dword ptr [edi+3C0h]
	PathfindingCollisionRadius = OverrideCollisionHeight + 0x4,

	Direction = 0x2BA0,
	IsTargetable = 0x1E8,
	IsTargetableToTeamFlags = 0x1F8,
};

//-----------AI_BASE-----------//

enum class Obj_AIBase
{
	ResourceName = 0x240,

	Gold = 0x1194,
	GoldTotal = Gold + 0x10,

	EvolvePoints = 0x35EC,
	PlayerControlled = 0x93C,

	//SpellCastBlockingAI = 0, //deprecated
	//AI_LastPetSpawnedID = 0, //deprecated
	//PetReturnRadius = 0, //deprecated
	//EnemyId = 0, //deprecated
	//TauntTargetId = EnemyId + 0x4,
	//FearLeashPoint = TauntTargetId + 0x4,

	//LastPausePosition = 0, //deprecated
	//DeathDuration = 0, //deprecated

	ExpGiveRadius = 0x934,
	AutoAttackTargettingFlags = 0x33B8,

	CharacterState = 0x95C + 0x4, //IssueOrder
	CharacterActionState = CharacterState + 0x4,
	CharacterIntermediate = 0x9b8,

	CombatType = 0x2E80,
	SkinName = 0x8d0, //INGAME

	//AIManager, Actor_Common
	AIManager = 0x2B9C,
	Actor_Common = 0x70,

	//SetSkin
	CharacterDataStack = 0x33E8,

	//UserComponent
	UserComponent = 0x2ED0, //Capture

	//CharData
	CharData = 0x3368, //x3344

	// 6.1
	mPercentDamageToBarracksMinionMod = 0xE68,
	mFlatDamageReductionFromBarracks = 0xE78,
};

//-----------MINION-----------//

enum class Obj_AIMinion
{
	RoamState = 0x35D8,
	OriginalState = RoamState + 0x4,
	CampNumber = OriginalState + 0x4,
	MinionLevel = 0x3664,
	LeashedPosition = 0x35E8
};

//-----------AIHEROCLIENT-----------//

enum class Obj_AIHero
{
	Experience = 0x362C,

	ChampionName = 0x8d0,
	Avatar = 0x5898,
	Level = Experience + 0x10,
	NumNeutralMinionsKilled = 0x37D0,
};

//-----------UnitInfoComponent-----------//

enum class UnitInfoComponent
{
	InfoComponent = 0x1160
};

//-----------Obj_BarracksDampener-----------//

enum class Obj_BarracksDampener
{
	DampenerState = 0x720
};


//-----------CHARDATASTACK-----------//

enum class CharacterDataStack
{
	ActiveModel = 0xC, //
	ActiveSkinId = 0x24 //
};

//-----------SPELLMISSILE-----------//

enum class Obj_SpellMissile
{
	SpellCaster = 0x150,
	LaunchPos = 0x150,
	DestPos = 0x15C,
	MissileData = 0x11C,
	SData = 0x13C,
	TargetId = 0x180,
	Path = 0x0
};

enum class SpellMissileData
{
	SpellCaster = 0x0,
	SData = 0xD8,
	CastInfo = 0x0
};

//-----------HEROINVENTORY-----------//

enum class InventorySlot
{
	Stacks = 0x4,
	Charges = 0x8,
	PurchaseTime = 0xC,
	ItemInfo = 0xC
};

enum class ItemNode
{
	BuffScript = 0x18,
	Slot = 0x10,
	Name = 0x0,
	ItemId = 0x70,
	MaxStacks = ItemId + 0x4,
	ItemCost = 0x98,
	RecipeItemIds = 0xB0
};

//-----------BUFFINSTANCE-----------//

enum class BuffInstance
{
	StartTime = 0x8,
	EndTime = 0xC,
	Type = 0x0,
	Index = 0x70,
	Count = 0x6C,
	IsVisible = 0xA8,

	ScriptBaseBuff = 0x4,
	BuffScriptInfo = 0x1C
};

enum class ScriptBaseBuff
{
	Name = 0x8,
	DisplayName = 0x34,
	ChildScriptBuff = 0x48
};

//-----------SPELLBOOK-----------//

enum class SpellbookStruct
{
	ActiveSpellSlot = 0xC,
	CastTime = 0x28,
	CastEndTime = 0x2C,
	TargetType = 0x8,
	Owner = 0x1C,
	SpellCaster_Client = 0x20,
	GetSpell = 0x518,

	SBookInst = 0x14
};

enum class SpellCastInfo
{
	SpellData = 0x0,
	Counter = 0x4,
	Level = 0x8, //+1
	TargetIndex = 0x4,
	Start = 0x30,
	End = 0x3C,
	Slot = 0x3b8
};

enum class MissileClient
{
	MissileClientData = 0x120,
	SpellCaster = 0x130,
	TargetId = 0x178,
};


enum class SpellDataInst
{
	Level = 0x10,
	CooldownExpires = 0x14,
	Ammo = 0x18,
	AmmoRechargeStart = 0x24,
	ToggleState = 0x28,
	Cooldown = 0x2C,
	SpellData = 0xDC,
	IsSealed = 0x84
};

//-----------DAMAGEINFO-----------//

enum class DamageInfo
{
	Damage = 0x24,
	DamageType = 0x54,
	DamageHitType = 0x248
};

//-----------NAVMESH-----------//

enum class NavMeshStruct
{
	Width = 0x520,
	Height = 0x524,
	CellMultiplicator = 0x52C,
	CellWidth = 0x34,
	CellHeight = 0x38,

	CellWidthDelta = 0x2C,
	CellHeightDelta = 0x30
};

//-----------ActorCommon-----------//

enum class ActorCommonStruct
{
	NavMesh = 0x100, //CreatePath
	CurrentPosition = 0x30A,
	HasNavPath = 0x1f8,
	AINavPath = 0x26C,
};

//-----------ClientFacade-----------//

enum class ClientFacadeStruct
{
	Region = 0x4,
	IP = 0x40,
	Port = 0x58,
	GameId = 0x6C,

	Virtual_GetPing = 0x98,
};

//-----------MenuGUI-----------//

enum class MenuGUIStruct
{
	IsActive = 0x48,
	IsTyping = 0x78,
	IsChatOpen = 0x7C,
	ActiveMessage = 0xFC,
};

//-----------pwHud-----------//

enum class pwHudStruct
{
	HudManager = 0x8,
	IsFocused = 0x5F,
};

//-----------HudManager-----------//

enum class HudManagerStruct
{
	CursorPos = 0x38,
	ShowPing = 0x1E1,

	VTShopMenu = 0xC4
};

//-----------r3dRenderer-----------//

enum class r3dRendererStruct
{
	View = 0x98, //w2s
	Projection = 0xD8,
	ClientWidth = 0x20,
	ClientHeight = 0x24,
};

//-----------TacticalMap-----------//

enum class TacticalMapStruct
{
	MinimapInst = 0xD0,
	MinimapX = 0x6C,
	MinimapY = MinimapX + 0x4,
	MinimapWidth = MinimapY + 0x4,
	MinimapHeight = MinimapWidth + 0x4,
	ScaleX = MinimapHeight + 0x4,
	ScaleY = ScaleX + 0x4,
};

//-----------Experience-----------//

enum class ExperienceStruct
{
	NumExperience = 0x0,
	Level = 0x10,
	SpellTrainingPoints = 0x34,
};

//-----------BuffManager-----------//

enum class BuffManagerStruct
{
	GetBegin = 0x18,
	GetEnd = 0x1C
};

//-----------RiotClock-----------//

enum class RiotClockStruct
{
	GameTime = 0x4, //Virtual
	Time = 0x2C,
	Delta = 0x14,
};

//-----------MissionInfo-----------//

enum class MissionInfoStruct
{
	GameType = 0x0,
	MapId = 0x4,
	GameMode = 0x8,
	GameId = 0x54
};
