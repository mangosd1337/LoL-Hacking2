#include "Stdafx.h"
#include "SpellData.hpp"

using namespace System::Runtime::InteropServices;

namespace EloBuddy
{
	SpellData::SpellData(Native::SpellData* spelldata)
	{
		this->self = spelldata;
	}

	SpellData::SpellData( uint hash )
	{
		//this->self = Native::SpellData::FindSpell( hash );
	}

	SpellData::SpellData( String^ name )
	{
		//this->GetPtr() = Native::SpellData::FindSpell( Native::SpellData::HashSpell( (char*) Marshal::StringToHGlobalAnsi( name ).ToPointer() ) );
	}

	SpellData^ SpellData::GetSpellData( String^ name )
	{
		auto stringPtr = Marshal::StringToHGlobalAnsi( name );
		SpellData^ sdata = nullptr;
		Native::SpellData* sdata_n = Native::SpellData::FindSpell( (char*) stringPtr.ToPointer() );
		if (sdata_n != nullptr)
		{
			sdata = gcnew SpellData( sdata_n );
		}
		Marshal::FreeHGlobal( stringPtr );
		return sdata;
	}
}