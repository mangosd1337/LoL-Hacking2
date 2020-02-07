#include "stdafx.h"

#include "../../EloBuddy.Core/EloBuddy.Core/ObjectManager.h"
#include "../../EloBuddy.Core/EloBuddy.Core/Obj_AI_Base.h"
#include "../../EloBuddy.Core/EloBuddy.Core/StaticEnums.h"
#include "../../EloBuddy.Core/EloBuddy.Core/Console.h"
#include "../../EloBuddy.Core/EloBuddy.Core/Vector3f.h"
#include "../../EloBuddy.Core/EloBuddy.Core/r3dRenderer.h"
#include "../../EloBuddy.Core/EloBuddy.Core/Actor_Common.h"

#include "Obj_AI_Base.hpp"
#include "BuffInstance.hpp"

using namespace EloBuddy::Native;

namespace EloBuddy
{
	static Obj_AI_Base::Obj_AI_Base()
	{
		ATTACH_DOMAIN();
		ATTACH_EVENT
		(
			Obj_AI_ProcessSpellCast,
			14, Native::OnObjAIBaseProcessSpellcast, Native::Obj_AI_Base*, Native::SpellCastInfo*
		);
		ATTACH_EVENT
		(
			Obj_AI_BaseTeleport,
			16, Native::OnObjAIBaseTeleport, Native::Obj_AI_Base*, char*, char*
		);
		ATTACH_EVENT
		(
			Obj_AI_BaseNewPath,
			17, Native::OnObjAIBaseNewPath, Native::Obj_AI_Base*, std::vector<Native::Vector3f>*, bool, float
		);
		ATTACH_EVENT
		(
			Obj_AI_BasePlayAnimation,
			18, Native::OnObjAIBasePlayAnimation, Native::Obj_AI_Base*, char**
		);
		ATTACH_EVENT
		(
			Obj_AI_BaseBuffGain,
			37, Native::OnObjAIBaseAddBuff, Native::Obj_AI_Base*, Native::BuffInstance*
		);
		ATTACH_EVENT
		(
			Obj_AI_BaseBuffLose,
			38, Native::OnObjAIBaseRemoveBuff, Native::Obj_AI_Base*, Native::BuffInstance*
		);
		ATTACH_EVENT
		(
			Obj_AI_BaseBuffUpdate,
			39, Native::OnObjAIBaseUpdateBuff, Native::Obj_AI_Base*, Native::BuffInstance*
		);
		ATTACH_EVENT
		(
			Obj_AI_BaseLevelUp,
			44, Native::OnObjAIBaseLevelUp, Native::Obj_AI_Base*, int
		);
		ATTACH_EVENT
		(
			Obj_AI_UpdateModel,
			51, Native::OnObjAIBaseUpdateModel, Native::Obj_AI_Base*, char*, int
		);
		ATTACH_EVENT
		(
			Obj_AI_UpdatePosition,
			70, Native::OnObjAIBaseUpdatePosition, Native::Obj_AI_Base*, Native::Vector3f*
		);
		ATTACH_EVENT
		(
			Obj_AI_BaseDoCastSpell,
			71, Native::OnObjAIBaseDoCast, Native::Obj_AI_Base*, Native::SpellCastInfo*
		);
		ATTACH_EVENT
		(
			Obj_AI_BaseOnBasicAttack,
			73, Native::OnObjAIBaseBasicAttack, Native::Obj_AI_Base*, Native::SpellCastInfo*
		);
		ATTACH_EVENT
		(
			Obj_AI_BaseOnSurrenderVote,
			74, Native::OnObjAIBaseSurrenderVote, Native::Obj_AI_Base*, byte
		);
	}

	void Obj_AI_Base::DomainUnloadEventHandler( System::Object^, System::EventArgs^ )
	{
		DETACH_EVENT
		(
			Obj_AI_ProcessSpellCast,
			14, Native::OnObjAIBaseProcessSpellcast, Native::Obj_AI_Base*, Native::SpellCastInfo*
		);
		DETACH_EVENT
		(
			Obj_AI_BaseTeleport,
			16, Native::OnObjAIBaseTeleport, Native::Obj_AI_Base*, char*, char*
		);
		DETACH_EVENT
		(
			Obj_AI_BaseNewPath,
			17, Native::OnObjAIBaseNewPath, Native::Obj_AI_Base*, std::vector<Native::Vector3f>*, bool, float
		);
		DETACH_EVENT
		(
			Obj_AI_BasePlayAnimation,
			18, Native::OnObjAIBasePlayAnimation, Native::Obj_AI_Base*, char**
		);
		DETACH_EVENT
		(
			Obj_AI_BaseBuffGain,
			37, Native::OnObjAIBaseAddBuff, Native::Obj_AI_Base*, Native::BuffInstance*
		);
		DETACH_EVENT
		(
			Obj_AI_BaseBuffLose,
			38, Native::OnObjAIBaseRemoveBuff, Native::Obj_AI_Base*, Native::BuffInstance*
		);
		DETACH_EVENT
		(
			Obj_AI_BaseBuffUpdate,
			39, Native::OnObjAIBaseUpdateBuff, Native::Obj_AI_Base*, Native::BuffInstance*
		);
		DETACH_EVENT
		(
			Obj_AI_BaseLevelUp,
			44, Native::OnObjAIBaseLevelUp, Native::Obj_AI_Base*, int
		);
		DETACH_EVENT
		(
			Obj_AI_UpdateModel,
			51, Native::OnObjAIBaseUpdateModel, Native::Obj_AI_Base*, char*, int
		);
		DETACH_EVENT
		(
			Obj_AI_UpdatePosition,
			70, Native::OnObjAIBaseUpdatePosition, Native::Obj_AI_Base*, Native::Vector3f*
		);
		DETACH_EVENT
		(
			Obj_AI_BaseDoCastSpell,
			71, Native::OnObjAIBaseDoCast, Native::Obj_AI_Base*, Native::SpellCastInfo*
		);
		DETACH_EVENT
		(
			Obj_AI_BaseOnBasicAttack,
			73, Native::OnObjAIBaseBasicAttack, Native::Obj_AI_Base*, Native::SpellCastInfo*
		);
		DETACH_EVENT
		(
			Obj_AI_BaseOnSurrenderVote,
			74, Native::OnObjAIBaseSurrenderVote, Native::Obj_AI_Base*, byte
		);
	}

	bool Obj_AI_Base::OnObj_AI_ProcessSpellCastNative( Native::Obj_AI_Base* unit, Native::SpellCastInfo* castInfo )
	{
		auto process = true;

		START_TRACE
			if (unit != nullptr && castInfo != nullptr)
			{
				//Native::Console::PrintLn( "ProcessSpell: %p -> %p", unit, castInfo );


				auto vecStart = castInfo->GetStart();
				auto vecEnd = castInfo->GetEnd();

				auto start = Vector3( vecStart->GetX(), vecStart->GetZ(), vecStart->GetY() );
				auto end = Vector3( vecEnd->GetX(), vecEnd->GetZ(), vecEnd->GetY() );
				Obj_AI_Base^ sender = static_cast<Obj_AI_Base^>(ObjectManager::CreateObjectFromPointer( unit ));
				auto args = gcnew GameObjectProcessSpellCastEventArgs( gcnew SpellData( castInfo->GetSpellData() ), castInfo->GetLevel(), start, end, castInfo->GetLocalId(), *castInfo->GetCounter(), static_cast<SpellSlot>(*castInfo->GetSpellSlot()), false );

				if (sender == nullptr)
				{
					return true;
				}

				for each (auto eventHandle in Obj_AI_ProcessSpellCastHandlers->ToArray())
				{
					START_TRACE
						eventHandle( sender, args );
					END_TRACE
				}
			}
		END_TRACE

		return process;
	}

	void Obj_AI_Base::OnObj_AI_BaseTeleportNative( Native::Obj_AI_Base* unit, char* recallType, char* recallName )
	{
		START_TRACE
			if (unit != nullptr)
			{
				auto sender = static_cast<Obj_AI_Base^>(ObjectManager::CreateObjectFromPointer( unit ));
				auto args = gcnew GameObjectTeleportEventArgs( gcnew String( recallType ), gcnew String( recallName ) );

				for each (auto eventHandle in Obj_AI_BaseTeleportHandlers->ToArray())
				{
					START_TRACE
						eventHandle( sender, args );
					END_TRACE
				}
			}
		END_TRACE
	}

	void Obj_AI_Base::OnObj_AI_BaseNewPathNative( Native::Obj_AI_Base* unit, std::vector<Native::Vector3f>* wpVector, bool isDash, float speed )
	{
		START_TRACE
			if (unit == nullptr || wpVector == nullptr)
			{
				return;
			}

			auto vectorList = gcnew List<Vector3>();

			auto vector = *wpVector;
			for (auto path = vector.begin(); path != vector.end(); ++path)
			{
				vectorList->Add( Vector3( path->GetX(), path->GetZ(), path->GetY() ) );
			}

			auto sender = static_cast<Obj_AI_Base^>(ObjectManager::CreateObjectFromPointer( unit ));
			auto args = gcnew GameObjectNewPathEventArgs( vectorList->ToArray(), isDash, speed );

			for each (auto eventHandle in Obj_AI_BaseNewPathHandlers->ToArray())
			{
				START_TRACE
					eventHandle( sender, args );
				END_TRACE
			}
		END_TRACE
	}

	bool Obj_AI_Base::OnObj_AI_BasePlayAnimationNative( Native::Obj_AI_Base* unit, char** animation )
	{
		auto process = true;

		START_TRACE
			if (unit != nullptr)
			{
				auto sender = static_cast<Obj_AI_Base^>(ObjectManager::CreateObjectFromPointer( unit ));
				auto args = gcnew GameObjectPlayAnimationEventArgs( animation );

				for each (auto eventHandle in Obj_AI_BasePlayAnimationHandlers->ToArray())
				{
					START_TRACE
						eventHandle( sender, args );

					if (!args->Process)
						process = false;
					END_TRACE
				}
			}
		END_TRACE

			return process;
	}

	void Obj_AI_Base::OnObj_AI_BaseBuffGainNative( Native::Obj_AI_Base* unit, Native::BuffInstance* buffInst )
	{
		START_TRACE
			if (unit != nullptr && buffInst != nullptr)
			{
				auto sender = static_cast<Obj_AI_Base^>(ObjectManager::CreateObjectFromPointer( unit ));
				auto buff = gcnew BuffInstance( buffInst, *unit->GetNetworkId(), *buffInst->GetIndex() );

				if (!cachedBuffs->ContainsKey( sender->NetworkId ))
				{
					cachedBuffs [sender->NetworkId] = gcnew List<BuffInstance^>();
				}

				if (buffInst->IsValid())
				{
					cachedBuffs [sender->NetworkId]->Add( buff );
				}

				if (Obj_AI_BaseBuffGainHandlers->Count > 0)
				{
					auto args = gcnew Obj_AI_BaseBuffGainEventArgs( buff );

					for each(auto eventHandle in Obj_AI_BaseBuffGainHandlers->ToArray())
					{
						eventHandle( sender, args );
					}
				}
			}
		END_TRACE
	}

	void Obj_AI_Base::OnObj_AI_BaseBuffLoseNative( Native::Obj_AI_Base* unit, Native::BuffInstance* buffInst )
	{
		START_TRACE
			if (unit != nullptr && buffInst != nullptr)
			{
				auto sender = static_cast<Obj_AI_Base^>(ObjectManager::CreateObjectFromPointer( unit ));
				auto buff = gcnew BuffInstance( buffInst, *unit->GetNetworkId(), *buffInst->GetIndex() );

				if (Obj_AI_BaseBuffLoseHandlers->Count > 0)
				{
					auto args = gcnew Obj_AI_BaseBuffLoseEventArgs( buff );

					for each(auto eventHandle in Obj_AI_BaseBuffLoseHandlers->ToArray())
					{
						eventHandle( sender, args );
					}
				}

				if (cachedBuffs->ContainsKey( sender->NetworkId ))
				{
					auto address = buff->MemoryAddress;
					for each (auto cachedBuff in cachedBuffs [sender->NetworkId]->ToArray())
					{
						if (cachedBuff->MemoryAddress == address)
						{
							cachedBuffs [sender->NetworkId]->Remove( cachedBuff );
						}
					}
				}
			}
		END_TRACE
	}

	void Obj_AI_Base::OnObj_AI_BaseBuffUpdateNative( Native::Obj_AI_Base* unit, Native::BuffInstance* buffInst )
	{
		START_TRACE
			if (unit != nullptr && buffInst != nullptr)
			{
				if (Obj_AI_BaseBuffUpdateHandlers->Count > 0)
				{
					auto sender = static_cast<Obj_AI_Base^>(ObjectManager::CreateObjectFromPointer( unit ));
					auto buff = gcnew BuffInstance( buffInst, *unit->GetNetworkId(), *buffInst->GetIndex() );

					auto args = gcnew Obj_AI_BaseBuffUpdateEventArgs( buff );

					for each(auto eventHandle in Obj_AI_BaseBuffUpdateHandlers->ToArray())
					{
						eventHandle( sender, args );
					}
				}
			}
		END_TRACE
	}

	void Obj_AI_Base::OnObj_AI_BaseLevelUpNative( Native::Obj_AI_Base* unit, int level )
	{
		START_TRACE
			if (unit != nullptr)
			{
				auto sender = static_cast<Obj_AI_Base^>(ObjectManager::CreateObjectFromPointer( unit ));
				auto args = gcnew Obj_AI_BaseLevelUpEventArgs( sender, level );

				for each (auto eventHandle in Obj_AI_BaseLevelUpHandlers->ToArray())
				{
					START_TRACE
						eventHandle( sender, args );
					END_TRACE
				}
			}
		END_TRACE
	}

	bool Obj_AI_Base::OnObj_AI_UpdateModelNative( Native::Obj_AI_Base* unit, char* model, int skinId )
	{
		auto process = true;

		START_TRACE
			if (unit != nullptr)
			{
				auto sender = static_cast<Obj_AI_Base^>(ObjectManager::CreateObjectFromPointer( unit ));
				auto args = gcnew UpdateModelEventArgs( gcnew String( model ), skinId );

				for each (auto eventHandle in Obj_AI_UpdateModelHandlers->ToArray())
				{
					START_TRACE
						eventHandle( sender, args );

					if (!args->Process)
						process = false;
					END_TRACE
				}
			}
		END_TRACE

		return process;
	}

	void Obj_AI_Base::OnObj_AI_UpdatePositionNative( Native::Obj_AI_Base* unit, Native::Vector3f* position )
	{
		START_TRACE
			if (unit != nullptr)
			{
				auto sender = static_cast<Obj_AI_Base^>(ObjectManager::CreateObjectFromPointer( unit ));
				auto args = gcnew Obj_AI_UpdatePositionEventArgs( sender, Vector3( position->GetX(), position->GetZ(), position->GetY() ) );

				for each (auto eventHandle in Obj_AI_UpdatePositionHandlers->ToArray())
				{
					START_TRACE
						eventHandle( sender, args );
					END_TRACE
				}
			}
		END_TRACE
	}

	void Obj_AI_Base::OnObj_AI_BaseDoCastSpellNative( Native::Obj_AI_Base* unit, Native::SpellCastInfo* castInfo )
	{
		START_TRACE
			if (unit != nullptr && castInfo != nullptr)
			{
				//Native::Console::PrintLn( "DoCast: %p -> %p", unit, castInfo );

				auto vecStart = castInfo->GetStart()->SwitchYZ();
				auto vecEnd = castInfo->GetEnd()->SwitchYZ();

				auto start = Vector3( vecStart.GetX(), vecStart.GetY(), vecStart.GetZ() );
				auto end = Vector3( vecEnd.GetX(), vecEnd.GetY(), vecEnd.GetZ() );
				auto sender = static_cast<Obj_AI_Base^>(ObjectManager::CreateObjectFromPointer( unit ));
				auto args = gcnew GameObjectProcessSpellCastEventArgs( gcnew SpellData( castInfo->GetSpellData() ), castInfo->GetLevel(), start, end, castInfo->GetLocalId(), *castInfo->GetCounter(), static_cast<SpellSlot>(*castInfo->GetSpellSlot()), *castInfo->GetSpellData()->GetIsToggleSpell() );

				if (sender == nullptr)
				{
					return;
				}

				for each (auto eventHandle in Obj_AI_BaseDoCastSpellHandlers->ToArray())
				{
					START_TRACE
						eventHandle( sender, args );
					END_TRACE
				}
			}
		END_TRACE
	}

	void Obj_AI_Base::OnObj_AI_BaseOnBasicAttackNative( Native::Obj_AI_Base* unit, Native::SpellCastInfo* castInfo )
	{
		START_TRACE
			if (unit != nullptr)
			{
				//Native::Console::PrintLn( "BasicAttack: %p -> %p", unit, castInfo );

				auto vecStart = castInfo->GetStart()->SwitchYZ();
				auto vecEnd = castInfo->GetEnd()->SwitchYZ();

				auto start = Vector3( vecStart.GetX(), vecStart.GetY(), vecStart.GetZ() );
				auto end = Vector3( vecEnd.GetX(), vecEnd.GetY(), vecEnd.GetZ() );
				auto sender = static_cast<Obj_AI_Base^>(ObjectManager::CreateObjectFromPointer( unit ));
				auto args = gcnew GameObjectProcessSpellCastEventArgs( gcnew SpellData( castInfo->GetSpellData() ), castInfo->GetLevel(), start, end, castInfo->GetLocalId(), *castInfo->GetCounter(), static_cast<SpellSlot>(*castInfo->GetSpellSlot()), *castInfo->GetSpellData()->GetIsToggleSpell() );

				if (sender == nullptr)
				{
					return;
				}
					
				for each (auto eventHandle in Obj_AI_BaseOnBasicAttackHandlers->ToArray())
				{
					START_TRACE
						eventHandle( sender, args );
					END_TRACE
				}
			}
		END_TRACE
	}

	void Obj_AI_Base::OnObj_AI_BaseOnSurrenderVoteNative( Native::Obj_AI_Base* unit, byte surrenderVoteType )
	{
		START_TRACE
			if (unit != nullptr)
			{
				auto sender = static_cast<Obj_AI_Base^>(ObjectManager::CreateObjectFromPointer( unit ));
				auto args = gcnew Obj_AI_BaseSurrenderVoteEventArgs( surrenderVoteType );

				for each (auto eventHandle in Obj_AI_BaseOnSurrenderVoteHandlers->ToArray())
				{
					START_TRACE
						eventHandle( sender, args );
					END_TRACE
				}
			}
		END_TRACE
	}

	Spellbook^ Obj_AI_Base::Spellbook::get()
	{
		return gcnew EloBuddy::Spellbook( this->GetPtr() );
	}

	Vector3 Obj_AI_Base::ServerPosition::get()
	{
		auto ptr = this->GetPtr();
		if (ptr != nullptr)
		{
			return Vector3( ptr->GetServerPosition().GetX(), ptr->GetServerPosition().GetY(), ptr->GetServerPosition().GetZ() );
		}
		return Vector3::Zero;
		/*
		auto ptr = this->GetPtr();
		if (ptr != nullptr)
		{
			auto AIManager = *ptr->GetAIManager_Client();
			if (AIManager != nullptr)
			{
				auto actor = AIManager->GetActor();
				if (actor != nullptr)
				{
					auto pos = actor->GetServerPosition();
					return Vector3( pos->GetX(), pos->GetZ(), pos->GetY() );
				}
			}
		}

		return Vector3::Zero;*/
	}

	int Obj_AI_Base::GetBuffCount( System::String^ buffName )
	{
		if (!String::IsNullOrEmpty(buffName))
		{
			auto buffMgr = this->GetPtr()->GetBuffManager();
			auto buffPtr = Marshal::StringToHGlobalAnsi(buffName);

			if (buffMgr != nullptr)
			{
				auto buffBegin = *buffMgr->GetBegin();
				auto buffEnd = *buffMgr->GetEnd();
				auto size = (buffEnd - buffBegin) / sizeof(Native::BuffInstance);

				if (buffBegin != buffEnd)
				{
					for (uint i = 0; i < size; i++)
					{
						auto buffNode = buffBegin + i;
						auto buffInst = buffNode->buffInst;

						if (buffNode != nullptr && buffInst != nullptr)
						{
							if (buffInst->IsValid() && buffInst->IsActive())
							{
								auto scriptBuff = buffInst->GetScriptBaseBuff();

								if (_strcmpi(scriptBuff->GetName(), (char*) buffPtr.ToPointer()) == 0 || _strcmpi(scriptBuff->GetVirtual()->GetDisplayName(), (char*) buffPtr.ToPointer()) == 0)
								{
									Marshal::FreeHGlobal(buffPtr);
									return buffInst->GetCount();
								}
							}
						}
					}
				}
			}

			Marshal::FreeHGlobal(buffPtr);
		}

		return -1;
	}

	List<BuffInstance^>^ Obj_AI_Base::Buffs::get()
	{
		if (cachedBuffs->ContainsKey( NetworkId ))
		{
			return cachedBuffs [NetworkId];
		}

		auto buffList = gcnew List<BuffInstance^>();

		auto buffMgr = this->GetPtr()->GetBuffManager();
		if (buffMgr != nullptr)
		{
			auto buffBegin = *buffMgr->GetBegin();
			auto buffEnd = *buffMgr->GetEnd();

			if (buffBegin != nullptr && buffEnd != nullptr)
			{
				auto buffSize = (buffEnd - buffBegin) / sizeof( Native::BuffInstance );

				//Native::Console::PrintLn( "Size: %d", buffSize );

				for (uint i = 0; i < buffSize; i++)
				{
					auto buffNode = buffBegin + i;
					auto buffInst = buffNode->buffInst;

					//Native::Console::PrintLn( "BuffNode: %p", buffNode );

					if (buffNode != nullptr
						&& buffInst != nullptr)
					{
						if (buffInst->IsValid() && buffInst->IsActive())
						{
							//Native::Console::PrintLn( "Buff: %s - Start/End: %g %g", buffInst->GetScriptBaseBuff()->GetName(), *buffInst->GetStartTime(), *buffInst->GetEndTime() );
							buffList->Add( gcnew BuffInstance( buffInst, this->m_networkId, i ) );
						}
					}
				}
			}
		}

		cachedBuffs->Add( NetworkId, buffList );
		return buffList;
	}

	bool Obj_AI_Base::HasBuffOfType( BuffType type )
	{
		for each(auto buff in this->Buffs)
		{
			if (buff->IsActive && static_cast<BuffType>( buff->Type ) == type)
			{
				return true;
			}
		}

		return false;
	}

	bool Obj_AI_Base::HasBuff( System::String^ buffName )
	{
		if (!String::IsNullOrEmpty( buffName ))
		{
			auto buffMgr = this->GetPtr()->GetBuffManager();
			auto buffPtr = Marshal::StringToHGlobalAnsi( buffName );

			if (buffMgr != nullptr)
			{
				auto buffBegin = *buffMgr->GetBegin();
				auto buffEnd = *buffMgr->GetEnd();
				auto size = (buffEnd - buffBegin) / sizeof( Native::BuffInstance );

				if (buffBegin != buffEnd)
				{
					for (uint i = 0; i < size; i++)
					{
						auto buffNode = buffBegin + i;
						auto buffInst = buffNode->buffInst;

						if (buffNode != nullptr && buffInst != nullptr)
						{
							if (buffInst->IsValid() && buffInst->IsActive())
							{
								auto scriptBuff = buffInst->GetScriptBaseBuff();

								if (_strcmpi( scriptBuff->GetName(), (char*) buffPtr.ToPointer() ) == 0 || _strcmpi( scriptBuff->GetVirtual()->GetDisplayName(), (char*) buffPtr.ToPointer() ) == 0)
								{
									Marshal::FreeHGlobal( buffPtr );
									return true;
								}
							}
						}
					}
				}
			}
			Marshal::FreeHGlobal( buffPtr );
		}
		return false;
	}

	BuffInstance^ Obj_AI_Base::GetBuff( System::String^ buffName )
	{
		if (!String::IsNullOrEmpty( buffName ))
		{
			auto buffMgr = this->GetPtr()->GetBuffManager();
			auto buffPtr = Marshal::StringToHGlobalAnsi( buffName );

			if (buffMgr != nullptr)
			{
				auto buffBegin = *buffMgr->GetBegin();
				auto buffEnd = *buffMgr->GetEnd();

				if (buffBegin != nullptr && buffEnd != nullptr)
				{
					for (uint i = 0; i < (buffEnd - buffBegin) / sizeof( Native::BuffInstance ); i++)
					{
						auto buffNode = buffBegin + i;
						auto buffInst = buffNode->buffInst;

						if (buffNode != nullptr && buffInst != nullptr)
						{
							if (buffInst->IsValid() && buffInst->IsActive())
							{
								auto scriptBuff = buffInst->GetScriptBaseBuff();

								if (_strcmpi( scriptBuff->GetName(), (char*) buffPtr.ToPointer() ) == 0 || _strcmpi( scriptBuff->GetVirtual()->GetDisplayName(), (char*) buffPtr.ToPointer() ) == 0)
								{
									Marshal::FreeHGlobal( buffPtr );
									return gcnew BuffInstance( buffInst, this->m_networkId, *buffInst->GetIndex() );
								}
							}
						}
					}
				}
			}

			Marshal::FreeHGlobal( buffPtr );
		}

		return nullptr;
	}

	Vector2 Obj_AI_Base::HPBarPosition::get()
	{
		auto ptr = this->GetPtr();
		if (ptr != nullptr)
		{
			auto infoComponent = *ptr->GetInfoComponent();
			if (infoComponent != nullptr)
			{
				auto vecOut = infoComponent->GetHPBarPosition();
				return Vector2( vecOut.GetX(), vecOut.GetY() );
			}
		}

		return Vector2::Zero;
	}

	float Obj_AI_Base::HPBarXOffset::get()
	{
		auto ptr = this->GetPtr();
		if (ptr != nullptr)
		{
			auto infoComponent = *ptr->GetInfoComponent();
			if (infoComponent != nullptr)
			{
				auto healthbar = *infoComponent->GetHealthbar();
				if (healthbar != nullptr)
				{
					return *healthbar->GetXOffset();
				}
			}
		}

		return 0;
	}

	void Obj_AI_Base::HPBarXOffset::set( float value )
	{
		auto self = this->GetPtr();
		if (self != nullptr)
		{
			auto infoComponent = *this->GetPtr()->GetInfoComponent();
			if (infoComponent != nullptr)
			{
				auto healthbar = *infoComponent->GetHealthbar();
				if (healthbar != nullptr)
				{
					healthbar->SetXOffset( value );
				}
			}
		}
	}

	float Obj_AI_Base::HPBarYOffset::get()
	{
		auto ptr = this->GetPtr();
		if (ptr != nullptr)
		{
			auto infoComponent = *ptr->GetInfoComponent();
			if (infoComponent != nullptr)
			{
				auto healthbar = *infoComponent->GetHealthbar();
				if (healthbar != nullptr)
				{
					return *healthbar->GetYOffset();
				}
			}
		}

		return 0;
	}

	void Obj_AI_Base::HPBarYOffset::set( float value )
	{
		auto self = this->GetPtr();
		if (self != nullptr)
		{
			auto infoComponent = *this->GetPtr()->GetInfoComponent();
			if (infoComponent != nullptr)
			{
				auto healthbar = *infoComponent->GetHealthbar();
				if (healthbar != nullptr)
				{
					healthbar->SetYOffset( value );
				}
			}
		}
	}

	Vector3 Obj_AI_Base::InfoComponentBasePosition::get()
	{
		auto ptr = this->GetPtr();
		if (ptr != nullptr)
		{
			auto infoComponent = *ptr->GetInfoComponent();
			if (infoComponent != nullptr)
			{
				auto vecOut = infoComponent->GetBaseDrawPosition();
				return Vector3( vecOut->GetX(), vecOut->GetZ(), vecOut->GetY() );
			}
		}

		return Vector3::Zero;
	}

	array<InventorySlot^>^ Obj_AI_Base::InventoryItems::get()
	{
		auto list = gcnew List<InventorySlot^>();
		auto ptr = this->GetPtr();
		if (ptr != nullptr)
		{
			auto inventory = ptr->GetInventory();
			if (inventory != nullptr)
			{
				for (int slot = 0; slot < 39; slot++)
				{
					auto inventorySlot = inventory->GetInventorySlot( slot );
					if (inventorySlot != nullptr && inventorySlot->GetItemNode() != nullptr)
					{
						list->Add( gcnew InventorySlot( m_networkId, slot ) );
					}
				}
			}
		}

		return list->ToArray();
	}

	//CharacterStates

	GameObjectCharacterState Obj_AI_Base::CharacterState::get()
	{
		return static_cast<GameObjectCharacterState>(*this->GetPtr()->GetCharacterActionState());
	}

	bool Obj_AI_Base::IsCallForHelpSuppresser::get()
	{
		return *(BYTE*) ((int)this->CharacterState + 58) != 0;
	}

	bool Obj_AI_Base::IsSuppressCallForHelp::get()
	{
		return *(BYTE*) ((int)this->CharacterState + 57) != 0;
	}

	bool Obj_AI_Base::IsIgnoreCallForHelp::get()
	{
		return *(BYTE*) ((int)this->CharacterState + 56) != 0;
	}

	bool Obj_AI_Base::IsForceRenderParticles::get()
	{
		return (*((DWORD *) (int)this->CharacterState + 4) >> 17) & 1;
	}

	bool Obj_AI_Base::IsFleeing::get()
	{
		return (*((DWORD *) (int)this->CharacterState + 4) >> 8) & 1;
	}

	bool Obj_AI_Base::IsNoRender::get()
	{
		return (*((DWORD *) (int)this->CharacterState + 8) >> 8) >> 8 & 1;
	}

	bool Obj_AI_Base::IsGhosted::get()
	{
		return (*((DWORD *) (int)this->CharacterState + 4) >> 12) & 1;
	}

	bool Obj_AI_Base::IsNearSight::get()
	{
		return (*((DWORD *) (int)this->CharacterState + 4) >> 11) & 1;
	}

	bool Obj_AI_Base::IsAsleep::get()
	{
		return (*((DWORD *) (int)this->CharacterState + 4) >> 10) & 1;
	}

	bool Obj_AI_Base::IsFeared::get()
	{
		return (*((DWORD *) (int)this->CharacterState + 4) >> 7) & 1;
	}

	bool Obj_AI_Base::IsCharmed::get()
	{
		return (*((DWORD *) (int)this->CharacterState + 4) >> 15) & 1;
	}

	bool Obj_AI_Base::IsTaunted::get()
	{
		return (*((DWORD *) (int)this->CharacterState + 4) >> 6) & 1;
	}

	bool Obj_AI_Base::IsRevealSpecificUnit::get()
	{
		return (*((DWORD *) (int)this->CharacterState + 4) >> 5) & 1;
	}

	bool Obj_AI_Base::IsStealthed::get()
	{
		return (*((DWORD *) (int)this->CharacterState + 4) >> 4) & 1;
	}

	bool Obj_AI_Base::CanMove::get()
	{
		auto actionState = (GameObjectCharacterState) *this->GetPtr()->GetCharacterActionState();
		return actionState.HasFlag( GameObjectCharacterState::CanMove ) 
			|| actionState.HasFlag( GameObjectCharacterState::Immovable );
	}

	bool Obj_AI_Base::CanCast::get()
	{
		auto actionState = (GameObjectCharacterState) *this->GetPtr()->GetCharacterActionState();
		return !actionState.HasFlag( GameObjectCharacterState::Surpressed ) 
			|| !actionState.HasFlag( GameObjectCharacterState::CanCast );
	}

	bool Obj_AI_Base::CanAttack::get()
	{
		auto actionState = (GameObjectCharacterState) *this->GetPtr()->GetCharacterActionState();
		return actionState.HasFlag( GameObjectCharacterState::CanAttack );
	}

	bool Obj_AI_Base::IsRooted::get()
	{
		auto actionState = (GameObjectCharacterState) *this->GetPtr()->GetCharacterActionState();
		return actionState.HasFlag( GameObjectCharacterState::Immovable )
			&& !actionState.HasFlag( GameObjectCharacterState::CanMove )
			&& !actionState.HasFlag( GameObjectCharacterState::CanAttack );
	}

	bool Obj_AI_Base::IsStunned::get()
	{
		auto actionState = (GameObjectCharacterState) *this->GetPtr()->GetCharacterActionState();
		return actionState.HasFlag( GameObjectCharacterState::Immovable )
			&& !actionState.HasFlag( GameObjectCharacterState::CanMove )
			&& !actionState.HasFlag( GameObjectCharacterState::CanAttack )
			&& !actionState.HasFlag( GameObjectCharacterState::CanCast );
	}

	bool Obj_AI_Base::IsPacified::get()
	{
		auto actionState = (GameObjectCharacterState) *this->GetPtr()->GetCharacterActionState();
		return actionState.HasFlag( GameObjectCharacterState::CanCast )
			&& !actionState.HasFlag( GameObjectCharacterState::CanCast );
	}

	GameObject^ Obj_AI_Base::Pet::get()
	{
		return nullptr;
		/*
		auto pet = Native::ObjectManager::GetUnitByIndex( this->AI_LastPetSpawnedID );
		if (pet != nullptr)
		{
			return gcnew Obj_AI_Base( *pet->GetIndex(), *pet->GetNetworkId(), pet );
		}
		return nullptr;
		*/
	}

	array<Vector3>^ Obj_AI_Base::GetPath( Vector3 end )
	{
		return this->GetPath( end, false );
	}

	array<Vector3>^ Obj_AI_Base::GetPath( Vector3 end, bool smoothPath )
	{
		auto vecList = gcnew List<Vector3>();

		auto ptr = this->GetPtr();
		if (ptr != nullptr)
		{
			auto AIManager = *ptr->GetAIManager_Client();
			if (AIManager != nullptr)
			{
				auto actor = AIManager->GetActor();
				if (actor != nullptr)
				{
					auto startVec = Native::Vector3f( this->Position.X, this->Position.Z, this->Position.Y );
					auto dstVec = Native::Vector3f( end.X, end.Z, end.Y );
					auto navPath = Native::NavigationPath();
					
					if (actor->CreatePath( startVec, dstVec, navPath ))
					{
						if (smoothPath)
						{
							actor->SmoothPath( &navPath );
						}
						
						auto begin = *navPath.GetBegin();
						auto end = navPath.GetEnd();

						while (begin != *end)
						{
							vecList->Add( Vector3( begin->GetX(), begin->GetZ(), begin->GetY() ) );
							begin += 12 / sizeof( Native::Vector3f );
						}
					}
				}
			}
		}

		return vecList->ToArray();
	}

	array<Vector3>^ Obj_AI_Base::GetPath( Vector3 start, Vector3 end )
	{
		return this->GetPath( start, end, false );
	}

	array<Vector3>^ Obj_AI_Base::GetPath( Vector3 start, Vector3 end, bool smoothPath )
	{
		auto vecList = gcnew List<Vector3>();

		auto ptr = this->GetPtr();
		if (ptr != nullptr)
		{
			auto AIManager = *ptr->GetAIManager_Client();
			if (AIManager != nullptr)
			{
				auto actor = AIManager->GetActor();
				if (actor != nullptr)
				{
					auto startVec = Native::Vector3f( start.X, start.Z, start.Y );
					auto dstVec = Native::Vector3f( end.X, end.Z, end.Y );
					auto navPath = Native::NavigationPath();

					if (actor->CreatePath( startVec, dstVec, navPath ))
					{
						if (smoothPath)
						{
							actor->SmoothPath( &navPath );
						}

						auto begin = *navPath.GetBegin();
						auto end = navPath.GetEnd();

						while (begin != *end)
						{
							vecList->Add( Vector3( begin->GetX(), begin->GetZ(), begin->GetY() ) );
							begin += 12 / sizeof( Native::Vector3f );
						}
					}
				}
			}
		}

		return vecList->ToArray();
	}

	array<Vector3>^ Obj_AI_Base::Path::get()
	{
		auto vecList = gcnew List<Vector3>();

		auto ptr = this->GetPtr();
		if (ptr != nullptr)
		{
			auto AIManager = *ptr->GetAIManager_Client();
			if (AIManager != nullptr)
			{
				auto actor = AIManager->GetActor();
				if (actor != nullptr)
				{
					auto navPath = AIManager->GetNavPath();

					if (navPath != nullptr)
					{
						auto begin = *navPath->GetBegin();
						auto end = navPath->GetEnd();

						while (begin != *end)
						{
							vecList->Add( Vector3( begin->GetX(), begin->GetZ(), begin->GetY() ) );
							begin += 12 / sizeof( Native::Vector3f );
						}
					}
				}
			}
		}

		return vecList->ToArray();
	}

	bool Obj_AI_Base::IsMoving::get()
	{
		return Path->Length >= 2;
		/*
		auto ptr = this->GetPtr();
		if (ptr != nullptr)
		{
			auto AIManager = *ptr->GetAIManager_Client();
			if (AIManager != nullptr)
			{
				auto actor = AIManager->GetActor();
				if (actor != nullptr)
				{
					return actor->GetHasNavPath();
				}
			}
		}
		return false;*/
	}

	void Obj_AI_Base::SetSkinId( int skinId )
	{
		auto ptr = this->GetPtr();
		if (ptr != nullptr)
		{
			auto characterStack = ptr->GetCharacterDataStack();
			if (characterStack != nullptr)
			{
				characterStack->SetBaseSkinId( skinId );
			}
		}
	}

	bool Obj_AI_Base::SetModel( String^ model )
	{
		auto ptr = this->GetPtr();
		if (ptr != nullptr)
		{
			auto characterStack = ptr->GetCharacterDataStack();
			if (characterStack != nullptr)
			{
				return characterStack->SetModel( static_cast<char*> (Marshal::StringToHGlobalAnsi( model ).ToPointer()) );
			}
		}
		return false;
	}

	bool Obj_AI_Base::SetSkin( String^ model, int skinId )
	{
		this->SetSkinId( skinId );
		return this->SetModel( model );
	}

	SpellData^ Obj_AI_Base::BasicAttack::get()
	{
		auto ptr = this->GetPtr();
		if (ptr != nullptr)
		{
			auto sdata = ptr->GetBasicAttack();
			if (sdata != nullptr)
			{
				return gcnew SpellData( sdata );
			}
		}
		return nullptr;
	}

	float Obj_AI_Base::TotalAttackDamage::get()
	{
		return this->FlatPhysicalDamageMod + this->BaseAttackDamage;
	}

	float Obj_AI_Base::TotalMagicalDamage::get()
	{
		return this->FlatMagicDamageMod + this->BaseAbilityDamage;
	}

	float Obj_AI_Base::DeathDuration::get()
	{
		return 0;
	}

	/*bool Obj_AI_Base::IssueOrder( GameObjectOrder order, GameObject^ targetUnit )
	{
		return this->IssueOrder( order, targetUnit, true );
	}

	bool Obj_AI_Base::IssueOrder( GameObjectOrder order, GameObject^ targetUnit, bool triggerEvent )
	{
		if (targetUnit != nullptr)
		{
			auto nativeObj = Native::ObjectManager::GetUnitByNetworkId( targetUnit->NetworkId );

			if (nativeObj != nullptr)
			{
				return GetPtr()->IssueOrder( &Native::Vector3f( targetUnit->Position.X, targetUnit->Position.Z, targetUnit->Position.Y ), nativeObj, static_cast<Native::GameObjectOrder>(order), triggerEvent );
			}
		}

		return false;
	}

	bool Obj_AI_Base::IssueOrder( GameObjectOrder order, Vector3 targetPos )
	{
		return this->IssueOrder( order, targetPos, true );
	}

	bool Obj_AI_Base::IssueOrder( GameObjectOrder order, Vector3 targetPos, bool triggerEvent )
	{
		if (order == GameObjectOrder::AttackUnit)
		{
			return false;
		}

		return GetPtr()->IssueOrder( &Native::Vector3f( targetPos.X, targetPos.Z, targetPos.Y ), nullptr, static_cast<Native::GameObjectOrder>(order), triggerEvent );
	}*/
}
