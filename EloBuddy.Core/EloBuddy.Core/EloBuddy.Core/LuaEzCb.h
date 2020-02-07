#pragma once

/*
#include "LuaEz.h"
#include "Utils.h"

#include <unordered_map>

namespace EloBuddy
{
	namespace Native
	{
		class
			LuaEzCb
		{
		private:
			std::unordered_map<std::string, std::vector<luabind::object>> m_callbacks;
		public:
			static LuaEzCb* GetInstance() {
				static auto instance = new LuaEzCb();
				return instance;
			}

			bool DefineModule();

			bool Trigger( const char* callback );
			bool Trigger( const char* callback, std::vector<luabind::object> const & objs );

			bool Bind( const char* callback, luabind::object obj);
			bool Unbind( const char* callback, luabind::object obj );

			static const char* EventIdToStr( int eventId );

			template <int eventId, typename ... A>
			bool Trigger(A... args)
			{
				auto cbVec = m_callbacks [EventIdToStr(eventId)];
				auto returnValue = false;

				for (auto i = 0; i < cbVec.size(); i++)
				{
					luabind::call_function<void>(cbVec.at(i), args... );
				}

				return returnValue;
			}
		};
	}
}*/