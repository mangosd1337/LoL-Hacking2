#pragma once

namespace EloBuddy
{
	namespace Native
	{
		class LolClient
		{
		private:
			bool m_isLoaded;
		public:
			static LolClient* Get();

			bool Load();
			bool IsLoaded() const;
		};
	}
}