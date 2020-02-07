#pragma once

#include "../../EloBuddy.Core/EloBuddy.Core/NavMesh.h"
#include "../../EloBuddy.Core/EloBuddy.Core/NavMeshCell.h"

#include "Game.hpp"

using namespace System;
using namespace System::Collections::Generic;
using namespace SharpDX;

#define VERSION_PROP(NAME, TYPE, PRIVATE_NAME) static property TYPE NAME { TYPE get() { return PRIVATE_NAME; } }

namespace EloBuddy
{
	public ref class Version
	{
	private:
		static int m_majorVersion;
		static int m_minorVersion;
		static int m_build;
		static int m_revision;
		static System::Version^ m_version;
	public:
		static Version()
		{
			auto versionString = Game::Version->Split( '.' );
			if (versionString->Length == 4)
			{
				m_majorVersion = Int32::Parse (versionString[0]);
				m_minorVersion = Int32::Parse( versionString[1] );
				m_build = Int32::Parse( versionString[2] );
				m_revision = Int32::Parse( versionString[3] );
				m_version = gcnew System::Version( m_majorVersion, m_minorVersion, m_build, m_revision );
			}
		}

		VERSION_PROP( MajorVersion, int, m_majorVersion );
		VERSION_PROP( MinorVersion, int, m_minorVersion );
		VERSION_PROP( Build, int, m_build );
		VERSION_PROP( Revision, int, m_revision );
		VERSION_PROP( CurrentVersion, System::Version^, m_version );

		static bool IsEqual(System::String^ version)
		{
			return CurrentVersion->Equals( gcnew System::Version( version ) );
		}

		static bool IsEqual( System::Version^ version )
		{
			return CurrentVersion->Equals( version );
		}

		static bool IsNewer( System::String^ version )
		{
			return CurrentVersion > gcnew System::Version( version );
		}

		static bool IsNewer( System::Version^ version )
		{
			return CurrentVersion > version;
		}

		static bool IsOlder( System::String^ version )
		{
			return CurrentVersion < gcnew System::Version( version );
		}

		static bool IsOlder( System::Version^ version )
		{
			return CurrentVersion < version;
		}
	};
}