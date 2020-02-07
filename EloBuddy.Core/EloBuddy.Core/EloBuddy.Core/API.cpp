#include "stdafx.h"
#include "Bootstrapper.h"
#include "Injector.h"

using namespace EloBuddy::Native;

extern "C"
{
	__declspec(dllexport) bool Bootstrap(BootstrapMemoryLayout* layout)
	{
		Bootstrapper::GetInstance()->SetMemoryLayout(layout);
		return true;
	}

	__declspec(dllexport) bool SafeInjection(int procId)
	{
		auto layout = Bootstrapper::GetInstance()->GetMemoryLayout();

		if (layout != nullptr)
		{
			return Injector::InjectDLL(procId, layout->EloBuddyCoreDllPath) != nullptr;
		}

		Console::PrintLn("BootstrapMemoryLayout* is null.");

		return false;
	}
}