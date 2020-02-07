#include "Stdafx.h"
#include "Hud.h"
#include "Obj_AI_Base.hpp"

namespace EloBuddy
{
	static Hud::Hud()
	{
		ATTACH_DOMAIN();

		ATTACH_EVENT
		(
			HudChangeTarget,
			49, Native::OnHudTargetChange, Native::GameObject*
		);
	}

	void Hud::DomainUnloadEventHandler( System::Object^, System::EventArgs^ )
	{
		DETACH_EVENT
		(
			HudChangeTarget,
			49, Native::OnHudTargetChange, Native::GameObject*
		);
	}

	void Hud::OnHudChangeTargetNative( Native::GameObject* target )
	{
		START_TRACE
			GameObject^ sender = nullptr;

			if (target != nullptr)
				GameObject^ sender = ObjectManager::CreateObjectFromPointer( target );

			auto args = gcnew HudChangeTargetEventArgs( target == nullptr ? nullptr : sender, target == nullptr );

			for each (auto eventHandle in HudChangeTargetHandlers->ToArray())
			{
				START_TRACE
					eventHandle(
						args
					);
				END_TRACE
			}
		END_TRACE
	}

	[Obsolete("This property is currently broken.")]
	GameObject^ Hud::SelectedTarget::get()
	{
		return nullptr;
	}

	void Hud::ShowClick( ClickType type, Vector3 pos)
	{
		auto pwHud = Native::pwHud::GetInstance();
		{
			if (pwHud != nullptr)
			{
				pwHud->ShowClick( &Native::Vector3f(pos.X, pos.Y, pos.Z), static_cast<byte>(type) );
			}
		}
	}

	bool Hud::IsDrawing(HudDrawingType type)
	{
		auto pwHud = Native::pwHud::GetInstance();

		if (pwHud != nullptr)
		{
			return !pwHud->IsDrawing( static_cast<Native::pwHudDrawingType>(type) );
		}

		return true;
	}

	void Hud::EnableDrawing( HudDrawingType type )
	{
		auto pwHud = Native::pwHud::GetInstance();

		if (pwHud != nullptr)
		{
			switch (type)
			{
			case HudDrawingType::Healthbar:
				pwHud->EnableHPBar();
				break;
			case HudDrawingType::Menu:
				pwHud->EnableMenuUI();
				break;
			case HudDrawingType::PwHud:
				pwHud->EnableUI();
				break;
			case HudDrawingType::Minimap:
				pwHud->EnableMinimap();
				break;
			case HudDrawingType::Ping:
				pwHud->EnablePing();
				break;
			}
		}
	}

	void Hud::DisableDrawing( HudDrawingType type )
	{
		auto pwHud = Native::pwHud::GetInstance();

		if (pwHud != nullptr)
		{
			switch (type)
			{
			case HudDrawingType::Healthbar:
				pwHud->DisableHPBar();
				break;
			case HudDrawingType::Menu:
				pwHud->DisableMenuUI();
				break;
			case HudDrawingType::PwHud:
				pwHud->DisableUI();
				break;
			case HudDrawingType::Minimap:
				pwHud->DisableMinimap();
				break;
			case HudDrawingType::Ping:
				pwHud->DisablePing();
				break;
			}
		}
	}
}