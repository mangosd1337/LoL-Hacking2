#include "stdafx.h"

namespace EloBuddy
{
	public ref class ShopCloseEventArgs : public System::EventArgs
	{
	private:
		bool m_process;
	public:
		delegate void ShopCloseEvent( ShopCloseEventArgs^ args );

		ShopCloseEventArgs()
		{
			this->m_process = true;
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