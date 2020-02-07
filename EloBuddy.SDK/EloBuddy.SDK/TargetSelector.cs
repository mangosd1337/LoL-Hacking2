using System;
using System.Collections.Generic;
using System.Linq;
using EloBuddy.SDK.Enumerations;
using EloBuddy.SDK.Menu;
using EloBuddy.SDK.Menu.Values;
using EloBuddy.SDK.Notifications;
using EloBuddy.SDK.Properties;
using EloBuddy.SDK.Rendering;
using EloBuddy.SDK.Utils;
using Newtonsoft.Json;
using SharpDX;

namespace EloBuddy.SDK
{
    public static class TargetSelector
    {
        internal static Menu.Menu Menu { get; set; }

        public static TargetSelectorMode ActiveMode { get; set; }

        public static AIHeroClient SelectedTarget { get; internal set; }

        #region Menu Values

        //Seleted OP
        public static bool SeletedEnabled
        {
            get { return SelectedEnabled; }
        }
        public static bool SelectedEnabled
        {
            get { return Menu["selectedTargetEnabled"].Cast<CheckBox>().CurrentValue; }
        }
        public static bool DrawCircleAroundSelected
        {
            get { return Menu["drawSelectedTarget"].Cast<CheckBox>().CurrentValue; }
        }
        private static bool OnlySelectedTargetEnabled
        {
            get { return Menu["onlySelectedTargetEnabled"].Cast<CheckBox>().CurrentValue; }
        }
        private static bool OnlySelectedTargetKey
        {
            get { return Menu["onlySelectedTargetKey"].Cast<KeyBind>().CurrentValue; }
        }
        private static bool DrawNotifications
        {
            get { return Menu["drawNotifications"].Cast<CheckBox>().CurrentValue; }
        }
        public static bool OnlySelectedTarget
        {
            get { return OnlySelectedTargetEnabled && OnlySelectedTargetKey; }
        }

        #endregion

        internal static readonly Dictionary<Champion, int> Priorities = new Dictionary<Champion, int>();
        internal static readonly Dictionary<Champion, Func<int>> CurrentPriorities = new Dictionary<Champion, Func<int>>();
        internal static Dictionary<string, int> PriorityData;
        internal static readonly Dictionary<Champion, string[]> BuffStackNames = new Dictionary<Champion, string[]>
        {
            //Unknown: It's valid for every ally.
            { Champion.Unknown, new[] { "BraumMark" } },
            { Champion.Darius, new[] { "DariusHemo" } },
            { Champion.Ekko, new[] { "EkkoStacks" } },
            { Champion.Gnar, new[] { "GnarWProc" } },
            { Champion.Kalista, new[] { "KalistaExpungeMarker" } },
            { Champion.Kennen, new[] { "kennenmarkofstorm" } },
            { Champion.Kindred, new[] { "KindredHitCharge", "kindredecharge" } },
            { Champion.TahmKench, new[] { "tahmkenchpdebuffcounter" } },
            { Champion.Tristana, new[] { "tristanaecharge" } },
            { Champion.Twitch, new[] { "TwitchDeadlyVenom" } },
            { Champion.Varus, new[] { "VarusWDebuff" } },
            { Champion.Vayne, new[] { "VayneSilverDebuff" } },
            { Champion.Velkoz, new[] { "VelkozResearchStack" } },
            { Champion.Vi, new[] { "ViWProc" } },
        };

        internal static void Initialize()
        {
            #region Loading Priorities

            ActiveMode = TargetSelectorMode.Auto;

            PriorityData = JsonConvert.DeserializeObject<Dictionary<string, int>>(Resources.Priorities);
            foreach (var enemy in EntityManager.Heroes.Enemies.Where(enemy => !Priorities.ContainsKey(enemy.Hero)))
            {
                if (PriorityData.ContainsKey(enemy.ChampionName))
                {
                    Priorities.Add(enemy.Hero, PriorityData[enemy.ChampionName]);
                }
                else
                {
                    Logger.Log(LogLevel.Warn, "[TargetSelector] '{0}' is not present in database! Using priority 1!", enemy.ChampionName);
                    Priorities.Add(enemy.Hero, 1);
                }
                var name = enemy.ChampionName;
                CurrentPriorities.Add(enemy.Hero, () => Menu[name].Cast<Slider>().CurrentValue);
            }

            #endregion

            #region Menu Creation

            Menu = MainMenu.AddMenu("Target Selector", "TargetSelector2.0");

            Menu.AddGroupLabel("Target Selector Mode");

            var modeBox = new ComboBox("Selected Mode:", Enum.GetValues(typeof (TargetSelectorMode)).Cast<TargetSelectorMode>().Select(o => o.ToString()));
            Menu.Add("modeBox", modeBox).OnValueChange += delegate { ActiveMode = (TargetSelectorMode) Enum.Parse(typeof (TargetSelectorMode), modeBox.SelectedText); };

            if (Priorities.Count > 0)
            {
                Menu.AddGroupLabel("Priorities");
                Menu.AddLabel("(Higher value means higher priority)");
                foreach (var champ in Priorities)
                {
                    Menu.Add(champ.Key.ToString(), new Slider(champ.Key.ToString(), champ.Value, 1, 5));
                }

                Menu.AddSeparator();
                Menu.Add("reset", new CheckBox("Reset to default priorities", false)).OnValueChange += delegate(ValueBase<bool> sender, ValueBase<bool>.ValueChangeArgs args)
                {
                    if (args.NewValue)
                    {
                        foreach (var champ in Priorities)
                        {
                            Menu[champ.Key.ToString()].Cast<Slider>().CurrentValue = champ.Value;
                        }
                        sender.CurrentValue = false;
                    }
                };
            }

            Menu.AddGroupLabel("Selected Target Settings");
            Menu.Add("selectedTargetEnabled", new CheckBox("Enable manual selected target"));
            Menu.Add("drawSelectedTarget", new CheckBox("Draw a circle around selected target"));
            Menu.Add("drawNotifications", new CheckBox("Draw notifications about selected target"));
            Menu.AddGroupLabel("Only Attack Selected Target Settings");
            Menu.Add("onlySelectedTargetEnabled", new CheckBox("Enable only attack selected target", false));
            Menu.Add("onlySelectedTargetKey", new KeyBind("Only attack selected target toggle", false, KeyBind.BindTypes.PressToggle, 'Z')).OnValueChange +=
                delegate(ValueBase<bool> sender, ValueBase<bool>.ValueChangeArgs args)
                {
                    if (OnlySelectedTargetEnabled)
                    {
                        if (DrawNotifications)
                        {
                            Notifications.Notifications.Show(args.NewValue
                                ? new SimpleNotification("Target Selector", "Only attack selected target enabled.")
                                : new SimpleNotification("Target Selector", "Only attack selected target disabled."));
                        }
                    }
                };

            #endregion

            #region Event Handling

            // TODO: Reenable when functioning
            /*Hud.OnTargetChange += delegate(HudChangeTargetEventArgs args)
            {
                var target = args.Target as AIHeroClient;
                if (target != null && target.IsEnemy)
                {
                    SelectedTarget = target;
                }
            };*/
            // TODO: Disable when above functioning
            Messages.RegisterEventHandler(delegate(Messages.LeftButtonDown args)
            {
                if (!MenuGUI.IsChatOpen && !MainMenu.IsMouseInside && SelectedEnabled)
                {
                    var target = EntityManager.Heroes.Enemies.FirstOrDefault(o => o.IsValidTarget() && o.IsInRange(Game.ActiveCursorPos, 100));
                    if (DrawNotifications)
                    {
                        if (target != null)
                        {
                            Notifications.Notifications.Show(new SimpleNotification("Target Selector", "Selected " + target.ChampionName + " as target."));
                        }
                        else if (SelectedTarget != null)
                        {
                            Notifications.Notifications.Show(new SimpleNotification("Target Selector", "Unselected " + SelectedTarget.ChampionName + " as target."));
                        }
                    }
                    SelectedTarget = target;
                }
            });
            Drawing.OnDraw += delegate
            {
                if (SelectedEnabled && DrawCircleAroundSelected && SelectedTarget.IsValidTarget())
                {
                    Circle.Draw(Color.Red, OnlySelectedTarget ? 120 : 80, OnlySelectedTarget ? 15 : 5, SelectedTarget.Position);
                }
            };

            #endregion
        }

        public static AIHeroClient GetTarget(IEnumerable<AIHeroClient> possibleTargets, DamageType damageType)
        {
            var aiHeroClients = possibleTargets.ToList();
            var validTargets = aiHeroClients.Where(h => !h.HasUndyingBuff(true)).ToList();
            if (validTargets.Count > 0)
            {
                aiHeroClients.Clear();
                aiHeroClients.AddRange(validTargets);
            }
            var selectedTargetIsValid = SelectedEnabled && SelectedTarget.IsValidTarget();
            if (selectedTargetIsValid)
            {
                if (OnlySelectedTarget)
                {
                    return SelectedTarget;
                }
            }
            switch (aiHeroClients.Count)
            {
                case 0:
                    return null;
                case 1:
                    return aiHeroClients[0];
            }
            if (selectedTargetIsValid)
            {
                if (aiHeroClients.Contains(SelectedTarget))
                {
                    return SelectedTarget;
                }
            }
            switch (ActiveMode)
            {
                case TargetSelectorMode.NearMouse:
                    return aiHeroClients.OrderBy(h => h.Distance(Game.ActiveCursorPos, true)).FirstOrDefault();
                case TargetSelectorMode.LessCast:
                    return aiHeroClients.OrderByDescending(h => GetReducedPriority(h) * Player.Instance.CalculateDamageOnUnit(h, DamageType.Magical, 100) / h.Health).FirstOrDefault();
                case TargetSelectorMode.LessAttack:
                    return aiHeroClients.OrderByDescending(h => GetReducedPriority(h) * Player.Instance.CalculateDamageOnUnit(h, DamageType.Physical, 100) / h.Health).FirstOrDefault();
                case TargetSelectorMode.HighestPriority:
                    return aiHeroClients.OrderByDescending(GetPriority).FirstOrDefault();
                case TargetSelectorMode.MostAbilityPower:
                    return aiHeroClients.OrderByDescending(unit => unit.TotalMagicalDamage).FirstOrDefault();
                case TargetSelectorMode.MostAttackDamage:
                    return aiHeroClients.OrderByDescending(unit => unit.TotalAttackDamage).FirstOrDefault();
                case TargetSelectorMode.LeastHealth:
                    return aiHeroClients.OrderBy(unit => unit.Health).FirstOrDefault();
                case TargetSelectorMode.Closest:
                    return aiHeroClients.OrderBy(unit => unit.Distance(Player.Instance, true)).FirstOrDefault();
                case TargetSelectorMode.MostStack:
                    return
                        aiHeroClients.OrderByDescending(
                            h =>
                                (BuffStackNames.Sum(pair => pair.Key == Player.Instance.Hero || pair.Key == Champion.Unknown ? pair.Value.Sum(stack => Math.Max(h.GetBuffCount(stack), 0)) : 0) + 1) *
                                GetReducedPriority(h) * Player.Instance.CalculateDamageOnUnit(h, damageType == DamageType.Magical ? DamageType.Magical : DamageType.Physical, 100) / h.Health)
                            .FirstOrDefault();
                case TargetSelectorMode.Auto:
                    return
                        aiHeroClients.OrderByDescending(
                            h => GetReducedPriority(h) * Player.Instance.CalculateDamageOnUnit(h, damageType == DamageType.Magical ? DamageType.Magical : DamageType.Physical, 100) / h.Health)
                            .FirstOrDefault();
            }

            return null;
        }

        public static AIHeroClient GetTarget(float range, DamageType damageType, Vector3? source = null, bool addBoundingRadius = false)
        {
            var sourcePosition = source ?? Player.Instance.ServerPosition;
            if (SelectedEnabled && SelectedTarget.IsValidTarget())
            {
                if (OnlySelectedTarget)
                {
                    return SelectedTarget;
                }
                if (sourcePosition.IsInRange(SelectedTarget, range * 1.15f))
                {
                    return SelectedTarget;
                }
            }
            return GetTarget(EntityManager.Heroes.Enemies.Where(h => h.IsValidTarget() && sourcePosition.IsInRange(h, range + (addBoundingRadius ? h.BoundingRadius : 0))), damageType);
        }

        internal static float GetReducedPriority(AIHeroClient target)
        {
            var priority = GetPriority(target);
            switch (priority)
            {
                case 5:
                    return 2.5f;
                case 4:
                    return 2f;
                case 3:
                    return 1.75f;
                case 2:
                    return 1.5f;
                default:
                    return 1f;
            }
        }

        public static int GetPriority(AIHeroClient target)
        {
            if (target == null)
            {
                return 0;
            }
            if (target.IsAlly)
            {
                if (PriorityData.ContainsKey(target.ChampionName))
                {
                    return PriorityData[target.ChampionName];
                }
            }
            return CurrentPriorities[target.Hero]();
        }
    }
}
