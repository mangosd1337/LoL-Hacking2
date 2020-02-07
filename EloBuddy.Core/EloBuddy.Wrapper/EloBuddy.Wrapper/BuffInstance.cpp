#include "stdafx.h"

#include "../../EloBuddy.Core/EloBuddy.Core/Obj_AI_Base.h"
#include "../../EloBuddy.Core/EloBuddy.Core/ObjectManager.h"

#include "BuffInstance.hpp"
#include "Exceptions.hpp"
#include "StaticEnums.h"

namespace EloBuddy
{
	BuffInstance::BuffInstance(Native::BuffInstance* inst, uint m_networkId, ushort index)
	{
		this->self = inst;
		this->m_networkId = m_networkId;
		this->m_index = index;
	}

	Native::BuffInstance* BuffInstance::GetBuffPtr()
	{
		if (this->self != nullptr)
		{
			return this->self;
		}

		auto ptr = static_cast<Native::Obj_AI_Base*>(Native::ObjectManager::GetUnitByNetworkId( this->m_networkId ));
		if (ptr != nullptr)
		{
			auto buffMgr = ptr->GetBuffManager();
			if (buffMgr != nullptr)
			{
				auto buffBegin = *buffMgr->GetBegin();
				auto buffEnd = *buffMgr->GetEnd();

				if (buffBegin != nullptr && buffEnd != nullptr)
				{
					for (uint i = 0; i < (buffEnd - buffBegin) / sizeof( Native::BuffInstance ); i++)
					{
						auto buffNode = buffBegin + i;
						auto buffInst = buffNode->buffInst;

						if (buffNode != nullptr && buffInst != nullptr)
						{
							if (buffInst == this->self)
							//if (*buffInst->GetIndex() == static_cast<byte>(this->m_index))
							{
								return buffInst;
							}
						}
					}
				}
			}
		}

		if (this->self != nullptr)
		{
			return this->self;
		}

		throw gcnew BuffInstanceNotFoundException();
	}
}