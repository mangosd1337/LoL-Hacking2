#include "stdafx.h"
#include "Drawing.h"

#include "Hacks.h"

namespace EloBuddy
{
	namespace Native
	{
		class D3D9Main;

		ID3DXLine* Drawing::g_pLine = nullptr;
		ID3DXFont* Drawing::g_pFont = nullptr;

		Drawing* Drawing::GetInstance()
		{
			static auto* instance = new Drawing();
			return instance;
		}

		void Drawing::DrawFontText(float x, float y, D3DCOLOR color, char* text, int size)
		{
			if (!g_pFont)
			{
				D3DXCreateFontA(r3dRenderer::GetInstance()->GetDevice(), 17, 0, 350, 0, 0, DEFAULT_CHARSET, OUT_TT_ONLY_PRECIS, PROOF_QUALITY, DEFAULT_PITCH | FW_BOLD, "Arial", &g_pFont);
			}

			RECT pos;
			pos.left = x;
			pos.top = y;

			g_pFont->DrawTextA(nullptr, text, -1, &pos, DT_LEFT | DT_NOCLIP, color);
		}

		SIZE Drawing::GetTextEntent(char* text, int fontsize)
		{
			if (!g_pFont)
			{
				D3DXCreateFontA(r3dRenderer::GetInstance()->GetDevice(), 17, 0, 350, 0, 0, DEFAULT_CHARSET, OUT_TT_ONLY_PRECIS, PROOF_QUALITY, DEFAULT_PITCH | FW_BOLD, "Arial", &g_pFont);
			}

			SIZE size = { };
			RECT rect = { 0, 0, 0, 0 };

			if (g_pFont)
			{
				g_pFont->DrawTextA(nullptr, text, -1, &rect, DT_CALCRECT, D3DCOLOR_XRGB(0, 0, 0));

				size.cx = rect.right;
				size.cy = rect.left;
			}

			return size;
		}

		void Drawing::DrawLogo()
		{
			static auto r3dRenderer = r3dRenderer::GetInstance();
			static auto pDrawing = GetInstance();

			if (r3dRenderer != nullptr && pDrawing != nullptr)
			{
				auto width = *r3dRenderer::GetInstance()->GetClientWidth() / 2 - 30;
				pDrawing->DrawFontText(width, 0, D3DCOLOR_XRGB(0, 0xff, 0), "EloBuddy", 15);
			}
		}

		void Drawing::DrawLine(float startX, float startY, float endX, float endY, float thickness, D3DCOLOR color)
		{
			if (!g_pLine)
			{
				D3DXCreateLine(r3dRenderer::GetInstance()->GetDevice(), &g_pLine);
			}

			g_pLine->SetWidth(thickness);

			D3DXVECTOR2 v2Line [2];
			v2Line [0].x = startX;
			v2Line [0].y = startY;
			v2Line [1].x = endX;
			v2Line [1].y = endY;

			g_pLine->Begin();
			g_pLine->Draw(v2Line, 2, color);
			g_pLine->End();
		}
	}
}