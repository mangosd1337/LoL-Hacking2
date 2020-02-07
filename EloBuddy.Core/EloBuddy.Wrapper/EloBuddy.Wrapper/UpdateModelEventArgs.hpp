#include "stdafx.h"

using namespace System;
using namespace SharpDX;

namespace EloBuddy
{
	ref class Obj_AI_Base;

	public ref class UpdateModelEventArgs : public System::EventArgs
	{
	private:
		String^ m_model;
		int m_skinId;
		bool m_process;
	public:
		delegate void UpdateModelEvent( Obj_AI_Base^ sender, UpdateModelEventArgs^ args );

		UpdateModelEventArgs( String^ model, int skinId )
		{
			this->m_model = model;
			this->m_skinId = skinId;
			this->m_process = true;
		}

		property String^ Model
		{
			String^ get()
			{
				return this->m_model;
			}
		}

		property int SkinId
		{
			int get()
			{
				return this->m_skinId;
			}
		}

		property bool Process
		{
			bool get()
			{
				return this->m_process;
			}
			void set(bool value)
			{
				this->m_process = value;
			}
		}
	};
}