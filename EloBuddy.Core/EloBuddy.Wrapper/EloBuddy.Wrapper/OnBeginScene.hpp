#include "stdafx.h"

namespace EloBuddy
{
	public ref class OnBeginSceneArgs : public System::EventArgs
	{
	private:
	public:
		delegate void OnBeginScene(System::EventArgs^);

		OnBeginSceneArgs(System::EventArgs^ args) {}
	};
}