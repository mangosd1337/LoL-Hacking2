#include "stdafx.h"
#include "Hacks.h"

namespace EloBuddy
{
	namespace Native
	{
#ifdef _DEBUG_BUILD
		bool Hacks::m_console = true;
#else
		bool Hacks::m_console = false;
#endif
		bool Hacks::m_pwConsole = true;
		bool Hacks::m_drawWatermark = true;
		bool Hacks::m_zoom = false;
		bool Hacks::m_antiafk = false;
		bool Hacks::m_streamingMode = false;
		bool Hacks::m_setMovementHack = false;
		bool Hacks::m_drawTurretRange = false;

		bool Hacks::GetConsole()
		{
			return m_console;
		}

		void Hacks::SetConsole(bool value)
		{
			m_console = value;

			if (value)
			{
				Console::Show();
			}

			if (!value)
			{
#ifndef _DEBUG_BUILD
				Console::Hide();
#endif
			}
		}
		
		bool Hacks::GetAntiAFK()
		{
			return m_antiafk;
		}

		void Hacks::SetAntiAFK(bool value)
		{
			m_antiafk = value;
		}

		bool Hacks::GetZoomHack()
		{
			return m_zoom;
		}

		void Hacks::SetZoomHack(bool value)
		{
			m_zoom = value;
		}

		void Hacks::SetPwConsole(bool value)
		{
			m_pwConsole = value;
		}

		bool Hacks::GetDrawWatermark()
		{
			return true;
			//return m_drawWatermark;
		}

		void Hacks::SetDrawWatermark(bool value)
		{
			m_drawWatermark = value;
		}

		bool Hacks::GetIsStreamingMode()
		{
			return m_streamingMode;
		}

		void Hacks::SetStreamingMode(bool value)
		{
			m_streamingMode = value;
		}

		bool Hacks::GetPwConsole()
		{
			return m_pwConsole;
		}

		bool Hacks::GetMovementHack()
		{
			return m_setMovementHack;
		}

		void Hacks::SetMovementHack(bool value)
		{
		}

		bool Hacks::GetTowerRanges()
		{
			return m_drawTurretRange;
		}

		void Hacks::SetTowerRanges(bool value)
		{
			m_drawTurretRange = value;
		}
	}
}