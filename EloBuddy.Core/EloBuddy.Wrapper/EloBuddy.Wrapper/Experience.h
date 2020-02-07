#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/Experience.h"
#include "../../EloBuddy.Core/EloBuddy.Core/AIHeroClient.h"
#include "Macros.hpp"

using namespace System;

namespace EloBuddy
{
	public ref class Experience
	{
	internal:
		Native::AIHeroClient* self;
	public:
		Experience(Native::AIHeroClient* hero)
		{
			this->self = hero;
		}

		property int SpellTrainingPoints
		{
			int get()
			{
				if (this->self != nullptr)
				{
					auto experience = this->self->GetExperience();
					if (experience != nullptr)
					{
						return *experience->GetSpellTrainingPoints();
					}
				}
				return 0;
			}
		}

		property int Level
		{
			int get()
			{
				if (this->self != nullptr)
				{
					auto experience = this->self->GetExperience();
					if (experience != nullptr)
					{
						return *experience->GetLevel();
					}
				}
				return 0;
			}
		}

		property float XP
		{
			float get()
			{
				if (this->self != nullptr)
				{
					auto experience = this->self->GetExperience();
					if (experience != nullptr)
					{
						return *experience->GetExperience();
					}
				}
				return 0;
			}
		}

		property float XPToNextLevel
		{
			float get()
			{
				if  (this->self != nullptr)
				{
					auto experience = this->self->GetExperience();
					if (experience != nullptr)
					{
						return experience->GetExpToNextLevel();
					}
				}
				return 0;
			}
		}

		property float XPToCurrentLevel
		{
			float get()
			{
				if (this->self != nullptr)
				{
					auto experience = this->self->GetExperience();
					if (experience != nullptr)
					{
						return experience->GetExpToCurrentLevel();
					}
				}
				return 0;
			}
		}

		property float XPPercentage
		{
			float get()
			{
				if (Level == 18)
				{
					return 100;
				}

				return ((XP - XPToCurrentLevel) * 100 / (XPToNextLevel - XPToCurrentLevel));
			}
		}

		property float XPNextLevelVisual
		{
			float get()
			{
				return XPToNextLevel - XPToCurrentLevel;
			}
		}
	};
}