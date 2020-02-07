#pragma once
#include "Utils.h"

#define VERIFY_HOOK(HOOK, NAME) if (!HOOK ()) { return false; }

namespace EloBuddy
{
	namespace Native
	{
		class Detour;

		class Core
		{
			HMODULE hModule;
			bool ApplyHooks() const;
		public:
			explicit Core(HMODULE h_module);
			~Core();

			void CreateThreadBootstrapAddons() const;
			void DisplayWelcomeMessage() const;

			HMODULE get_hModule() const;
			void set_hModule(HMODULE h);

			/*
			 * static
			*/
			static int mainModule;

			template <typename T>
			static T GetAddress(DWORD virtualAddr);

			static Detour* get_DetourInstance();
		};

		template <typename T>
		T Core::GetAddress(DWORD virtualAddr)
		{
			return static_cast<T>(mainModule) + virtualAddr - 0x400000;
		}
	}
}

