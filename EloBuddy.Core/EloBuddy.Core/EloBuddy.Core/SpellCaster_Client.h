#pragma once

namespace EloBuddy
{
	namespace Native
	{
		class SpellCaster_Client
		{
		public:
			virtual ~SpellCaster_Client() {}

			virtual void Function1(); //0x4
			virtual void Function2(); //0x8
			virtual void Function3(); //0xC
			virtual void Function4(); //0x10
			virtual void Function5(); //0x14
			virtual void Function6(); //0x18
			virtual void Function7(); //0x1C
			virtual bool SpellWasCast(); //0x20
			virtual bool IsAutoAttacking(); //0x24
			virtual bool IsCharging(); //0x28
			virtual bool IsChanneling(); //0x2C
			virtual void Function12(); //0x30
			virtual void Function13(); //0x34
			virtual float CastEndTime(); //0x38
			virtual void Function15(); //0x3C
			virtual bool IsStopped(); //0x40
		};
	}
}