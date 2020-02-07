#include "stdafx.h"

#include "Lua.h"
#include "ObjectManager.h"
#include "AIHeroClient.h"

#ifndef MANAGED_BUILD
	#include <boost/format.hpp>
#endif

using namespace luabind;

namespace EloBuddy
{
	namespace Native
	{
		lua_State* Lua::LuaState = nullptr;

		Lua::Lua()
		{
			/*Console::PrintLn("new lua");

			L = luaL_newstate();
			luaL_openlibs(L);
			luabind::open(L);

			LuaState = L;

			RegisterFuncs(L);
			RegisterGlobals(L);

			luaL_dostring(L, "print('name:' .. test.Name)");*/
		}

		Lua::~Lua()
		{
			//lua_gc(L, LUA_GCCOLLECT, 0);
			//lua_close(L);
		}

		bool Lua::Execute(const luabind::object obj) const
		{
			call_function<void>(obj);

			return true;
		}

		object Lua::MakeFnc(const std::string& lua) const
		{
			int ret;
			if ((ret = luaL_loadbuffer(L, lua.data(), lua.size(), "scriptName") == LUA_OK))
			{
				object scriptObject(from_stack(L, -1));
				lua_pop(L, 1);

				return scriptObject;
			}

			return object();
		}

		void Lua::RegisterFuncs(lua_State* L)
		{
			GameObject::ExportFunctions();
		}

		void Lua::RegisterGlobals(lua_State* L)
		{
			globals(L) ["test"] = (GameObject*) ObjectManager::GetPlayer();
		}
	}
}