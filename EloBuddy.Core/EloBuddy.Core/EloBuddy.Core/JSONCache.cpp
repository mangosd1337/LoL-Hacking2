#include "stdafx.h"
#include "JSONCache.h"
#include "Core.h"

#include "resource.h"

namespace EloBuddy
{
	namespace Native
	{
		JSONCache* JSONCache::GetInstance()
		{
			static auto instance = new JSONCache();
			return instance;
		}

		bool JSONCache::Parse()
		{
			//Items
			__try
			{
				auto itemResource = ::FindResource( Core::GetInstance()->GetHModule(), MAKEINTRESOURCE( IDR_RCDATA1 ), RT_RCDATA );
				auto itemResourceData = ::LoadResource( Core::GetInstance()->GetHModule(), itemResource );
				auto pitemData = ::LockResource( itemResourceData );

				m_itemDocument.Parse( static_cast<char*>(pitemData) );
			}
			__except (EXCEPTION_EXECUTE_HANDLER)
			{
				return false;
			}

			//Champions
			__try
			{
				auto championResource = ::FindResource( Core::GetInstance()->GetHModule(), MAKEINTRESOURCE( IDR_RCDATA2 ), RT_RCDATA );
				auto championResourceData = ::LoadResource( Core::GetInstance()->GetHModule(), championResource );
				auto pChampionData = ::LockResource( championResourceData );

				m_championDocument.Parse( static_cast<char*>(pChampionData) );
			}
			__except (EXCEPTION_EXECUTE_HANDLER)
			{
				return false;
			}

			return !m_itemDocument.HasParseError()
				&& !m_championDocument.HasParseError();
		}

		rapidjson::Document & JSONCache::GetItemDocument()
		{
			return m_itemDocument;
		}

		rapidjson::Document & JSONCache::GetChampionDocument()
		{
			return m_championDocument;
		}
	}
}