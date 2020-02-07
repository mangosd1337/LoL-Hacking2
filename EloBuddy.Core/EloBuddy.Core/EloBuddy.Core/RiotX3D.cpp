#include "stdafx.h"
#include "RiotX3D.h"
#include "Hacks.h"
#include "ObjectManager.h"
#include "Game.h"

namespace EloBuddy
{
	namespace Native
	{
		LPDIRECT3DDEVICE9 RiotX3D::Direct3DDevice9 = nullptr;

		MAKE_HOOK<convention_type::fastcall_t, void, LPDIRECT3DDEVICE9, D3DPRESENT_PARAMETERS*> RiotX3D_Reset;
		MAKE_HOOK<convention_type::cdecl_t, void, void*> RiotX3D_BeginScene;
		MAKE_HOOK<convention_type::cdecl_t, int, void*> RiotX3D_Present;
		MAKE_HOOK<convention_type::cdecl_t, void, void*> RiotX3D_EndScene;
		MAKE_HOOK<convention_type::cdecl_t, void, void*> RiotX3D_SetRenderTarget;
		MAKE_HOOK<convention_type::fastcall_t, void, void*> Hud_OnDraw;

		bool RiotX3D::ApplyHooks()
		{
			RiotX3D_Reset.Apply(MAKE_RVA(Offsets::RiotX3D::Reset), [] (LPDIRECT3DDEVICE9 _pDevice, D3DPRESENT_PARAMETERS* param) -> void
			{
				EventHandler<9, OnDrawingPreReset>::GetInstance()->Trigger();

				if (Drawing::g_pFont)
				{
					Drawing::g_pFont->OnLostDevice();
				}

				if (Drawing::g_pLine)
				{
					Drawing::g_pLine->OnLostDevice();
				}

				RiotX3D_Reset.CallOriginal(_pDevice, param);

				if (Drawing::g_pFont)
				{
					Drawing::g_pFont->OnResetDevice();
				}

				if (Drawing::g_pLine)
				{
					Drawing::g_pLine->OnResetDevice();
				}

				EventHandler<8, OnDrawingPostReset>::GetInstance()->Trigger();
			});

			RiotX3D_BeginScene.Apply(MAKE_RVA(Offsets::RiotX3D::BeginScene), [] (void* device) -> void
			{
				__asm pushad;
					EventHandler<5, OnDrawingBeginScene>::GetInstance()->Trigger();
					EventHandler<45, OnDrawingFlushEndScene>::GetInstance()->Trigger();
				__asm popad;

				RiotX3D_BeginScene.CallOriginal(device);
			});

			RiotX3D_Present.Apply(MAKE_RVA(Offsets::RiotX3D::Present), [] (void* device) -> int
			{
				__asm pushad;
					EventHandler<10, OnDrawingPresent>::GetInstance()->Trigger();
				__asm popad;

				return RiotX3D_Present.CallOriginal(device);
			});

			RiotX3D_EndScene.Apply(MAKE_RVA(Offsets::RiotX3D::EndScene), [] (void* device) -> void
			{
				IDirect3DDevice9* pDevice = nullptr;
				__asm mov pDevice, ecx;

				__asm pushad;
				if (pDevice != nullptr || pDevice != Direct3DDevice9)
				{
					Direct3DDevice9 = pDevice;
				}

				SetRenderStates(pDevice);
				EventHandler<7, OnDrawingEndScene>::GetInstance()->Trigger();

				if (Hacks::GetDrawWatermark() && !Hacks::GetIsStreamingMode())
				{
					Drawing::DrawLogo();
				}

				EventHandler<45, OnDrawingFlushEndScene>::GetInstance()->Trigger();
				FinishRenderStates(pDevice);

				__asm popad;
				__asm mov ecx, pDevice;

				RiotX3D_EndScene.CallOriginal(device);
			});

			RiotX3D_SetRenderTarget.Apply(MAKE_RVA(Offsets::RiotX3D::SetRenderTarget), [] (void* device) -> void
			{
				__asm pushad;
					EventHandler<11, OnDrawingSetRenderTarget>::GetInstance()->Trigger();
				__asm popad;

				RiotX3D_SetRenderTarget.CallOriginal(device);
			});

			Hud_OnDraw.Apply(MAKE_RVA(Offsets::pwHud::pwHud_OnDraw), [] (void* useless)-> void
			{
				if (Direct3DDevice9 != nullptr)
				{
					Direct3DDevice9->SetRenderState(D3DRS_CULLMODE, D3DCULL_NONE);
					Direct3DDevice9->SetRenderState(D3DRS_ALPHATESTENABLE, TRUE);
					{
						EventHandler<6, OnDrawingDraw>::GetInstance()->Trigger();
						EventHandler<45, OnDrawingFlushEndScene>::GetInstance()->Trigger();
					}
					Direct3DDevice9->SetRenderState(D3DRS_CULLMODE, D3DCULL_CCW);
					Direct3DDevice9->SetRenderState(D3DRS_ALPHATESTENABLE, FALSE);
				}

				Hud_OnDraw.CallOriginal(useless);
			});

			return RiotX3D_Reset.IsApplied()
				&& RiotX3D_BeginScene.IsApplied()
				&& RiotX3D_Present.IsApplied()
				&& RiotX3D_EndScene.IsApplied()
				&& RiotX3D_SetRenderTarget.IsApplied()
				&& Hud_OnDraw.IsApplied();
		}

		void RiotX3D::SetRenderStates(IDirect3DDevice9* device)
		{
			if (device != nullptr)
			{
				device->SetRenderState(D3DRS_ALPHATESTENABLE, TRUE);
				device->SetRenderState(D3DRS_ALPHABLENDENABLE, TRUE);
				device->SetRenderState(D3DRS_ALPHAFUNC, D3DCMP_GREATEREQUAL);
				device->SetRenderState(D3DRS_ALPHAREF, static_cast<DWORD>(8));
				device->SetRenderState(D3DRS_CULLMODE, D3DCULL_NONE);
			}
		}

		void RiotX3D::FinishRenderStates(IDirect3DDevice9* device)
		{
			if (device != nullptr)
			{
				device->SetRenderState(D3DRS_ALPHATESTENABLE, FALSE);
				device->SetRenderState(D3DRS_CULLMODE, D3DCULL_CCW);
				device->SetRenderState(D3DRS_ALPHAFUNC, D3DCMP_LESSEQUAL);
			}
		}

		IDirect3DDevice9* RiotX3D::GetDirect3DDevice()
		{
			return Direct3DDevice9;
		}
	}
}