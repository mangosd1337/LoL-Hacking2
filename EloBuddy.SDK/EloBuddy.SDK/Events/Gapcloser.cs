using System;
using System.Collections.Generic;
using System.Linq;

using EloBuddy.SDK.Enumerations;
using EloBuddy.SDK.Menu.Values;
using EloBuddy.SDK.Properties;
using EloBuddy.SDK.Utils;

using Newtonsoft.Json;

using SharpDX;

namespace EloBuddy.SDK.Events
{
    public static class Gapcloser
    {
        internal static Menu.Menu Menu { get; set; }

        public delegate void GapcloserHandler(AIHeroClient sender, GapcloserEventArgs e);

        public static event GapcloserHandler OnGapcloser;

        internal static List<GapCloser> _gapCloserList;

        public static List<GapCloser> GapCloserList
        {
            get
            {
                return new List<GapCloser>(_gapCloserList);
            }
        }

        internal static List<GapcloserEventArgs> _activeGapClosers;

        public static List<GapcloserEventArgs> ActiveGapClosers
        {
            get
            {
                return new List<GapcloserEventArgs>(_activeGapClosers);
            }
        }

        static Gapcloser()
        {
            // Initialize properties
            _activeGapClosers = new List<GapcloserEventArgs>();
            _gapCloserList = new List<GapCloser>();
            try
            {
                _gapCloserList = JsonConvert.DeserializeObject<List<GapCloser>>(Resources.Gapclosers);
            }
            catch (Exception e)
            {
                Logger.Log(LogLevel.Error, "Failed to load gapclosers:\n{}", e);
            }

            // Listen to required events
            Obj_AI_Base.OnProcessSpellCast += OnProcessSpellCast;
            Game.OnTick += OnTick;
        }

        internal static void Initialize()
        {
        }

        internal static void AddMenu()
        {
            var addedGapclosers = new List<GapCloser>();
            foreach (
                var gapCloser in
                    _gapCloserList.Where(gapCloser => EntityManager.Heroes.AllHeroes.Any(h => h.ChampionName == gapCloser.ChampName))
                        .OrderBy(h => h.ChampName)
                        .ThenBy(h => h.SpellSlot))
            {
                // Create the menu if it's not created yet
                if (Menu == null)
                {
                    Menu = Core.Menu.AddSubMenu("Gapcloser");
                    Menu.AddGroupLabel("Spells to be detected:");
                }

                // Add a label for each champ if it's not present yet
                if (addedGapclosers.All(o => o.ChampName != gapCloser.ChampName))
                {
                    Menu.AddLabel(gapCloser.ChampName);
                }

                // Add a checkbox for each spell
                Menu.Add(gapCloser.ToString(), new CheckBox(string.Format("{0} - {1}", gapCloser.SpellSlot, gapCloser.SpellName)));

                addedGapclosers.Add(gapCloser);
            }
        }

        internal static void OnTick(EventArgs args)
        {
            _activeGapClosers.RemoveAll(entry => Core.GameTickCount > entry.TickCount + 1500 || entry.Sender.IsDead);

            if (OnGapcloser == null || _activeGapClosers.Count == 0)
            {
                return;
            }

            foreach (var gapcloser in _activeGapClosers.Where(o => o.Handle.IsEnabled))
            {
                if (gapcloser.Target != null && gapcloser.Target.IsValidTarget())
                {
                    gapcloser.End = gapcloser.Target.ServerPosition;
                }
                OnGapcloser(gapcloser.Sender, gapcloser);
            }
        }

        internal static void OnProcessSpellCast(Obj_AI_Base sender, GameObjectProcessSpellCastEventArgs args)
        {
            var heroSender = sender as AIHeroClient;
            if (heroSender == null)
            {
                return;
            }

            var gapCloser =
                _gapCloserList.FirstOrDefault(
                    o =>
                        o.ChampName == heroSender.ChampionName &&
                        o.SpellName == (args.SData.Name ?? args.SData.AlternateName).ToLower());
            if (!gapCloser.IsValid)
            {
                return;
            }

            // Calculate skillshot end position
            var endPos = args.End;
            Obj_AI_Base target = null;
            switch (gapCloser.SkillType)
            {
                case GapcloserType.Skillshot:
                    var range = args.SData.CastRangeDisplayOverride;
                    // ReSharper disable once CompareOfFloatsByEqualityOperator
                    if (range == 0)
                    {
                        range = args.SData.CastRange;
                    }
                    var targettingType = args.SData.TargettingType;
                    var isLinear = targettingType == SpellDataTargetType.Location ||
                                   targettingType == SpellDataTargetType.Location2
                                   || targettingType == SpellDataTargetType.Location3 ||
                                   targettingType == SpellDataTargetType.LocationAoe;
                    if (!endPos.IsInRange(heroSender, range) || isLinear)
                    {
                        endPos = heroSender.ServerPosition.Extend(endPos, range).To3DWorld();
                    }
                    break;
                case GapcloserType.Targeted:
                    target = args.Target as Obj_AI_Base;
                    break;
            }

            switch (gapCloser.ChampName)
            {
                case "Azir":
                    var soldier = Orbwalker.AzirSoldiers.OrderBy(s => s.Distance(args.End))
                        .FirstOrDefault(s => s.IsValid && s.Team == heroSender.Team);
                    if (soldier != null)
                    {
                        endPos = soldier.ServerPosition;
                        target = soldier;
                    }
                    else
                    {
                        return;
                    }
                    break;
                case "Caitlyn":
                    endPos = heroSender.ServerPosition.Extend(args.End, -375).To3D();
                    break;
                case "Leona":
                    var lastEnemy =
                        EntityManager.Heroes.Enemies.OrderByDescending(h => args.Start.Distance(h, true))
                            .FirstOrDefault(
                                h =>
                                    h.IsValidTarget()
                                    && h.ServerPosition.To2D().Distance(args.Start.To2D(), args.End.To2D(), true, true)
                                    <= ((args.SData.LineWidth + h.BoundingRadius)*1.8f).Pow());
                    if (lastEnemy != null)
                    {
                        endPos = lastEnemy.ServerPosition;
                        target = lastEnemy;
                    }
                    else
                    {
                        return;
                    }
                    break;
                case "LeeSin":
                    var qTarget =
                        ObjectManager.Get<Obj_AI_Base>()
                            .FirstOrDefault(
                                o =>
                                    o.IsValidTarget() && o.Team != heroSender.Team &&
                                    (o.HasBuff("BlindMonkQOne") || o.HasBuff("BlindMonkQOneChaos")));
                    if (qTarget != null)
                    {
                        endPos = qTarget.ServerPosition;
                        target = qTarget;
                    }
                    else
                    {
                        return;
                    }
                    break;
                case "JarvanIV":
                    var flag =
                        ObjectManager.Get<Obj_AI_Minion>()
                            .FirstOrDefault(
                                o =>
                                    o.IsEnemy && o.IsMinion && !o.IsMonster && o.MaxHealth <= 6 &&
                                    o.BaseSkinName.Equals("JarvanIVStandard"));
                    if (flag == null
                        || flag.ServerPosition.To2D().Distance(args.Start.To2D(), endPos.To2D(), true, true)
                        > (args.SData.LineWidth + flag.BoundingRadius).Pow().Pow())
                    {
                        return;
                    }
                    break;
                case "Thresh":
                    var leaptarget =
                        ObjectManager.Get<Obj_AI_Base>()
                            .FirstOrDefault(
                                o =>
                                    o.IsValidTarget() && o.Team != heroSender.Team &&
                                    o.Buffs.Any(b => b.Name.ToLower().Contains("threshq")));
                    if (leaptarget != null)
                    {
                        endPos = leaptarget.ServerPosition;
                        target = leaptarget;
                    }
                    else
                    {
                        return;
                    }
                    break;
                case "Riven":
                    var Q3 = heroSender.GetBuffCount("RivenTriCleave") >= 2;
                    if (Q3 && args.Slot == SpellSlot.Q)
                    {
                        endPos =
                            (heroSender.Position.To2D() + 500*heroSender.Direction.To2D().Perpendicular()).Extend(
                                args.End,
                                args.SData.CastRangeDisplayOverride).To3DWorld();
                    }
                    else if (args.Slot == SpellSlot.E)
                    {
                        endPos =
                            heroSender.ServerPosition.Extend(args.End, args.SData.CastRangeDisplayOverride).To3DWorld();
                    }
                    else
                    {
                        return;
                    }
                    break;
            }

            // Add gapclose to active gapclosers
            _activeGapClosers.Add(
                new GapcloserEventArgs
                {
                    Handle = gapCloser,
                    Sender = heroSender,
                    SpellName = args.SData.Name ?? args.SData.AlternateName,
                    Slot = gapCloser.SpellSlot,
                    Type = gapCloser.SkillType,
                    Start = args.Start,
                    SenderMousePos = args.End,
                    End = endPos,
                    Target = target,
                    TickCount = Core.GameTickCount,
                    GameTime = Game.Time
                });
        }

        public struct GapCloser
        {
            public string ChampName;

            public SpellSlot SpellSlot;

            public string SpellName;

            public GapcloserType SkillType;

            internal bool IsValid
            {
                get
                {
                    return this.ChampName != null && this.SpellName != null;
                }
            }

            internal bool IsEnabled
            {
                get
                {
                    return Menu[this.ToString()].Cast<CheckBox>().CurrentValue;
                }
            }

            public override string ToString()
            {
                return string.Format("{0}: ({1}) - {2} ({3})", this.ChampName, this.SpellSlot, this.SpellName, this.SkillType);
            }
        }

        public enum GapcloserType
        {
            Skillshot,

            Targeted
        }

        public class GapcloserEventArgs : EventArgs
        {
            internal GapCloser Handle { get; set; }

            public AIHeroClient Sender { get; set; }

            public string SpellName { get; set; }

            public SpellSlot Slot { get; set; }

            public GapcloserType Type { get; set; }

            public Vector3 Start { get; set; }

            public Vector3 End { get; set; }

            public Vector3 SenderMousePos { get; set; }

            public int TickCount { get; set; }

            public float GameTime { get; set; }

            public Obj_AI_Base Target { get; set; }
        }
    }
}