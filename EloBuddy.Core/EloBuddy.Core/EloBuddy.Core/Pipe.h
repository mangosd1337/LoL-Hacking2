#include <Windows.h>

namespace EloBuddy
{
	namespace Native
	{
		class NetworkPipe
		{
		public:
			static void Listen();
			static DWORD WINAPI ClientThread(LPVOID);
		};
	}
}