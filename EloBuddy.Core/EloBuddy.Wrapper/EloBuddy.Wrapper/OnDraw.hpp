#include "stdafx.h"

namespace EloBuddy
{
	public ref class OnDrawArgs : public System::EventArgs
	{
	private:
	public:
		delegate void OnDraw(System::EventArgs^);

		OnDrawArgs(System::EventArgs^ args) {}
	};
}