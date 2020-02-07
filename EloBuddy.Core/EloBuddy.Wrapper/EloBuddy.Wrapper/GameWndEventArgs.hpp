#include "stdafx.h"

using namespace System;

namespace EloBuddy
{
	public ref class WndEventArgs : public EventArgs
	{
	private:
		HWND m_hwnd;
		UINT m_msg;
		WPARAM m_wparam;
		LPARAM m_lparam;
		bool m_process;
	public:
		delegate void WndProcEvent(WndEventArgs^ args);

		WndEventArgs( HWND HWnd, UINT message, WPARAM WParam, LPARAM LParam)
		{
			this->m_hwnd = HWnd;
			this->m_msg = message;
			this->m_lparam = LParam;
			this->m_wparam = WParam;
			this->m_process = true;
		}

		property UInt32 HWnd
		{
			UInt32 get()
			{
				return reinterpret_cast<UInt32>(this->m_hwnd);
			}
		}

		property UInt32 Msg
		{
			UInt32 get()
			{
				return static_cast<UInt32>(this->m_msg);
			}
		}

		property UInt32 WParam
		{
			UInt32 get()
			{
				return static_cast<UInt32>(this->m_wparam);
			}
		}

		property long LParam
		{
			long get()
			{
				return static_cast<long>(this->m_lparam);
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