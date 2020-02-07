#include "stdafx.h"

#include "Obj_AI_Base.hpp"
#include "Experience.h"

using namespace System;
using namespace SharpDX;

namespace EloBuddy
{
	public ref class Obj_AI_BaseSurrenderVoteEventArgs : public System::EventArgs
	{
	private:
		byte m_surrenderType;
	public:
		delegate void Obj_AI_BaseSurrenderVote( Obj_AI_Base^ sender, Obj_AI_BaseSurrenderVoteEventArgs^ args );

		Obj_AI_BaseSurrenderVoteEventArgs( byte surrenderType )
		{
			this->m_surrenderType = surrenderType;
		}

		property SurrenderVoteType Type
		{
			SurrenderVoteType get()
			{
				return static_cast<SurrenderVoteType>(this->m_surrenderType);
			}
		}
	};
}