#include "stdafx.h"
#include "MapData.h"
#include "Utils.h"

namespace EloBuddy
{
	namespace Native
	{
		Mapata* MapData::getInstance()
		{
			static MapData* instance = *(MapData**)(MAKE_RVA(m_GameMap::MapData));
			return instance;
		}
	}
}