#include "stdafx.h"

namespace EloBuddy
{
	public ref class OnEndSceneArgs : public System::EventArgs
	{
	private:
	public:
		delegate void OnEndScene(System::EventArgs^);

		OnEndSceneArgs(System::EventArgs^ args) {}
	};
}