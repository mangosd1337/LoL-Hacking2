#include "stdafx.h"

namespace EloBuddy
{
	public ref class OnResetArgs : public System::EventArgs
	{
	private:
	public:
		delegate void OnReset(System::EventArgs^);

		OnResetArgs(System::EventArgs^ args) {}
	};
}