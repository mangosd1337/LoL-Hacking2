#include "stdafx.h"
#include "Humanizer.h"

namespace EloBuddy
{
	namespace Native
	{
		int Humanizer::GetDelay() const
		{
			return rand() % (m_minDelay - m_maxDelay) + m_maxDelay - m_minDelay * 2;
		}

		bool Humanizer::Execute(std::function<bool()> fnc)
		{
			auto tickCount = GetTickCount();

			if (tickCount - m_lastExec >= m_delay)
			{
				m_lastExec = tickCount;
				return fnc();
			}

			return false;
		}

		bool Humanizer::CanExecute()
		{
			auto tickCount = GetTickCount();

			if (tickCount - m_lastExec >= m_delay)
			{
				m_lastExec = tickCount;
				return true;
			}

			return false;
		}

		bool Humanizer::CanExecute(byte hash)
		{
			if (this->m_hash == hash)
			{
				return this->CanExecute();
			}

			this->m_hash = hash;

			return true;
		}
	}
}
