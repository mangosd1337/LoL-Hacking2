#pragma once

namespace EloBuddy
{
	namespace Native
	{
		class TeemoClient
		{
		private:
			bool m_isLoaded;
		public:
			static TeemoClient* GetInstance();
			
			bool Load() const;
			bool IsLoaded() const;
		};
	}
}