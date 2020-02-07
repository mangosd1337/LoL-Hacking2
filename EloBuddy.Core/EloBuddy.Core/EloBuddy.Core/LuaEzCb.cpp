#include "stdafx.h"
/*
#include "LuaEzCb.h"

namespace EloBuddy
{
	namespace Native
	{
		bool LuaEzCb::DefineModule()
		{
			module( LuaEz::GetMainState(), "Callback" )
				[
					def<void( const char*, luabind::object )>( "Bind", [] ( const char* callback, luabind::object obj )
					{
						LuaEzCb::GetInstance()->Bind( callback, obj );
					} ),

					def<void( const char*, luabind::object )>( "Unbind", [] ( const char* callback, luabind::object obj )
					{
						LuaEzCb::GetInstance()->Unbind( callback, obj );
					} )
				];

			for (auto i = 0; i < 60; i++)
			{
				auto eventName = EventIdToStr( i );
				if (strlen(eventName) > 0)
					Console::PrintLn( "Exported Event: %s (%d)", eventName, i);
			}

			return LuaEz::GetMainState() != nullptr;
		}

		bool LuaEzCb::Trigger( const char* callback )
		{
			return true;
			//return this->Trigger( callback, std::vector<luabind::object>() );
		}

		/*bool LuaEzCb::Trigger( const char* callback, std::vector<luabind::object> const & objs )
		{
			auto cbVec = m_callbacks [callback];
			auto returnValue = false;

			for (auto i = 0; i < cbVec.size(); i++)
			{
				switch (objs.size())
				{
					case 0:
						returnValue = luabind::call_function<bool>( cbVec.at( i ));
						break;
					case 1:
						returnValue = luabind::call_function<bool>( cbVec.at( i ), objs [0] );
						break;
					case 2:
						returnValue = luabind::call_function<bool>( cbVec.at( i ), objs [0], objs [1] );
						break;
					case 3:
						returnValue = luabind::call_function<bool>( cbVec.at( i ), objs [0], objs [1], objs [2] );
						break;
				}
			}

			return !returnValue;
		}

		bool LuaEzCb::Bind( const char* callback, luabind::object obj )
		{
			if (!obj.is_valid() || luabind::type( obj ) != LUA_TFUNCTION)
			{
				Console::PrintLn("Lua: Callback %s canceled, reason: not a LUA_TFUNCTION", callback);
				return false;
			}

			m_callbacks [callback].push_back( obj );

			return true;
		}

		bool LuaEzCb::Unbind( const char* callback, luabind::object obj )
		{
			if (!obj.is_valid() || luabind::type( obj ) != LUA_TFUNCTION)
			{
				Console::PrintLn( "Lua: Callback %s canceled, reason: not a LUA_TFUNCTION", callback );
				return false;
			}

			auto cbVec = m_callbacks [callback];

			for (auto it = cbVec.begin(); it != cbVec.end();)
			{
				if (it == obj)
				{
					cbVec.erase( it );
					return true;
				}
			}

			return false;
		}

		const char* LuaEzCb::EventIdToStr( int eventId )
		{
			auto returnValue = "";

			switch (eventId)
			{
			case 15:
				returnValue = "OnIssueOrder";
					break;
			case 31:
				returnValue = "OnPreTick";
				break;
			case 32:
				returnValue = "OnTick";
				break;
			case 33:
				returnValue = "OnPostTick";
				break;
			}

			return returnValue;
		}
	}
}*/