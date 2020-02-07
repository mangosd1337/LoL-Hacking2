#pragma once

using namespace SharpDX;

namespace EloBuddy
{
	public ref struct Vector3Time
	{
	private:
		Vector3 m_pos;
		float m_time;

	public:
		Vector3Time(Vector3 pos, float time)
		{
			this->m_pos = pos;
			this->m_time = time;
		}

		property Vector3 Position
		{
			Vector3 get()
			{
				return this->m_pos;
			}
		}

		property float Time
		{
			float get()
			{
				return this->m_time;
			}
		}
	};
}