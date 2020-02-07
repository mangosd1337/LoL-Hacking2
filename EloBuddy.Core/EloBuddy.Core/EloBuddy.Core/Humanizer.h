#pragma once
#include <functional>

namespace EloBuddy
{
	namespace Native
	{
		class Humanizer
		{
		private:
			int m_minDelay;
			int m_maxDelay;
			int m_delay;
			int m_lastExec;
			byte m_hash;
		public:
			Humanizer(int minDelay, int maxDelay) : m_minDelay(minDelay), m_maxDelay(maxDelay), m_lastExec(0), m_hash(0)
			{
				this->m_delay = this->GetDelay();
			}

			int GetDelay() const;
			bool Execute(std::function<bool()> fnc);
			bool CanExecute();
			bool CanExecute(byte hash);
		};
	}
}