#pragma once


#ifndef MANAGED_BUILD
	#pragma comment(lib, "Lua52.lib")
	#pragma comment(lib, "luabind.lib")
#endif

extern "C"
{
	#include "lua-52/lua52/Lua52/src/lua.h"
	#include "lua-52/lua52/Lua52/src/lualib.h"
}

#include "luabind/luabind/src/luabind.hpp"
#include "luabind/luabind/src/class_info.hpp"

using namespace luabind;

#include "Utils.h"


namespace EloBuddy
{
	namespace Native
	{
		class
			DLLEXPORT Lua
		{

		public:
			static lua_State* LuaState;

			lua_State *L;

			Lua();
			~Lua();

			bool Execute(const luabind::object obj) const;
			luabind::object MakeFnc(const std::string & lua) const;

			template <class ValueWrapper1, class ValueWrapper2>
			void setfenv(
				ValueWrapper1 const &obj, ValueWrapper2 const &env)
			{
				lua_State *interpreter = value_wrapper_traits<ValueWrapper1>::interpreter(obj);
				value_wrapper_traits<ValueWrapper1>::unwrap(interpreter, obj);
				detail::stack_pop pop(interpreter, 1);
				value_wrapper_traits<ValueWrapper2>::unwrap(interpreter, env);
				lua_setfenv(interpreter, -2);
			}

		private:
			void RegisterFuncs(lua_State*);
			void RegisterGlobals(lua_State*);
		};
	}
}
