#pragma once
#include "Utils.h"
#include <vector>
#include "Macros.h"
#include <functional>
#include "GameObject.h"
#include "SpellCastInfo.h"
#include "Obj_AI_Base.h"
#include "ClientNode.h"
#include "AttackableUnit.h"
#include "LuaEzCb.h"

#ifndef N_MANAGED_BUILD
	#include <ppl.h>
	#include <future>
#endif


#define EVENT_TIMEOUT_EJECT 250

namespace EloBuddy
{
	namespace Native
	{
		class C2S_ENetPacket;
		class S2C_ENetPacket;
		class ItemNode;

		//Game
		typedef bool(OnWndProc)(HWND, uint, WPARAM, LPARAM);
		typedef void(OnGameUpdate)();
		typedef void(OnGameStart)();
		typedef void(OnGameEnd)();
		typedef void(OnGameLoad)();
		typedef void(OnGamePreTick)();
		typedef void(OnGameTick)();
		typedef void(OnGamePostTick)();
		typedef bool(OnGameAfk)();
		typedef void(OnGameDisconnect)();
		typedef void(OnGameNotify)(uint, int);
		//Drawing
		typedef void(OnDrawingBeginScene)();
		typedef void(OnDrawingDraw)();
		typedef void(OnDrawingEndScene)();
		typedef void(OnDrawingPostReset)();
		typedef void(OnDrawingPreReset)();
		typedef void(OnDrawingPresent)();
		typedef void(OnDrawingSetRenderTarget)();
		typedef void(OnDrawingFlushEndScene)();
		typedef bool(OnDrawingHealthBars)(UnitInfoComponent*, AttackableUnit*);
		//GameObject
		typedef void(OnGameObjectCreate)(GameObject*);
		typedef void(OnGameObjectDelete)(GameObject*);
		typedef void(OnGameObjectFloatPropertyChange)(GameObject*, const char*, float);
		typedef void(OnGameObjectIntegerPropertyChange)(GameObject*, const char*, float);
		//Obj_AI_Base
		typedef bool(OnObjAIBaseProcessSpellcast)(Obj_AI_Base*, SpellCastInfo*);
		typedef bool(OnObjAIBaseIssueOrder)(Obj_AI_Base*, uint, Vector3f*, GameObject*, bool);
		typedef void(OnObjAIBaseTeleport)(Obj_AI_Base*, char*, char*);
		typedef void(OnObjAIBaseNewPath)(Obj_AI_Base*, std::vector<Native::Vector3f>*, bool, float);
		typedef bool(OnObjAIBasePlayAnimation)(Obj_AI_Base*, char**);
		typedef void(OnObjAIBaseAddBuff)(Obj_AI_Base*, BuffInstance*);
		typedef void(OnObjAIBaseRemoveBuff)(Obj_AI_Base*, BuffInstance*);
		typedef void(OnObjAIBaseUpdateBuff)(Obj_AI_Base*, BuffInstance*);
		typedef void(OnObjAIBaseLevelUp)(Obj_AI_Base*, int);
		typedef void(OnObjAIBaseUpdateModel)(Obj_AI_Base*, char*, int);
		typedef void(OnObjAIBaseUpdatePosition)(Obj_AI_Base*, Vector3f*);
		typedef void(OnObjAIBaseDoCast)(Obj_AI_Base*, SpellCastInfo*);
		typedef void(OnObjAIBaseBasicAttack)(Obj_AI_Base*, SpellCastInfo*);
		typedef void(OnObjAIBaseSurrenderVote)(Obj_AI_Base*, byte);
		//AIHeroClient
		typedef void(OnObjAIHeroDeath)(Obj_AI_Base*, float);
		typedef void(OnObjAIHeroSpawn)(AIHeroClient*);
		typedef void(OnObjAIHeroApplyCD)(AIHeroClient*, SpellDataInst*, uint);
		//Player
		typedef bool(OnPlayerBuyItem)(AIHeroClient*, int, ItemNode*);
		typedef bool(OnPlayerSellItem)(AIHeroClient*, int, ItemNode*);
		typedef bool(OnPlayerSwapItem)(AIHeroClient*, uint, uint);
		typedef bool(OnPlayerDoEmote)(AIHeroClient*, short);
		//AttackableUnit
		typedef void(OnAttackableUnitModifyShield)(AttackableUnit*, float, float);
		typedef void(OnAttackableUnitOnDamage)(AttackableUnit*, AttackableUnit*, float, DamageLayout*);
		//ENet
		typedef bool(OnGameSendPacket)(C2S_ENetPacket*, uint, uint, DWORD);
		typedef bool(OnGameProcessPacket)(S2C_ENetPacket*, uint, uint, DWORD);
		//Spellbook
		typedef bool(OnSpellbookCastSpell)(Obj_AI_Base*, Spellbook*, Vector3f*, Vector3f*, uint, int);
		typedef void(OnSpellbookStopCast)(Obj_AI_Base*, bool, bool, bool, bool, int, int);
		typedef bool(OnSpellbookUpdateChargeableSpell)(Spellbook*, int, Native::Vector3f*, bool);
		//TacticalMap
		typedef bool(OnTacticalMapPing)(Vector3f*, GameObject*, GameObject*, uint pingType);
		//Chat
		typedef bool(OnChatInput)(char**);
		typedef bool(OnChatMessage)(AIHeroClient*, char**);
		typedef bool(OnChatClientSideMessage)(char**);
		typedef bool(OnChatSendWhisper)(char**, char**);
		//Hud
		typedef void(OnHudTargetChange)(GameObject*);
		//AudioManager
		typedef void(OnAudioManagerPlaySound)(std::string);
		//Shop
		typedef bool(OnShopOpen)();
		typedef bool(OnCloseShop)();
		typedef bool(OnUndoPurchase)();
		//r3dCamera
		typedef bool(OnCameraSnap)();
		typedef bool(OnCameraToggleLock)();
		typedef bool(OnCameraUpdate)(float, float);
		typedef bool(OnCameraZoom)();
		//ObjectManager
		typedef bool(OnObjectStackLoad)(GameObject*, char**, int*, int*, Vector3f**);

		// ReSharper disable once CppClassNeedsConstructorBecauseOfUninitializedMember
		template <int uniqueEventNumber, typename T, typename ... TArgs> class DLLEXPORT EventHandler
		{
			std::vector<void*> m_EventCallbacks;
			DWORD t_RemovalTickCount;
			static EventHandler* instance;
		public:
			static EventHandler* GetInstance()
			{
				if (instance == nullptr)
				{
					instance = new EventHandler();
				}

				return instance;
			}

			void Add(void* callback)
			{
				if (callback != nullptr)
				{
					m_EventCallbacks.push_back(callback);
				}
			}

			void Remove(void* listener)
			{
				if (listener == nullptr)
				{
					return;
				}

				auto eventPtr = find(m_EventCallbacks.begin(), m_EventCallbacks.end(), listener);
				if (eventPtr != m_EventCallbacks.end())
				{
					m_EventCallbacks.erase(find(m_EventCallbacks.begin(), m_EventCallbacks.end(), listener));
				}

				this->t_RemovalTickCount = GetTickCount();
			}

			bool __cdecl TriggerProcess(TArgs... args)
			{
				auto process = true;
				auto tickCount = GetTickCount();

				for (auto ptr : m_EventCallbacks)
				{
					if (ptr != nullptr)
					{
						if (tickCount - t_RemovalTickCount > EVENT_TIMEOUT_EJECT)
						{
							if (!static_cast<T*>(ptr) (args...))
							{
								process = false;
							}
						}
					}
				}

				return process;
			}

			bool __cdecl Trigger(TArgs... args)
			{
				auto tickCount = GetTickCount();

				for (auto ptr : m_EventCallbacks)
				{
					if (ptr != nullptr)
					{
						if (tickCount - t_RemovalTickCount > EVENT_TIMEOUT_EJECT)
						{
							static_cast<T*>(ptr) (args...);
						}
					}
				}

				return true;
			}
		};
	}
}
