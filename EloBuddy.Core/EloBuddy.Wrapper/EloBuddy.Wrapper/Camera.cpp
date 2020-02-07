#include "stdafx.h"
#include "Camera.h"

namespace EloBuddy
{
	static Camera::Camera()
	{
		ATTACH_DOMAIN();

		ATTACH_EVENT
		(
			CameraSnap,
			55, Native::OnCameraSnap
		);
		ATTACH_EVENT
		(
			CameraToggleLock,
			56, Native::OnCameraToggleLock
		);
		ATTACH_EVENT
		(
			CameraUpdate,
			57, Native::OnCameraUpdate, float, float
		);
		ATTACH_EVENT
		(
			CameraZoom,
			58, Native::OnCameraZoom
		);
	}

	void Camera::DomainUnloadEventHandler( System::Object^, System::EventArgs^ )
	{
		DETACH_EVENT
		(
			CameraSnap,
			55, Native::OnCameraSnap
		);
		DETACH_EVENT
		(
			CameraToggleLock,
			56, Native::OnCameraToggleLock
		);
		DETACH_EVENT
		(
			CameraUpdate,
			57, Native::OnCameraUpdate, float, float
		);
		DETACH_EVENT
		(
			CameraZoom,
			58, Native::OnCameraZoom
		);
	}

	bool Camera::OnCameraSnapNative()
	{
		bool process = true;

		START_TRACE
			auto args = gcnew CameraSnapEventArgs();

			for each(auto eventHandle in CameraSnapHandlers->ToArray())
			{
				eventHandle( args );

				if (!args->Process)
					process = false;
			}
		END_TRACE

		return process;
	}

	bool Camera::OnCameraToggleLockNative()
	{
		bool process = true;

		START_TRACE
			auto args = gcnew CameraLockToggleEventArgs();

			for each(auto eventHandle in CameraToggleLockHandlers->ToArray())
			{
				eventHandle( args );

				if (!args->Process)
					process = false;
			}
		END_TRACE

		return process;
	}


	bool Camera::OnCameraUpdateNative(float mouseX, float mouseY)
	{
		bool process = true;

		START_TRACE
			auto args = gcnew CameraUpdateEventArgs(mouseX, mouseY);

			for each(auto eventHandle in CameraUpdateHandlers->ToArray())
			{
				eventHandle( args );

				if (!args->Process)
					process = false;
			}
		END_TRACE

		return process;
	}

	bool Camera::OnCameraZoomNative()
	{
		bool process = true;

		START_TRACE
			auto args = gcnew CameraZoomEventArgs();

			for each(auto eventHandle in CameraZoomHandlers->ToArray())
			{
				eventHandle( args );

				if (!args->Process)
					process = false;
			}
		END_TRACE

		return process;
	}
}