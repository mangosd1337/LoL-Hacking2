#pragma once
#include "Utils.h"

namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT Experience
		{
		public:
			MAKE_GET( Experience, float, Offsets::ExperienceStruct::NumExperience );
			MAKE_GET( Level, int, Offsets::ExperienceStruct::Level );
			MAKE_GET( SpellTrainingPoints, int, Offsets::ExperienceStruct::SpellTrainingPoints );

			float GetExpToNextLevel()
			{
				auto returnValue = 0.0f;

				if (this != nullptr)
				{
					auto expToNextLevel = MAKE_RVA( Offsets::Experience::XPToNextLevel );

					__asm
					{
						mov ecx, this
						call [expToNextLevel]
						movss returnValue, xmm0
					}
				}

				return returnValue;
			}

			float GetExpToCurrentLevel()
			{
				auto returnValue = 0.0f;

				if (this != nullptr)
				{
					auto expToCurrentLevel = MAKE_RVA( Offsets::Experience::XPToCurrentLevel );

					__asm
					{
						mov ecx, this
						call [expToCurrentLevel]
						movss returnValue, xmm0
					}
				}
				return returnValue;
			}
		};
	}
}