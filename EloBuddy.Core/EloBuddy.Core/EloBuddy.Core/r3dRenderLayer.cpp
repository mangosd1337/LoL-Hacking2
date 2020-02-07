#include "stdafx.h"
#include "r3dRenderLayer.h"
#include "Detour.hpp"
#include "ObjectManager.h"
#include "RiotX3D.h"

namespace EloBuddy
{
	namespace Native
	{
		MAKE_HOOK<convention_type::stdcall_t, r3dTexture*, std::string*, DWORD*, int, int, int> r3dLoadTexture;
		MAKE_HOOK<convention_type::fastcall_t, void, int> CreateWorldObject;

		bool r3dRenderLayer::ApplyHooks()
		{
			r3dLoadTexture.Apply(MAKE_RVA(Offsets::r3dRenderLayer::LoadTexture), [] (std::string* texture, DWORD* sharedMemory, int a2, int a3, int a4) -> r3dTexture*
			{
				auto pr3dText = r3dLoadTexture.CallOriginal(texture, sharedMemory, a2, a3, a4);
				//if (RiotX3D::IsBottingMode())
				//{
				//	auto const blockedTexture = new std::string( "notfound.dds" );
				//	texture = blockedTexture;
				//}
				return pr3dText;
			});

			return r3dLoadTexture.IsApplied();
		}

		r3dTexture* r3dRenderLayer::LoadTexture(std::string* texture)
		{
			auto r3dSharedManager = *reinterpret_cast<DWORD**>(MAKE_RVA(Offsets::r3dRenderLayer::IRedTexturePacket1));
			auto managerShared = static_cast<DWORD*>(r3dSharedManager + 0x25); //0x8c/0x4

			return r3dLoadTexture.CallDetour(texture, managerShared, 6, 0, 1);
		}
	}
}