#pragma once
#include "Utils.h"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT Hacks
		{
		private:
			static bool m_console;
			static bool m_antiafk;
			static bool m_zoom;
			static bool m_pwConsole;
			static bool m_drawWatermark;
			static bool m_streamingMode;
			static bool m_setMovementHack;
			static bool m_drawTurretRange;
		public:
			static bool GetConsole();
			static void SetConsole(bool value);

			static bool GetAntiAFK();
			static void SetAntiAFK(bool value);

			static bool GetZoomHack();
			static void SetZoomHack(bool value);

			static bool GetPwConsole();
			static void SetPwConsole(bool value);

			static bool GetDrawWatermark();
			static void SetDrawWatermark(bool value);

			static bool GetIsStreamingMode();
			static void SetStreamingMode(bool value);

			static bool GetMovementHack();
			static void SetMovementHack(bool value);

			static bool GetTowerRanges();
			static void SetTowerRanges(bool value);
		};
	}
}