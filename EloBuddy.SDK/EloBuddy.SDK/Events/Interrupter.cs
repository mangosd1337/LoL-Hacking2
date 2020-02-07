using System;
using System.Collections.Generic;
using System.Linq;
using EloBuddy.SDK.Enumerations;

namespace EloBuddy.SDK.Events
{
    public static class Interrupter
    {
        public delegate void InterruptableSpellHandler(Obj_AI_Base sender, InterruptableSpellEventArgs e);

        public static event InterruptableSpellHandler OnInterruptableSpell;

        internal static readonly Dictionary<int, InterruptableSpell> CastingSpell = new Dictionary<int, InterruptableSpell>();
        internal static readonly Dictionary<string, List<InterruptableSpell>> SpellDatabase = new Dictionary<string, List<InterruptableSpell>>();

        static Interrupter()
        {
            Initialize();
        }

        internal static void Initialize()
        {
            //High Priority
            RegisterSpell("Urgot", SpellSlot.R, DangerLevel.High);
            RegisterSpell("Velkoz", SpellSlot.R, DangerLevel.High);
            RegisterSpell("Warwick", SpellSlot.R, DangerLevel.High);
            RegisterSpell("Xerath", SpellSlot.R, DangerLevel.High);
            RegisterSpell("Caitlyn", SpellSlot.R, DangerLevel.High);
            RegisterSpell("FiddleSticks", SpellSlot.R, DangerLevel.High);
            RegisterSpell("Galio", SpellSlot.R, DangerLevel.High);
            RegisterSpell("Sion", SpellSlot.Q, DangerLevel.High);
            RegisterSpell("Karthus", SpellSlot.R, DangerLevel.High);
            RegisterSpell("Katarina", SpellSlot.R, DangerLevel.High);
            RegisterSpell("Lucian", SpellSlot.R, DangerLevel.High);
            RegisterSpell("Malzahar", SpellSlot.R, DangerLevel.High);
            RegisterSpell("MissFortune", SpellSlot.R, DangerLevel.High);
            RegisterSpell("Nunu", SpellSlot.R, DangerLevel.High);

            //Medium Priority
            RegisterSpell("FiddleSticks", SpellSlot.W, DangerLevel.Medium);
            RegisterSpell("Varus", SpellSlot.Q, DangerLevel.Medium);
            RegisterSpell("Pantheon", SpellSlot.E, DangerLevel.Medium);
            RegisterSpell("Janna", SpellSlot.R, DangerLevel.Medium);
            RegisterSpell("TahmKench", SpellSlot.R, DangerLevel.Medium);
            RegisterSpell("Quinn", SpellSlot.R, DangerLevel.Medium);
            RegisterSpell("Xerath", SpellSlot.Q, DangerLevel.Medium);
            RegisterSpell("Zac", SpellSlot.E, DangerLevel.Medium);

            //Low Priority
            RegisterSpell("MasterYi", SpellSlot.W, DangerLevel.Low);
            RegisterSpell("RekSai", SpellSlot.R, DangerLevel.Low);
            RegisterSpell("Shen", SpellSlot.R, DangerLevel.Low);
            RegisterSpell("TwistedFate", SpellSlot.R, DangerLevel.Low);

            // Listen to required events
            Game.OnTick += GameOnOnUpdate;
            Obj_AI_Base.OnProcessSpellCast += OnOnProcessSpellCast;
        }

        internal static void GameOnOnUpdate(EventArgs args)
        {
            foreach (
                var unit in
                    EntityManager.Heroes.AllHeroes
                        .Where(
                            h =>
                                CastingSpell.ContainsKey(h.NetworkId) && !h.Spellbook.IsChanneling && !h.Spellbook.IsCharging && !h.Spellbook.IsCastingSpell))
            {
                CastingSpell.Remove(unit.NetworkId);
            }
            if (OnInterruptableSpell == null)
            {
                return;
            }

            foreach (var interrupterArgs in EntityManager.Heroes.AllHeroes.Select(GetSpell).Where(h => h != null))
            {
                OnInterruptableSpell(interrupterArgs.Sender, interrupterArgs);
            }
        }

        internal static void RegisterSpell(string champName, SpellSlot slot, DangerLevel dangerLevel)
        {
            if (!SpellDatabase.ContainsKey(champName))
            {
                SpellDatabase.Add(champName, new List<InterruptableSpell>());
            }
            SpellDatabase[champName].Add(new InterruptableSpell { SpellSlot = slot, DangerLevel = dangerLevel });
        }

        internal static void OnOnProcessSpellCast(Obj_AI_Base sender, GameObjectProcessSpellCastEventArgs args)
        {
            var unit = sender as AIHeroClient;
            if (unit == null || CastingSpell.ContainsKey(unit.NetworkId) || !SpellDatabase.ContainsKey(unit.ChampionName))
            {
                return;
            }

            var spell = SpellDatabase[unit.ChampionName].Find(o => o.SpellSlot == args.Slot);
            if (spell != null)
            {
                CastingSpell.Add(unit.NetworkId, spell);
            }
        }

        internal static InterruptableSpellEventArgs GetSpell(AIHeroClient target)
        {
            if (!target.IsValid || target.IsDead || !CastingSpell.ContainsKey(target.NetworkId))
            {
                return null;
            }

            var spell = CastingSpell[target.NetworkId];
            return new InterruptableSpellEventArgs
            {
                Sender = target,
                Slot = spell.SpellSlot,
                DangerLevel = spell.DangerLevel,
                EndTime = target.Spellbook.CastEndTime
            };
        }

        internal class InterruptableSpell
        {
            public DangerLevel DangerLevel { get; internal set; }
            public SpellSlot SpellSlot { get; internal set; }
        }

        public class InterruptableSpellEventArgs : EventArgs
        {
            public AIHeroClient Sender { get; internal set; }
            public SpellSlot Slot { get; internal set; }
            public DangerLevel DangerLevel { get; internal set; }
            public float EndTime { get; internal set; }
        }
    }
}
