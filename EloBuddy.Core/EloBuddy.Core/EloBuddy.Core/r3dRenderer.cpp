#include "stdafx.h"
#include "r3dRenderer.h"
#include "RiotX3D.h"
#include "r3dRenderLayer.h"

namespace EloBuddy
{
	namespace Native
	{
		MAKE_HOOK<convention_type::cdecl_t, int, float, float, float, float, float, int> OnDrawLine2D;

		r3dRenderer* r3dRenderer::GetInstance()
		{
			return *reinterpret_cast<r3dRenderer**>(MAKE_RVA(Offsets::r3dRenderer::r3dRendererInstance));
		}

		LPDIRECT3DDEVICE9 r3dRenderer::GetDevice()
		{
			return RiotX3D::GetDirect3DDevice();
		}

		void __stdcall r3dRenderer::DrawCircularRangeIndicator(Vector3f* dstVector, float radius, int color, r3dTexture* texture)
		{
			auto r3dDrawCircularRangeIndicator = MAKE_RVA(Offsets::r3dRenderer::DrawCircularRangeIndicator);
			auto floatOne = 1.0f;
			auto floatZero = 0.0f;

			auto pColor = &color;
			auto pDstVector = new Vector3f(dstVector->GetX(), dstVector->GetZ(), dstVector->GetY());

			int espValue;

			__asm
			{
				mov espValue, esp

				push floatOne
				push floatOne
				push texture
				movss xmm1, radius
				mov ecx, pDstVector
				mov edx, pColor

				call [r3dDrawCircularRangeIndicator]

				mov esp, espValue
			}
		}

		void r3dRenderer::DrawTexture(std::string texture, Vector3f* dstVec, float radius, int color)
		{
			auto tex = r3dRenderLayer::LoadTexture(&texture);
			if (tex != nullptr)
			{
				DrawCircularRangeIndicator(dstVec, radius, color, tex);
			}
		}

		void r3dRenderer::DrawTexture(r3dTexture* texture, Vector3f* dstVec, float radius, int color)
		{
			DrawCircularRangeIndicator(dstVec, radius, color, texture);
		}

		void r3dRenderer::r3dScreenTo3D(float x, float y, Vector3f* vecOut, Vector3f* vecOut2)
		{
			__try
			{
				auto r3dScreenTo3D = MAKE_RVA(Offsets::r3dRenderer::r3dScreenTo3D);

				__asm
				{
					movss xmm0, x
					movss xmm1, y
					//push x
					mov ecx, vecOut2
					mov edx, vecOut

					call [r3dScreenTo3D]
				}
			}
			__except (1) {}
		}

		bool r3dRenderer::r3dProjectToScreen(Vector3f* vecIn, Vector3f* vecOut)
		{
			vecIn = &vecIn->SwitchYZ();

			return reinterpret_cast<bool(__fastcall*)(Vector3f*, Vector3f*)>
				MAKE_RVA(Offsets::r3dRenderer::r3dProjectToScreen)
				(vecIn, vecOut);
		}
	}
}
