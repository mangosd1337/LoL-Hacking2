#include "stdafx.h"

namespace EloBuddy
{
	public ref class OnPresentArgs : public System::EventArgs
	{
	private:
	public:
		delegate void OnPresent(System::EventArgs^);

		OnPresentArgs(System::EventArgs^ args) {}
	};
}