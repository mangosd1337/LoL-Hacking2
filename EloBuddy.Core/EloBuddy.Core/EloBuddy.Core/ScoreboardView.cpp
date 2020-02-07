#include "stdafx.h"
#include "ScoreboardView.h"
#include "AIHeroClient.h"
#include "Hacks.h"

namespace EloBuddy
{
	namespace Native
	{
		MAKE_HOOK<convention_type::stdcall_t, int, GameObjectTeam, const char*, const char*,  int*, int*, bool, bool, int*, int*> ScoreboardView_AddPlayer;

		bool ScoreboardView::ApplyHooks()
		{
			/*ScoreboardView_AddPlayer.Apply( MAKE_RVA( Offsets::ScoreboardView::AddPlayer ), [](
				GameObjectTeam team, const char* playerName, const char* champName, int* summonerIcon,
				int* profileIcon, bool unkn2, bool unkn3, int* r3dUnknTex, int* r3dUnknTex2 ) -> int
			{
				__asm pushad;
				static auto bNamesPatched = false;

				if (!bNamesPatched && Hacks::GetIsStreamingMode())
				{
					for (auto i = 0; i < 10; i++)
					{
						auto hero = ScoreboardArray() [i];
						if (hero == nullptr || hero == ScoreboardArrayLast())
							break;

						//hero->GetName() = "EloBuddy";
					}

					bNamesPatched = true;
				}
				__asm popad;

				return ScoreboardView_AddPlayer.CallOriginal( team, "Player", champName, summonerIcon, profileIcon, unkn2, unkn3, r3dUnknTex, r3dUnknTex2 );
			} );

			return ScoreboardView_AddPlayer.IsApplied();*/
			return true;
		}

		AIHeroClient** ScoreboardView::ScoreboardArray()
		{
			return nullptr;
			//return *reinterpret_cast<AIHeroClient***>(MAKE_RVA( Offsets::ScoreboardView::PlayerList ));
		}

		AIHeroClient* ScoreboardView::ScoreboardArrayLast()
		{
			return nullptr;
			//return **reinterpret_cast<AIHeroClient***>(MAKE_RVA( Offsets::ScoreboardView::PlayerListSize ));
		}
	}
}