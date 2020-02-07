using System;
using EloBuddy.SDK.Enumerations;

namespace EloBuddy.SDK.Spells
{
    public enum SummonerSpellsEnum
    {
        Barrier,
        Clarity,
        Cleanse,
        Exhaust,
        Flash,
        Mark,
        Ghost,
        Heal,
        Ignite,
        Smite
    }
    public static class SummonerSpells
    {
        public static Spell.Active Barrier = new Spell.Active(SpellSlot.Unknown, int.MaxValue);
        public static Spell.Active Clarity = new Spell.Active(SpellSlot.Unknown, int.MaxValue);
        public static Spell.Active Cleanse = new Spell.Active(SpellSlot.Unknown, int.MaxValue);
        public static Spell.Targeted Exhaust = new Spell.Targeted(SpellSlot.Unknown, 650);
        public static Spell.Skillshot Flash = new Spell.Skillshot(SpellSlot.Unknown, 420, SkillShotType.Circular, 0, int.MaxValue);
        public static Spell.Skillshot Mark = new Spell.Skillshot(SpellSlot.Unknown, 1600, SkillShotType.Linear, 0, 1000, 60);
        public static Spell.Active Ghost = new Spell.Active(SpellSlot.Unknown, int.MaxValue);
        public static Spell.Active Heal = new Spell.Active(SpellSlot.Unknown, int.MaxValue);
        public static Spell.Targeted Ignite = new Spell.Targeted(SpellSlot.Unknown, 600);
        public static Spell.Targeted Smite = new Spell.Targeted(SpellSlot.Unknown, 680);

        public static bool PlayerHas(SummonerSpellsEnum sumSpell)
        {
            switch (sumSpell)
            {
                case SummonerSpellsEnum.Barrier:
                    return Barrier.Slot != SpellSlot.Unknown;
                case SummonerSpellsEnum.Clarity:
                    return Clarity.Slot != SpellSlot.Unknown;
                case SummonerSpellsEnum.Cleanse:
                    return Cleanse.Slot != SpellSlot.Unknown;
                case SummonerSpellsEnum.Exhaust:
                    return Exhaust.Slot != SpellSlot.Unknown;
                case SummonerSpellsEnum.Flash:
                    return Flash.Slot != SpellSlot.Unknown;
                case SummonerSpellsEnum.Mark:
                    return Mark.Slot != SpellSlot.Unknown;
                case SummonerSpellsEnum.Ghost:
                    return Ghost.Slot != SpellSlot.Unknown;
                case SummonerSpellsEnum.Heal:
                    return Heal.Slot != SpellSlot.Unknown;
                case SummonerSpellsEnum.Ignite:
                    return Ignite.Slot != SpellSlot.Unknown;
                case SummonerSpellsEnum.Smite:
                    return Smite.Slot != SpellSlot.Unknown;
            }
            return false;
        }

        internal static void Initialize()
        {
            Barrier.Slot = Player.Instance.FindSummonerSpellSlotFromName("summonerbarrier");
            Clarity.Slot = Player.Instance.FindSummonerSpellSlotFromName("summonermana");
            Cleanse.Slot = Player.Instance.FindSummonerSpellSlotFromName("summonerboost");
            Exhaust.Slot = Player.Instance.FindSummonerSpellSlotFromName("summonerexhaust");
            Flash.Slot = Player.Instance.FindSummonerSpellSlotFromName("summonerflash");
            Mark.Slot = Player.Instance.FindSummonerSpellSlotFromName("summonersnowball");
            Ghost.Slot = Player.Instance.FindSummonerSpellSlotFromName("summonerhaste");
            Heal.Slot = Player.Instance.FindSummonerSpellSlotFromName("summonerheal");
            Ignite.Slot = Player.Instance.FindSummonerSpellSlotFromName("summonerdot");
            Smite.Slot = Player.Instance.FindSummonerSpellSlotFromName("smite");
        }
    }
}