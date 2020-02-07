using System;
using System.Collections.Generic;
using System.Linq;
using EloBuddy.SDK.Constants;
using EloBuddy.SDK.Events;
using EloBuddy.SDK.Menu;
using EloBuddy.SDK.Menu.Values;
using EloBuddy.SDK.Notifications;
using EloBuddy.SDK.Rendering;
using EloBuddy.SDK.Utils;
using SharpDX;

namespace EloBuddy.SDK
{
    public static class Orbwalker
    {
        internal const int MinionsRangeSqr = 2250000; // 1500 * 1500
        internal const int TurretRangeSqr = 688900; // 830 * 830

        [Flags]
        public enum ActiveModes
        {
            None = 0x00,
            Combo = 0x01,
            Harass = 0x02,
            LastHit = 0x04,
            JungleClear = 0x08,
            LaneClear = 0x10,
            Flee = 0x20
        }

        internal enum TargetTypes
        {
            Hero,
            JungleMob,
            LaneMinion,
            Structure
        }

        #region Events

        public class PreAttackArgs : EventArgs
        {
            /// <summary>
            /// Gets or sets the process state of the event. When set to false, the attack attempt will be cancelled.
            /// </summary>
            public bool Process { get; set; }

            public AttackableUnit Target { get; private set; }

            public PreAttackArgs(AttackableUnit target)
            {
                Process = true;
                Target = target;
            }
        }

        public class UnkillableMinionArgs : EventArgs
        {
            public float RemainingHealth { get; internal set; }
        }

        public delegate Vector3? OrbwalkPositionDelegate();

        public delegate void PreAttackHandler(AttackableUnit target, PreAttackArgs args);

        public delegate void AttackHandler(AttackableUnit target, EventArgs args);

        public delegate void PostAttackHandler(AttackableUnit target, EventArgs args);

        public delegate void UnkillableMinionHandler(Obj_AI_Base target, UnkillableMinionArgs args);

        public static event PreAttackHandler OnPreAttack;
        public static event AttackHandler OnAttack;
        public static event PostAttackHandler OnPostAttack;
        public static event UnkillableMinionHandler OnUnkillableMinion;

        internal static void TriggerPreAttackEvent(AttackableUnit target, PreAttackArgs args)
        {
            if (OnPreAttack != null)
            {
                NotifyEventListeners("OnPreAttack", OnPreAttack.GetInvocationList(), target, args);
            }
        }

        internal static void TriggerAttackEvent(AttackableUnit target, EventArgs args = null)
        {
            if (OnAttack != null)
            {
                NotifyEventListeners("OnAttack", OnAttack.GetInvocationList(), target, args ?? EventArgs.Empty);
            }
        }

        internal static void TriggerPostAttackEvent(AttackableUnit target, EventArgs args = null)
        {
            if (OnPostAttack != null)
            {
                NotifyEventListeners("OnPostAttack", OnPostAttack.GetInvocationList(), target, args ?? EventArgs.Empty);
            }
        }

        internal static void TriggerUnkillableMinionEvent(Obj_AI_Base target, UnkillableMinionArgs args)
        {
            if (OnUnkillableMinion != null)
            {
                NotifyEventListeners("OnUnkillableMinion", OnUnkillableMinion.GetInvocationList(), target, args);
            }
        }

        internal static void NotifyEventListeners(string eventName, Delegate[] invocationList, params object[] args)
        {
            foreach (var listener in invocationList)
            {
                try
                {
                    listener.DynamicInvoke(args);
                }
                catch (Exception e)
                {
                    Logger.Exception("Failed to notify Orbwalker.{0} event listener!", e, eventName);
                }
            }
        }

        #endregion

        private static int _lastAutoAttackSent;
        private static bool _autoAttackStarted;

        internal static bool _waitingPostAttackEvent;
        internal static bool _waitingForAutoAttackReset;
        private static float _lastCastEndTime;
        private static bool _setCastEndTime;
        private static bool _autoAttackCompleted;

        public static bool IsMelee
        {
            get
            {
                return Player.Instance.IsMelee || Player.Instance.Hero == Champion.Azir || Player.Instance.Hero == Champion.Thresh || Player.Instance.Hero == Champion.Velkoz ||
                       (Player.Instance.Hero == Champion.Viktor && Player.Instance.HasBuff("viktorpowertransferreturn"));
            }
        }
        public static bool IsRanged
        {
            get { return !IsMelee; }
        }

        public static ActiveModes ActiveModesFlags { get; set; }

        public static OrbwalkPositionDelegate OverrideOrbwalkPosition { get; set; }
        public static Vector3 OrbwalkPosition
        {
            get
            {
                if (OverrideOrbwalkPosition != null)
                {
                    var pos = OverrideOrbwalkPosition();
                    if (pos.HasValue)
                    {
                        return pos.Value;
                    }
                }
                if (StickToTarget && LastTarget != null && !ActiveModesFlags.HasFlag(ActiveModes.Flee))
                {
                    var targetBase = LastTarget as Obj_AI_Base;
                    if (targetBase != null && (targetBase.IsMonster || targetBase.Type == GameObjectType.AIHeroClient) &&
                        Player.Instance.IsInRange(targetBase, Player.Instance.GetAutoAttackRange(targetBase) + 150) &&
                        Game.CursorPos.Distance(targetBase, true) < Game.CursorPos.Distance(Player.Instance, true) && targetBase.Path.Length > 0)
                    {
                        return targetBase.Path.Last();
                    }
                }
                return Game.CursorPos;
            }
        }
        public static int LastAutoAttack { get; internal set; }

        public static readonly Dictionary<Champion, string> AllowedMovementBuffs = new Dictionary<Champion, string>
        {
            { Champion.Lucian, "LucianR" },
            { Champion.Varus, "VarusQ" },
            { Champion.Vi, "ViQ" },
            { Champion.Vladimir, "VladimirE" },
            { Champion.Xerath, "XerathArcanopulseChargeUp" }
        };

        public static float AttackCastDelay
        {
            get
            {
                switch (Player.Instance.Hero)
                {
                    case Champion.TwistedFate:
                        if (Player.Instance.HasBuff("BlueCardPreAttack") || Player.Instance.HasBuff("RedCardPreAttack") || Player.Instance.HasBuff("GoldCardPreAttack"))
                        {
                            return 0.13f;
                        }
                        break;
                }
                return Player.Instance.AttackCastDelay;
            }
        }
        public static float AttackDelay
        {
            get
            {
                switch (Player.Instance.Hero)
                {
                    case Champion.Graves:
                        if (Player.Instance.HasBuff("GravesBasicAttackAmmo1"))
                        {
                            return 1.0740296828f * Player.Instance.AttackDelay - 0.7162381256175f;
                        }
                        break;
                }
                return Player.Instance.AttackDelay;
            }
        }

        public static bool CanMove
        {
            get
            {
                if (AutoAttacks.UnabortableAutoDatabase.Contains(Player.Instance.Hero) && Core.GameTickCount - LastAutoAttack >= Math.Min(MovementDelay, 100))
                {
                    return true;
                }
                if (Core.GameTickCount - _lastAutoAttackSent <= 100 + Game.Ping)
                {
                    return false;
                }
                if (Player.Instance.Spellbook.IsChanneling && (!AllowedMovementBuffs.ContainsKey(Player.Instance.Hero) || !Player.Instance.HasBuff(AllowedMovementBuffs[Player.Instance.Hero])))
                {
                    return false;
                }
                return CanBeAborted;
            }
        }

        public static bool CanAutoAttack
        {
            get
            {
                if (!Player.Instance.CanAttack && !_waitingForAutoAttackReset)
                {
                    return false;
                }
                if (Player.Instance.Spellbook.IsChanneling)
                {
                    return false;
                }
                switch (Player.Instance.Hero)
                {
                    case Champion.Jhin:
                        if (Player.Instance.HasBuff("JhinPassiveReload"))
                        {
                            return false;
                        }
                        break;
                    case Champion.Kalista:
                        if (Player.Instance.IsDashing())
                        {
                            return false;
                        }
                        break;
                }
                return CanIssueOrder;
            }
            internal set
            {
                if (value)
                {
                    _autoAttackStarted = false;
                    _autoAttackCompleted = true;
                    LastAutoAttack = 0;
                    LastMovementSent = 0;
                    _lastCastEndTime = 0;
                    _lastAutoAttackSent = 0;
                }
                else
                {
                    _autoAttackStarted = true;
                    _autoAttackCompleted = false;
                    _waitingPostAttackEvent = true;
                    _setCastEndTime = true;
                    LastAutoAttack = Core.GameTickCount;
                    if (FastKiting)
                    {
                        LastMovementSent -= MovementDelay - RandomOffset;
                        _lastAutoAttackSent -= 100 + Game.Ping;
                    }
                }
            }
        }
        private static bool CanIssueOrder
        {
            get
            {
                if (Core.GameTickCount - _lastAutoAttackSent <= 100 + Game.Ping)
                {
                    return false;
                }
                return Core.GameTickCount - LastAutoAttack + Game.Ping + 70 >= AttackDelay * 1000;
            }
        }

        public static bool CanBeAborted
        {
            get
            {
                var extraWindUpTime = ExtraWindUpTime;
                switch (Player.Instance.Hero)
                {
                    case Champion.Jinx:
                        extraWindUpTime += 150;
                        break;
                    case Champion.Rengar:
                        extraWindUpTime += 150;
                        break;
                }
                if (_autoAttackCompleted)
                {
                    return true;
                }
                if (Core.GameTickCount - LastAutoAttack >= AttackCastDelay * 1000 + extraWindUpTime + Game.Ping / 10f)
                {
                    return true;
                }
                if (IsMelee)
                {
                    if (_lastCastEndTime - Game.Time < 0f)
                    {
                        return true;
                    }
                }
                return false;
            }
        }

        public static bool IsAutoAttacking
        {
            get { return Player.Instance.Spellbook.IsAutoAttacking; }
        }

        public static bool GotAutoAttackReset { get; internal set; }

        public static AttackableUnit LastTarget { get; internal set; }
        public static AttackableUnit ForcedTarget { get; set; }

        internal static Menu.Menu Menu { get; set; }
        internal static Menu.Menu DrawingsMenu { get; set; }
        internal static Menu.Menu AdvancedMenu { get; set; }
        internal static Menu.Menu FarmingMenu { get; set; }

        #region Menu values

        public static int HoldRadius
        {
            get { return Menu["holdRadius" + Player.Instance.ChampionName].Cast<Slider>().CurrentValue; }
        }
        public static bool SupportMode
        {
            get { return Menu["supportMode" + Player.Instance.ChampionName].Cast<CheckBox>().CurrentValue; }
        }
        public static bool LaneClearAttackChamps
        {
            get { return Menu["laneClearChamps"].Cast<CheckBox>().CurrentValue; }
        }
        private static int? _customMovementDelay;
        public static int MovementDelay
        {
            get { return _customMovementDelay ?? Menu["delayMove"].Cast<Slider>().CurrentValue; }
            set { _customMovementDelay = value; }
        }
        public static int ExtraWindUpTime
        {
            get { return Menu["extraWindUpTime"].Cast<Slider>().CurrentValue; }
        }
        public static bool DrawRange
        {
            get { return DrawingsMenu["drawrange"].Cast<CheckBox>().CurrentValue; }
        }
        public static bool DrawAzirRange
        {
            get { return DrawingsMenu["drawAzirRange"].Cast<CheckBox>().CurrentValue; }
        }
        public static bool DrawEnemyRange
        {
            get { return DrawingsMenu["_drawEnemyRange"].Cast<CheckBox>().CurrentValue; }
        }
        public static bool DrawLastHitMarker
        {
            get { return DrawingsMenu["drawLasthit"].Cast<CheckBox>().CurrentValue; }
        }
        public static bool DrawDamageMarker
        {
            get { return DrawingsMenu["drawDamage"].Cast<CheckBox>().CurrentValue; }
        }
        public static bool DrawHoldRadius
        {
            get { return DrawingsMenu["drawHoldRadius"].Cast<CheckBox>().CurrentValue; }
        }

        internal static bool _disableMovement;
        public static bool DisableMovement
        {
            get { return _disableMovement || AdvancedMenu["disableMovement"].Cast<CheckBox>().CurrentValue; }
            set { _disableMovement = value; }
        }
        internal static bool _disableAttacking;
        public static bool DisableAttacking
        {
            get { return _disableAttacking || AdvancedMenu["disableAttacking"].Cast<CheckBox>().CurrentValue; }
            set { _disableAttacking = value; }
        }
        public static bool UseOnTick
        {
            get { return AdvancedMenu["useTick"].Cast<CheckBox>().CurrentValue; }
        }
        public static bool UseOnUpdate
        {
            get { return AdvancedMenu["useUpdate"].Cast<CheckBox>().CurrentValue; }
        }
        internal static bool UseTiamat
        {
            get
            {
                return Player.Instance.IsMelee && FarmingMenu["useTiamat"].Cast<CheckBox>().CurrentValue &&
                       Player.Instance.InventoryItems.HasItem(ItemId.Tiamat_Melee_Only, ItemId.Ravenous_Hydra_Melee_Only);
            }
        }
        internal static bool AttackObjects
        {
            get { return Menu["attackObjects"].Cast<CheckBox>().CurrentValue; }
        }
        internal static bool StickToTarget
        {
            get { return Player.Instance.IsMelee && Menu["stickToTarget"].Cast<CheckBox>().CurrentValue; }
        }
        internal static bool FastKiting
        {
            get { return Menu["fastKiting"].Cast<CheckBox>().CurrentValue; }
        }
        internal static bool CheckYasuoWall
        {
            get { return Menu["checkYasuoWall"].Cast<CheckBox>().CurrentValue; }
        }
        public static bool LastHitPriority
        {
            get { return FarmingMenu["lastHitPriority"].Cast<CheckBox>().CurrentValue; }
        }
        public static int ExtraFarmDelay
        {
            get { return FarmingMenu["extraFarmDelay"].Cast<Slider>().CurrentValue; }
        }

        internal static bool FreezePriority
        {
            get { return FarmingMenu["_freezePriority"].Cast<CheckBox>().CurrentValue; }
        }

        #endregion

        public static int LastMovementSent { get; internal set; }
        private static GameObjectOrder? _lastIssueOrderType;
        private static int? _lastIssueOrderTargetId;
        private static Vector3? _lastIssueOrderEndVector;
        private static Vector3? _lastIssueOrderStartVector;

        internal static Random Random { get; set; }
        internal static int RandomOffset { get; set; }

        internal static readonly Dictionary<int, Obj_AI_Base> LastTargetTurrets = new Dictionary<int, Obj_AI_Base>();
        internal static readonly ColorBGRA EnemyRangeColorNotInRange = new ColorBGRA(144, 238, 144, 100);
        internal static readonly ColorBGRA EnemyRangeColorInRange = new ColorBGRA(255, 0, 0, 100);

        #region Azir Fields and Properties

        internal static readonly Dictionary<int, Obj_AI_Minion> _azirSoldiers = new Dictionary<int, Obj_AI_Minion>();
        public static List<Obj_AI_Minion> AzirSoldiers
        {
            get { return _azirSoldiers.Values.ToList(); }
        }
        internal static readonly Dictionary<int, Obj_AI_Minion> _validAzirSoldiers = new Dictionary<int, Obj_AI_Minion>();
        public static List<Obj_AI_Minion> ValidAzirSoldiers
        {
            get { return _validAzirSoldiers.Values.ToList(); }
        }
        internal static Dictionary<int, bool> AzirSoldierPreDashStatus { get; set; }

        // TODO: Improve
        public const float AzirSoldierAutoAttackRange = 275;

        #endregion

        #region Illaoi Fields and Properties

        internal static bool IllaoiGhost { get; set; }

        #endregion

        internal static readonly List<AttackableUnit> EnemyStructures = new List<AttackableUnit>();

        internal static readonly List<Obj_AI_Minion> TickCachedMinions = new List<Obj_AI_Minion>();
        internal static readonly List<Obj_AI_Minion> TickCachedMonsters = new List<Obj_AI_Minion>();

        internal static void Initialize()
        {
            // Initialize properties
            Random = new Random(DateTime.Now.Millisecond);

            #region Hero Specific Properties

            switch (Player.Instance.Hero)
            {
                case Champion.Azir:
                    foreach (var soldier in ObjectManager.Get<Obj_AI_Minion>().Where(o => o.IsValid && o.IsAlly && o.Name == "AzirSoldier" &&
                                                                                          o.Buffs.Any(b => b.IsValid() && b.Caster.IsMe && b.Count == 1 && b.DisplayName == "azirwspawnsound")))
                    {
                        _azirSoldiers[soldier.NetworkId] = soldier;
                        if (Player.Instance.IsInRange(soldier, 950))
                        {
                            _validAzirSoldiers[soldier.NetworkId] = soldier;
                        }
                    }
                    AzirSoldierPreDashStatus = new Dictionary<int, bool>();

                    Obj_AI_Base.OnPlayAnimation += delegate(Obj_AI_Base sender, GameObjectPlayAnimationEventArgs args)
                    {
                        var soldier = sender as Obj_AI_Minion;
                        if (soldier != null && soldier.IsAlly && soldier.Name == "AzirSoldier")
                        {
                            switch (args.Animation)
                            {
                                case "Inactive":
                                    _validAzirSoldiers.Remove(soldier.NetworkId);
                                    if (AzirSoldierPreDashStatus.ContainsKey(soldier.NetworkId))
                                    {
                                        AzirSoldierPreDashStatus[soldier.NetworkId] = false;
                                    }
                                    break;
                                case "Reactivate":
                                    _validAzirSoldiers[soldier.NetworkId] = soldier;
                                    if (AzirSoldierPreDashStatus.ContainsKey(soldier.NetworkId))
                                    {
                                        AzirSoldierPreDashStatus[soldier.NetworkId] = true;
                                    }
                                    break;
                                case "Run":
                                    if (!AzirSoldierPreDashStatus.ContainsKey(soldier.NetworkId))
                                    {
                                        AzirSoldierPreDashStatus.Add(soldier.NetworkId, _validAzirSoldiers.Any(o => o.Value.IdEquals(soldier)));
                                    }
                                    _validAzirSoldiers.Remove(soldier.NetworkId);
                                    break;
                                case "Run_Exit":
                                    if (AzirSoldierPreDashStatus.ContainsKey(soldier.NetworkId) && AzirSoldierPreDashStatus[soldier.NetworkId])
                                    {
                                        _validAzirSoldiers[soldier.NetworkId] = soldier;
                                        AzirSoldierPreDashStatus.Remove(soldier.NetworkId);
                                    }
                                    break;
                                case "Death":
                                    _azirSoldiers.Remove(soldier.NetworkId);
                                    _validAzirSoldiers.Remove(soldier.NetworkId);
                                    AzirSoldierPreDashStatus.Remove(soldier.NetworkId);
                                    break;
                            }
                        }
                    };
                    Obj_AI_Base.OnBuffGain += delegate(Obj_AI_Base sender, Obj_AI_BaseBuffGainEventArgs args)
                    {
                        var soldier = sender as Obj_AI_Minion;
                        if (soldier != null && soldier.IsAlly && soldier.Name == "AzirSoldier" && args.Buff.Caster.IsMe &&
                            args.Buff.DisplayName == "azirwspawnsound")
                        {
                            _azirSoldiers[soldier.NetworkId] = soldier;
                            _validAzirSoldiers[soldier.NetworkId] = soldier;
                        }
                    };
                    break;
            }

            #endregion

            #region Illaoi Specific Properties

            IllaoiGhost = EntityManager.Heroes.Allies.Any(h => h.Hero == Champion.Illaoi);

            #endregion

            // Create the menu
            CreateMenu();

            // Gather enemy structures for attacking
            EnemyStructures.AddRange(ObjectManager.Get<AttackableUnit>().Where(o => o.IsEnemy && o.IsStructure()));

            // Listen to required events
            Game.OnTick += delegate
            {
                if (UseOnTick)
                {
                    OnTick();
                }
            };
            Game.OnUpdate += delegate
            {
                if (UseOnUpdate)
                {
                    OnTick();
                }
                OnUpdate();
            };
            GameObject.OnCreate += OnCreate;
            Obj_AI_Base.OnBasicAttack += OnBasicAttack;
            Obj_AI_Base.OnProcessSpellCast += OnProcessSpellCast;
            Obj_AI_Base.OnSpellCast += OnSpellCast;
            Spellbook.OnStopCast += OnStopCast;
            Drawing.OnDraw += OnDraw;
            //Special Resets
            if (AutoAttacks.DashAutoAttackResetSlotsDatabase.ContainsKey(Player.Instance.Hero))
            {
                var dashEndPosition = default(Vector3);
                if (AutoAttacks.AutoAttackResetAnimationName.ContainsKey(Player.Instance.Hero))
                {
                    Obj_AI_Base.OnPlayAnimation += delegate(Obj_AI_Base sender, GameObjectPlayAnimationEventArgs args)
                    {
                        if (sender.IsMe)
                        {
                            if (AutoAttacks.IsDashAutoAttackReset(Player.Instance, args))
                            {
                                Chat.Print("Reset Animation");
                                GotAutoAttackReset = true;
                                _waitingForAutoAttackReset = true;
                            }
                        }
                    };
                }
                Obj_AI_Base.OnProcessSpellCast += delegate(Obj_AI_Base sender, GameObjectProcessSpellCastEventArgs args)
                {
                    if (sender.IsMe)
                    {
                        if (AutoAttacks.IsDashAutoAttackReset(Player.Instance, args))
                        {
                            Chat.Print("Reset Spell");
                            GotAutoAttackReset = true;
                            _waitingForAutoAttackReset = true;
                        }
                    }
                };
                Game.OnUpdate += delegate
                {
                    if (dashEndPosition != default(Vector3) && _waitingForAutoAttackReset && Vector3.Distance(Player.Instance.Position, dashEndPosition) <= Player.Instance.BoundingRadius)
                    {
                        ResetAutoAttack();
                        _waitingForAutoAttackReset = false;
                        dashEndPosition = default(Vector3);
                    }
                };
                Obj_AI_Base.OnNewPath += delegate(Obj_AI_Base sender, GameObjectNewPathEventArgs args)
                {
                    if (sender.IsMe)
                    {
                        if (args.IsDash)
                        {
                            if (_waitingForAutoAttackReset)
                            {
                                dashEndPosition = args.Path.LastOrDefault();
                            }
                        }
                        else if (_waitingForAutoAttackReset)
                        {
                            ResetAutoAttack();
                            _waitingForAutoAttackReset = false;
                            dashEndPosition = default(Vector3);
                        }
                    }
                };
            }

            var minionBarOffset = new Vector2(36, 3);
            var barHeight = 6;
            var barWidth = 62;
            Drawing.OnEndScene += delegate
            {
                if (DrawDamageMarker)
                {
                    foreach (var minion in TickCachedMinions.Where(o => DamageOnMinions.ContainsKey(o.NetworkId) && o.VisibleOnScreen))
                    {
                        var position = minionBarOffset + minion.HPBarPosition + new Vector2(barWidth * Math.Min(GetAutoAttackDamage(minion) / minion.MaxHealth, 1), 0);
                        Line.DrawLine(System.Drawing.Color.Black, 2f, position, position + new Vector2(0, barHeight));
                    }
                }
            };

            if (Player.Instance.IsMelee)
            {
                const int rangeSqr = 160000; //400 * 400
                OnUnkillableMinion += delegate(Obj_AI_Base target, UnkillableMinionArgs args)
                {
                    if (ActiveModesFlags.HasFlag(ActiveModes.LastHit) || ActiveModesFlags.HasFlag(ActiveModes.LaneClear))
                    {
                        if (UseTiamat && Player.Instance.Distance(target, true) <= rangeSqr)
                        {
                            var damage = Player.Instance.GetItemDamage(target, ItemId.Tiamat_Melee_Only);
                            var health = Prediction.Health.GetPrediction(target, 200);
                            if (health <= damage)
                            {
                                foreach (var item in new[] { ItemId.Tiamat_Melee_Only, ItemId.Ravenous_Hydra_Melee_Only }.Where(Item.CanUseItem))
                                {
                                    Item.UseItem(item);
                                }
                            }
                        }
                    }
                };
            }

            // LastTarget clearing
            GameObject.OnDelete += delegate(GameObject sender, EventArgs args)
            {
                if (sender.IsStructure())
                {
                    EnemyStructures.RemoveAll(o => o.IdEquals(sender));
                }
                if (sender.IdEquals(LastHitMinion))
                {
                    LastHitMinion = null;
                }
                if (sender.IdEquals(PriorityLastHitWaitingMinion))
                {
                    PriorityLastHitWaitingMinion = null;
                }
                if (sender.IdEquals(LaneClearMinion))
                {
                    LaneClearMinion = null;
                }
                if (LastTarget.IdEquals(sender))
                {
                    LastTarget = null;
                }
                if (ForcedTarget.IdEquals(sender))
                {
                    ForcedTarget = null;
                }
            };
            Player.OnPostIssueOrder += delegate(Obj_AI_Base sender, PlayerIssueOrderEventArgs args)
            {
                if (sender.IsMe)
                {
                    _lastIssueOrderStartVector = sender.Position;
                    _lastIssueOrderEndVector = args.TargetPosition;
                    _lastIssueOrderType = args.Order;
                    _lastIssueOrderTargetId = args.Target != null ? args.Target.NetworkId : default(int);
                }
            };
        }

        internal static void CreateMenu()
        {
            #region Menu Creation

            // Create the menu
            Menu = MainMenu.AddMenu("Orbwalker", "Orbwalker");
            Menu.AddGroupLabel("Hotkeys");
            RegisterKeyBind(Menu.Add("combo", new KeyBind("Combo", false, KeyBind.BindTypes.HoldActive, 32)), ActiveModes.Combo);
            RegisterKeyBind(Menu.Add("harass", new KeyBind("Harass", false, KeyBind.BindTypes.HoldActive, 'C')), ActiveModes.Harass);
            RegisterKeyBind(Menu.Add("laneclear", new KeyBind("LaneClear", false, KeyBind.BindTypes.HoldActive, 'V')), ActiveModes.LaneClear);
            RegisterKeyBind(Menu.Add("jungleclear", new KeyBind("JungleClear", false, KeyBind.BindTypes.HoldActive, 'V')), ActiveModes.JungleClear);
            RegisterKeyBind(Menu.Add("lasthit", new KeyBind("LastHit", false, KeyBind.BindTypes.HoldActive, 'X')), ActiveModes.LastHit);
            RegisterKeyBind(Menu.Add("flee", new KeyBind("Flee", false, KeyBind.BindTypes.HoldActive, 'T')), ActiveModes.Flee);

            #region Extra Settings Menu

            Menu.AddGroupLabel("Extra settings");
            Menu.Add("attackObjects", new CheckBox("Attack other objects"));
            Menu.Add("laneClearChamps", new CheckBox("Attack champions in LaneClear mode"));
            Menu.Add("stickToTarget", new CheckBox("Stick to target (only melee)", false));
            Menu.Add("fastKiting", new CheckBox("Fast kiting"));
            var supportHeroes = new HashSet<Champion>
            {
                Champion.Alistar,
                Champion.Bard,
                Champion.Braum,
                Champion.Janna,
                Champion.Karma,
                Champion.Leona,
                Champion.Lulu,
                Champion.Morgana,
                Champion.Nami,
                Champion.Sona,
                Champion.Soraka,
                Champion.TahmKench,
                Champion.Taric,
                Champion.Thresh,
                Champion.Zilean,
                Champion.Zyra
            };
            Menu.Add("supportMode" + Player.Instance.ChampionName, new CheckBox("Support Mode", supportHeroes.Contains(Player.Instance.Hero)));
            Menu.Add("checkYasuoWall", new CheckBox("Don't attack Yasuo's WindWall"));
            Menu.Add("holdRadius" + Player.Instance.ChampionName, new Slider("Hold radius", 100, 0, Math.Max(100, (int) (Player.Instance.GetAutoAttackRange() / 2))));
            Menu.Add("delayMove", new Slider("Delay between movements in milliseconds", 220 + Random.Next(40), 0, 1000));
            Menu.Add("extraWindUpTime", new Slider("Extra windup time", 35, 0, 200));
            Menu.AddLabel("Tip: If your autoattack is getting cancelled too much, you can fix it by adding more extra windup time.");

            #endregion

            #region Farming Menu

            FarmingMenu = Menu.AddSubMenu("Farming", "Farming " + Player.Instance.ChampionName);
            FarmingMenu.AddGroupLabel("Misc Settings");
            FarmingMenu.Add("lastHitPriority", new CheckBox("Priorize LastHit over Harass"));
            FarmingMenu.Add("_freezePriority", new CheckBox("Priorize Freeze over Push", false));
            FarmingMenu.Add("extraFarmDelay", new Slider("Extra farm delay", 0, -80, 80));
            FarmingMenu.AddGroupLabel("Masteries Settings");
            FarmingMenu.Add("doubleEdgedSword", new CheckBox("Double-Edged Sword (Ferocity Tree)", false));
            FarmingMenu.Add("assassin", new CheckBox("Assassin (Cunning Tree)", false));
            FarmingMenu.Add("savagery", new Slider("Savagery (Cunning Tree)", 0, 0, 5));
            FarmingMenu.Add("merciless", new Slider("Merciless (Cunning Tree)", 0, 0, 5));
            FarmingMenu.AddGroupLabel("Item Settings");
            FarmingMenu.Add("useTiamat", new CheckBox("Use Tiamat/Hydra on unkillable minions"));

            #endregion

            #region Drawings Menu

            DrawingsMenu = Menu.AddSubMenu("Drawings");
            DrawingsMenu.AddGroupLabel("Drawings");
            DrawingsMenu.Add("drawrange", new CheckBox("Auto attack range"));
            if (Player.Instance.Hero == Champion.Azir)
            {
                DrawingsMenu.Add("drawAzirRange", new CheckBox("Azir soldier attack range"));
            }
            DrawingsMenu.Add("_drawEnemyRange", new CheckBox("Enemy auto attack range"));
            DrawingsMenu.Add("drawHoldRadius", new CheckBox("Hold radius (see main menu)", false));
            DrawingsMenu.Add("drawLasthit", new CheckBox("Lasthittable minions"));
            DrawingsMenu.Add("drawDamage", new CheckBox("Damage on minions"));

            #endregion

            #region Advanced

            AdvancedMenu = Menu.AddSubMenu("Advanced");

            AdvancedMenu.AddGroupLabel("Orbwalker control");
            AdvancedMenu.Add("disableAttacking", new CheckBox("Disable auto attacking", false));
            AdvancedMenu.Add("disableMovement", new CheckBox("Disable moving to mouse", false));
            AdvancedMenu.AddGroupLabel("Update event listening");
            var useTick = new CheckBox("Use Game.OnTick (more fps)");
            var useUpdate = new CheckBox("Update Game.OnUpdate (faster reaction)", false);

            useTick.OnValueChange += delegate(ValueBase<bool> sender, ValueBase<bool>.ValueChangeArgs args)
            {
                if (args.NewValue)
                {
                    useUpdate.CurrentValue = false;
                }
                else if (!useUpdate.CurrentValue)
                {
                    useTick.CurrentValue = true;
                }
            };
            useUpdate.OnValueChange += delegate(ValueBase<bool> sender, ValueBase<bool>.ValueChangeArgs args)
            {
                if (args.NewValue)
                {
                    useTick.CurrentValue = false;
                }
                else if (!useTick.CurrentValue)
                {
                    useUpdate.CurrentValue = true;
                }
            };

            AdvancedMenu.Add("useTick", useTick);
            AdvancedMenu.Add("useUpdate", useUpdate);

            #endregion

            #endregion
        }

        /// <summary>
        /// Resets the auto attack internally. This will not make it actually reset it in game!
        /// </summary>
        public static void ResetAutoAttack()
        {
            CanAutoAttack = true;
            GotAutoAttackReset = true;
        }

        internal static void Clear()
        {
            // Don't store junk
            if (LastTarget != null && !LastTarget.IsValidTarget())
            {
                LastTarget = null;
            }

            // Prevent getting stuck
            if (!CanMove && LastTarget == null)
            {
                // Allow moving
                _autoAttackCompleted = true;
            }

            // Refresh sets
            foreach (var type in Enum.GetValues(typeof(TargetMinionType)).Cast<TargetMinionType>())
            {
                CurrentMinionsLists[type] = new List<Obj_AI_Minion>();
            }

            // Validate current minions
            foreach (var entry in CurrentMinions.Where(entry => !entry.Value.IsValidTarget() || !Player.Instance.IsInAutoAttackRange(entry.Value)).ToArray())
            {
                // Set invalid minion to null
                CurrentMinions[entry.Key] = null;
            }
            _precalculatedDamage = null;
            TickCachedMonsters.Clear();
            TickCachedMinions.Clear();
            CurrentMinionValues.Clear();
            DamageOnMinions.Clear();
        }

        internal static void OnTick()
        {
            Clear();
            // Check if an active mode is set
            if (ActiveModesFlags != ActiveModes.None || DrawLastHitMarker || DrawDamageMarker)
            {
                // Recalculate lasthittable minions
                TickCachedMinions.AddRange(EntityManager.MinionsAndMonsters.EnemyMinions.Where(o => Player.Instance.Distance(o, true) <= MinionsRangeSqr));
                _onlyLastHit = !ActiveModesFlags.HasFlag(ActiveModes.LaneClear) && !ActiveModesFlags.HasFlag(ActiveModes.JungleClear);
                if (ActiveModesFlags != ActiveModes.None || DrawLastHitMarker)
                {
                    RecalculateLasthittableMinions();
                }
                else if (DrawDamageMarker)
                {
                    foreach (var minion in TickCachedMinions)
                    {
                        GetAutoAttackDamage(minion);
                    }
                }
            }
            // Check if an active mode is set
            if (ActiveModesFlags != ActiveModes.None)
            {
                // We don't need to get jungleminions if we are in lane
                if (LastHitMinion == null && !ShouldWait && LaneClearMinion == null)
                {
                    TickCachedMonsters.AddRange(
                        EntityManager.MinionsAndMonsters.Monsters.Where(Player.Instance.IsInAutoAttackRange)
                            .OrderByDescending(o => o.MaxHealth));
                }
            }
        }

        internal static void TriggerOnPostAttack()
        {
            if (_waitingPostAttackEvent && CanBeAborted)
            {
                TriggerPostAttackEvent(LastTarget, EventArgs.Empty);
                GotAutoAttackReset = false;
                _waitingPostAttackEvent = false;
            }
        }

        internal static void OnUpdate()
        {
            if (_setCastEndTime && Player.Instance.Spellbook.IsAutoAttacking)
            {
                var castEndTime = Player.Instance.Spellbook.CastEndTime;
                if (castEndTime > 0)
                {
                    _lastCastEndTime = castEndTime;
                    _setCastEndTime = false;
                }
            }
            TriggerOnPostAttack();
            // Check if an active mode is set
            if (ActiveModesFlags != ActiveModes.None)
            {
                OrbwalkTo(OrbwalkPosition);
            }
        }

        #region Orbwalking

        /// <summary>
        /// Orbwalks to the desired position, tries to autoattack if it's possible, it moves if isn't possible.
        /// </summary>
        public static void OrbwalkTo(Vector3 position)
        {
            if (Chat.IsOpen /*|| Shop.IsOpen || (Player.Instance.Spellbook.IsCastingSpell && !Player.Instance.Spellbook.IsAutoAttacking)*/)
            {
                return;
            }
            if (!DisableAttacking && CanAutoAttack /* && !Player.Instance.HasBuffOfType(BuffType.Blind)*/)
            {
                var target = GetTarget();
                // Validate the target
                if (target != null)
                {
                    // Create a new event arg instance
                    var args = new PreAttackArgs(target);

                    // Trigger pre attack event
                    TriggerPreAttackEvent(target, args);

                    // Validate processing of the args
                    if (args.Process)
                    {
                        if (_lastIssueOrderType.HasValue && _lastIssueOrderType == GameObjectOrder.AttackUnit && _lastAutoAttackSent > 0 && _lastIssueOrderTargetId.HasValue &&
                            _lastIssueOrderTargetId.Value == args.Target.NetworkId && _lastIssueOrderStartVector.HasValue &&
                            _lastIssueOrderStartVector.Value == Player.Instance.Position)
                        {
                            // Set the new "last" target
                            _autoAttackStarted = false;
                            _lastAutoAttackSent = Core.GameTickCount;
                            LastTarget = args.Target;
                            return;
                        }
                        // Issue the attack
                        if (Player.IssueOrder(GameObjectOrder.AttackUnit, args.Target))
                        {
                            // Set the new "last" target
                            _autoAttackStarted = false;
                            _lastAutoAttackSent = Core.GameTickCount;
                            LastTarget = args.Target;
                            return;
                        }
                    }
                }
            }
            if (!DisableMovement)
            {
                // Move instead of attacking
                MoveTo(position);
            }
        }

        /// <summary>
        /// Moves to the desired position, checking holdradius distance.
        /// </summary>
        public static void MoveTo(Vector3 position)
        {
            // Gernerally no movement
            if (!CanMove || Core.GameTickCount - LastMovementSent + RandomOffset <= MovementDelay ||
                (!FastKiting && Core.GameTickCount - _lastAutoAttackSent + RandomOffset <= MovementDelay))
            {
                return;
            }

            var movePosition = Player.Instance.Distance(position, true) < 100.Pow()
                ? Player.Instance.Position.Extend(position, 100).To3DWorld()
                : position;
            GameObjectOrder order;

            if (HoldRadius > 0)
            {
                if (position.Distance(Player.Instance, true) > HoldRadius.Pow())
                {
                    order = GameObjectOrder.MoveTo;
                }
                else
                {
                    order = GameObjectOrder.Stop;
                }
            }
            else
            {
                order = GameObjectOrder.MoveTo;
            }

            if (_lastIssueOrderType.HasValue && _lastIssueOrderType == order)
            {
                switch (order)
                {
                    case GameObjectOrder.Stop:
                        if (_lastIssueOrderStartVector.HasValue && _lastIssueOrderStartVector.Value == Player.Instance.Position)
                        {
                            return;
                        }
                        return;
                    case GameObjectOrder.MoveTo:
                        if (_lastIssueOrderEndVector.HasValue && _lastIssueOrderEndVector.Value == movePosition)
                        {
                            if (Player.Instance.IsMoving)
                            {
                                return;
                            }
                        }
                        break;
                }
            }
            // Issue the order
            if (Player.IssueOrder(order, movePosition))
            {
                LastMovementSent = Core.GameTickCount;
                RandomOffset = Random.Next(30) - 15;
            }
        }

        #endregion

        #region Attack Event Handling

        internal static void _OnAttack(GameObjectProcessSpellCastEventArgs args)
        {
            // Update internally marked auto attack
            CanAutoAttack = false;

            // Get the target and cast it to AttackableUnit
            var target = args != null ? args.Target as AttackableUnit : LastTarget;

            // Validate the target
            if (target != null)
            {
                // Update the last target
                LastTarget = target;

                // Trigger the attack event
                TriggerAttackEvent(target, EventArgs.Empty);
            }
        }

        internal static void OnBasicAttack(Obj_AI_Base sender, GameObjectProcessSpellCastEventArgs args)
        {
            if (sender.IsMe)
            {
                _OnAttack(args);
            }
            else if (sender is Obj_AI_Turret && args.Target is Obj_AI_Base)
            {
                LastTargetTurrets[sender.NetworkId] = (Obj_AI_Base) args.Target;
            }
        }

        internal static void OnProcessSpellCast(Obj_AI_Base sender, GameObjectProcessSpellCastEventArgs args)
        {
            if (sender.IsMe)
            {
                _lastIssueOrderStartVector = null;
                _lastIssueOrderEndVector = null;
                _lastIssueOrderTargetId = null;
                _lastIssueOrderType = null;
                if (args.IsAutoAttack())
                {
                    _OnAttack(args);
                }
                else if (AutoAttacks.IsAutoAttackReset(Player.Instance, args) && Math.Abs(args.SData.CastTime) < float.Epsilon)
                {
                    Core.DelayAction(ResetAutoAttack, 30);
                }
            }
        }

        internal static void OnSpellCast(Obj_AI_Base sender, GameObjectProcessSpellCastEventArgs args)
        {
            if (sender.IsMe)
            {
                if (args.IsAutoAttack())
                {
                    // Mark as attack completed
                    if (Game.Ping < 50)
                    {
                        Core.DelayAction(delegate
                        {
                            _autoAttackCompleted = true;
                            TriggerOnPostAttack();
                        }, 50 - Game.Ping);
                    }
                    else
                    {
                        _autoAttackCompleted = true;
                        TriggerOnPostAttack();
                    }
                }
                else if (AutoAttacks.IsAutoAttackReset(Player.Instance, args))
                {
                    Core.DelayAction(ResetAutoAttack, 30);
                }
            }
        }

        internal static void OnCreate(GameObject sender, EventArgs args)
        {
            var missile = sender as MissileClient;
            if (missile != null)
            {
                var caster = missile.SpellCaster;
                if (caster != null && caster.IsMe && missile.IsAutoAttack())
                {
                    // Mark as attack completed
                    _autoAttackCompleted = true;
                    TriggerOnPostAttack();
                }
            }
        }

        internal static void OnStopCast(Obj_AI_Base sender, SpellbookStopCastEventArgs args)
        {
            // Validate our player and the stop cast
            if (sender.IsMe && (args.DestroyMissile || args.StopAnimation) && (_lastCastEndTime - Game.Time > 0 || (IsRanged && !_autoAttackCompleted)))
            {
                // Reset the auto attack internally because we stopped a cast of an auto attack
                // ResetAutoAttack();
            }
        }

        #endregion

        #region Target Aquiring

        /// <summary>
        /// Returns the target that the orbwalker will focus.
        /// </summary>
        public static AttackableUnit GetTarget()
        {
            // Return forced targets if set
            if (ForcedTarget != null && ForcedTarget.IsValidTarget())
            {
                return Player.Instance.IsInAutoAttackRange(ForcedTarget) ? ForcedTarget : null;
            }

            // Return null if the mode is none
            if (ActiveModesFlags == ActiveModes.None)
            {
                return null;
            }

            // Create list
            var potentialTargets = new List<AttackableUnit>();
            // Check for the modes from the most important one to the least imprtant one
            foreach (var mode in Enum.GetValues(typeof(ActiveModes)).Cast<ActiveModes>().Where(mode => mode != ActiveModes.None && ActiveModesFlags.HasFlag(mode)))
            {
                AttackableUnit structure;
                switch (mode)
                {
                    case ActiveModes.Combo:
                        // Surrounding enemy champion
                        potentialTargets.Add(GetTarget(TargetTypes.Hero));
                        break;

                    case ActiveModes.Harass:
                        structure = GetTarget(TargetTypes.Structure);
                        if (structure != null)
                        {
                            if (!LastHitPriority)
                            {
                                potentialTargets.Add(structure);
                            }
                            potentialTargets.Add(GetTarget(TargetTypes.LaneMinion));
                            if (LastHitPriority && !ShouldWait)
                            {
                                potentialTargets.Add(structure);
                            }
                        }
                        else
                        {
                            if (!LastHitPriority)
                            {
                                potentialTargets.Add(GetTarget(TargetTypes.Hero));
                            }

                            // Jungle mob or lane minion which are about to die
                            potentialTargets.Add(GetTarget(TargetTypes.JungleMob) ?? GetTarget(TargetTypes.LaneMinion));

                            // Surrounding enemy champion
                            if (LastHitPriority && !ShouldWait)
                            {
                                potentialTargets.Add(GetTarget(TargetTypes.Hero));
                            }
                        }
                        break;

                    case ActiveModes.LastHit:
                        // Only lane minions which are about to die
                        potentialTargets.Add(GetTarget(TargetTypes.LaneMinion));
                        break;

                    case ActiveModes.JungleClear:
                        // Jungle mob
                        potentialTargets.Add(GetTarget(TargetTypes.JungleMob));
                        break;

                    case ActiveModes.LaneClear:
                        structure = GetTarget(TargetTypes.Structure);
                        var minion = GetTarget(TargetTypes.LaneMinion);
                        if (structure != null)
                        {
                            if (!LastHitPriority)
                            {
                                potentialTargets.Add(structure);
                            }
                            if (minion.IdEquals(LastHitMinion))
                            {
                                potentialTargets.Add(minion);
                            }
                            if (LastHitPriority && !ShouldWait)
                            {
                                potentialTargets.Add(structure);
                            }
                        }
                        else
                        {
                            if (!LastHitPriority && LaneClearAttackChamps)
                            {
                                potentialTargets.Add(GetTarget(TargetTypes.Hero));
                            }
                            if (minion.IdEquals(LastHitMinion))
                            {
                                potentialTargets.Add(minion);
                            }
                            if (LastHitPriority && LaneClearAttackChamps && !ShouldWait)
                            {
                                potentialTargets.Add(GetTarget(TargetTypes.Hero));
                            }
                            if (minion.IdEquals(LaneClearMinion))
                            {
                                potentialTargets.Add(minion);
                            }
                        }
                        break;
                }
            }

            // Remove invalid targets
            potentialTargets.RemoveAll(o => o == null);

            return potentialTargets.FirstOrDefault();
        }

        internal static bool SupportModeNotificationShown;

        internal static AttackableUnit GetTarget(TargetTypes targetType)
        {
            switch (targetType)
            {
                case TargetTypes.Hero:
                    //First we check if we can hit with azir's soldiers.
                    if (Player.Instance.Hero == Champion.Azir)
                    {
                        var target = TargetSelector.GetTarget(EntityManager.Heroes.Enemies.Where(h => h.IsValidTarget() && ValidAzirSoldiers.Any(i => i.IsInAutoAttackRange(h))), DamageType.Magical);
                        if (target != null)
                        {
                            return target;
                        }
                    }
                    //This fixes Caitlyn's extra range
                    return
                        TargetSelector.GetTarget(
                            EntityManager.Heroes.Enemies.Where(
                                h =>
                                    h.IsValidTarget() && Player.Instance.IsInAutoAttackRange(h) &&
                                    (!IsRanged || !CheckYasuoWall || (Prediction.Position.Collision.GetYasuoWallCollision(Player.Instance.ServerPosition, h.ServerPosition) == Vector3.Zero))),
                            DamageType.Physical) ??
                        GetIllaoiGhost();

                case TargetTypes.JungleMob:
                    return TickCachedMonsters.FirstOrDefault();

                case TargetTypes.LaneMinion:
                    var supportMode = SupportMode && EntityManager.Heroes.Allies.Any(i => !i.IsMe && i.IsValidTarget(1050));
                    if (supportMode && !SupportModeNotificationShown)
                    {
                        Notifications.Notifications.Show(new SimpleNotification("Orbwalker", "Support mode is enabled"));
                        SupportModeNotificationShown = true;
                    }
                    var lastHit = !supportMode || Player.Instance.GetBuffCount("TalentReaper") > 0;
                    if (lastHit)
                    {
                        if (LastHitMinion != null)
                        {
                            // Check if AlmostLasthittableMinion it's a better minion to kill
                            // <- This can be replaced with .MaxHealth but it's better only waiting siege minions
                            if (PriorityLastHitWaitingMinion != null && !PriorityLastHitWaitingMinion.IdEquals(LastHitMinion) && PriorityLastHitWaitingMinion.IsSiegeMinion())
                            {
                                return null;
                            }
                            // We kill the minion if there isn't a better one.
                            return LastHitMinion;
                        }
                    }
                    if (ShouldWait || _onlyLastHit)
                    {
                        return null;
                    }
                    return !supportMode ? LaneClearMinion : null;
                case TargetTypes.Structure:
                    return
                        EnemyStructures.Where(o => o.IsValid && !o.IsDead && o.IsTargetable && Player.Instance.Distance(o, true) <= Player.Instance.GetAutoAttackRange(o).Pow())
                            .OrderByDescending(o => o.MaxHealth)
                            .FirstOrDefault();
            }

            return null;
        }

        #region Illaoi Target Aquiring

        internal static AttackableUnit GetIllaoiGhost()
        {
            if (!IllaoiGhost)
            {
                return null;
            }

            return ObjectManager.Get<Obj_AI_Minion>().FirstOrDefault(o => o.IsValidTarget() && o.IsEnemy && o.HasBuff("illaoiespirit") && Player.Instance.IsInAutoAttackRange(o));
        }

        #endregion

        #endregion

        #region Lasthit Calculations

        private static readonly Dictionary<int, float> DamageOnMinions = new Dictionary<int, float>();
        private static PrecalculatedAutoAttackDamage _precalculatedDamage;
        private const int ShouldWaitTime = 400;
        public static bool ShouldWait
        {
            get { return Core.GameTickCount - _lastShouldWait <= ShouldWaitTime || PriorityLastHitWaitingMinion != null; }
        }
        private static int _lastShouldWait;
        private static bool _onlyLastHit;

        private static float GetAutoAttackDamage(Obj_AI_Minion minion)
        {
            if (_precalculatedDamage == null)
            {
                // Precalculate static auto attack damage
                _precalculatedDamage = Player.Instance.GetStaticAutoAttackDamage(true);
            }
            if (!DamageOnMinions.ContainsKey(minion.NetworkId))
            {
                DamageOnMinions[minion.NetworkId] = Player.Instance.GetAutoAttackDamage(minion, _precalculatedDamage);
            }
            return DamageOnMinions[minion.NetworkId];
        }
        
        private static int GetAttackCastDelay(AttackableUnit target)
        {
            if (Player.Instance.Hero == Champion.Azir)
            {
                var soldier = AzirSoldiers.FirstOrDefault(i => i.IsInAutoAttackRange(target));
                if (soldier != null)
                {
                    return (int)(soldier.AttackCastDelay * 1000);
                }
            }

            return (int) (AttackCastDelay * 1000);
        }

        private static int GetAttackDelay(AttackableUnit target)
        {
            if (Player.Instance.Hero == Champion.Azir)
            {
                var soldier = AzirSoldiers.FirstOrDefault(i => i.IsInAutoAttackRange(target));
                if (soldier != null)
                {
                    return (int) (soldier.AttackDelay * 1000);
                }
            }
            return (int) (AttackDelay * 1000);
        }

        private static int GetMissileTravelTime(Obj_AI_Base minion)
        {
            return IsMelee
                ? 0
                : (int) Math.Max(0, 1000 * Prediction.Health.GetPredictedMissileTravelTime(minion, Player.Instance.ServerPosition, AttackCastDelay, Player.Instance.BasicAttack.MissileSpeed));
        }

        internal static bool HasTurretTargetting(this Obj_AI_Minion minion)
        {
            return LastTargetTurrets.Any(o => o.Value.IdEquals(minion));
        }

        internal enum TargetMinionType
        {
            LastHit,
            PriorityLastHitWaiting,
            LaneClear,
            UnKillable
        }

        internal static readonly Dictionary<TargetMinionType, List<Obj_AI_Minion>> CurrentMinionsLists = new Dictionary<TargetMinionType, List<Obj_AI_Minion>>();

        public static List<Obj_AI_Minion> LastHitMinionsList
        {
            get { return CurrentMinionsLists.ContainsKey(TargetMinionType.LastHit) ? new List<Obj_AI_Minion>(CurrentMinionsLists[TargetMinionType.LastHit]) : new List<Obj_AI_Minion>(); }
        }
        public static List<Obj_AI_Minion> PriorityLastHitWaitingMinionsList
        {
            get
            {
                return CurrentMinionsLists.ContainsKey(TargetMinionType.PriorityLastHitWaiting)
                    ? new List<Obj_AI_Minion>(CurrentMinionsLists[TargetMinionType.PriorityLastHitWaiting])
                    : new List<Obj_AI_Minion>();
            }
        }
        public static List<Obj_AI_Minion> LaneClearMinionsList
        {
            get { return CurrentMinionsLists.ContainsKey(TargetMinionType.LaneClear) ? new List<Obj_AI_Minion>(CurrentMinionsLists[TargetMinionType.LaneClear]) : new List<Obj_AI_Minion>(); }
        }
        public static List<Obj_AI_Minion> UnKillableMinionsList
        {
            get { return CurrentMinionsLists.ContainsKey(TargetMinionType.UnKillable) ? new List<Obj_AI_Minion>(CurrentMinionsLists[TargetMinionType.UnKillable]) : new List<Obj_AI_Minion>(); }
        }

        internal static readonly Dictionary<TargetMinionType, Obj_AI_Minion> CurrentMinions = new Dictionary<TargetMinionType, Obj_AI_Minion>
        {
            { TargetMinionType.LaneClear, null },
            { TargetMinionType.LastHit, null },
            { TargetMinionType.PriorityLastHitWaiting, null }
        };

        public static Obj_AI_Minion LastHitMinion
        {
            get { return CurrentMinions[TargetMinionType.LastHit]; }
            internal set { CurrentMinions[TargetMinionType.LastHit] = value; }
        }
        public static Obj_AI_Minion PriorityLastHitWaitingMinion
        {
            get { return CurrentMinions[TargetMinionType.PriorityLastHitWaiting]; }
            internal set
            {
                if (value != null)
                {
                    _lastShouldWait = Core.GameTickCount;
                }
                CurrentMinions[TargetMinionType.PriorityLastHitWaiting] = value;
            }
        }
        public static Obj_AI_Minion LaneClearMinion
        {
            get { return CurrentMinions[TargetMinionType.LaneClear]; }
            internal set { CurrentMinions[TargetMinionType.LaneClear] = value; }
        }

        internal static readonly Dictionary<int, CalculatedMinionValue> CurrentMinionValues = new Dictionary<int, CalculatedMinionValue>();
        // ReSharper disable once FunctionComplexityOverflow
        internal static void RecalculateLasthittableMinions()
        {
            var extraTime = !CanIssueOrder ? Math.Max(0, (int)(AttackDelay * 1000 - (Core.GameTickCount - LastAutoAttack))) : 0;
            var canMove = CanMove;
            var maxMissileTravelTime = (int) (IsMelee ? 0 : 1000 * Player.Instance.GetAutoAttackRange() / Player.Instance.BasicAttack.MissileSpeed);
            // Calculate values for all of the minions
            foreach (var minion in TickCachedMinions)
            {
                var attackCastDelay = GetAttackCastDelay(minion);
                var missileTravelTime = GetMissileTravelTime(minion);
                // Create new minion value instance
                var values = new CalculatedMinionValue(minion)
                {
                    LastHitTime = attackCastDelay + missileTravelTime + extraTime + Math.Max(0, (int) (2f * 1000 * (Player.Instance.Distance(minion) - Player.Instance.GetAutoAttackRange(minion)) / Player.Instance.MoveSpeed)),
                    LaneClearTime = GetAttackDelay(minion) + attackCastDelay + maxMissileTravelTime
                };
                CurrentMinionValues[minion.NetworkId] = values;
            }

            //Adding the damage that minions will receive
            foreach (var attack in Prediction.Health.IncomingAttacks.SelectMany(i => i.Value))
            {
                var netId = attack.Target.NetworkId;
                if (CurrentMinionValues.ContainsKey(netId))
                {
                    CurrentMinionValues[netId].LastHitHealth -= attack.GetDamage(CurrentMinionValues[netId].LastHitTime);
                    CurrentMinionValues[netId].LaneClearHealth -= attack.GetDamage(CurrentMinionValues[netId].LaneClearTime);
                }
            }
            //Selecting minion type
            foreach (var pair in CurrentMinionValues)
            {
                var value = pair.Value;
                var minion = value.Handle;
                // Check if minion is unkillable
                if (value.IsUnkillable)
                {
                    if (!minion.IdEquals(LastTarget))
                    {
                        if (OnUnkillableMinion != null && canMove)
                        {
                            OnUnkillableMinion(minion, new UnkillableMinionArgs { RemainingHealth = value.LastHitHealth });
                        }
                        CurrentMinionsLists[TargetMinionType.UnKillable].Add(minion);
                    }
                }
                // Check if minion can be last hitted
                else if (value.IsLastHittable)
                {
                    CurrentMinionsLists[TargetMinionType.LastHit].Add(minion);
                }
                // Check if minion is close to be lasthittable
                else if (value.IsAlmostLastHittable)
                {
                    CurrentMinionsLists[TargetMinionType.PriorityLastHitWaiting].Add(minion);
                }
                // Check if we can lane clear the minion
                else if (value.IsLaneClearMinion)
                {
                    CurrentMinionsLists[TargetMinionType.LaneClear].Add(minion);
                }
            }

            // Sort the lists and define targets
            SortMinionsAndDefineTargets();

            // Attack other objects than minions if we don't have a valid minion target
            if (AttackObjects && LastHitMinion == null && !ShouldWait)
            {
                foreach (var minion in
                    EntityManager.MinionsAndMonsters.OtherEnemyMinions.Where(Player.Instance.IsInAutoAttackRange)
                        .OrderByDescending(minion => minion.MaxHealth)
                        .ThenBy(minion => minion.Health)
                        .Where(minion => minion.Health > 0))
                {
                    LastHitMinion = minion;
                    break;
                }
            }
        }

        internal static void SortMinionsAndDefineTargets()
        {
            foreach (var entry in CurrentMinionsLists.ToArray())
            {
                switch (entry.Key)
                {
                    case TargetMinionType.LaneClear:
                        if (!_onlyLastHit)
                        {
                            CurrentMinionsLists[entry.Key] =
                                entry.Value.OrderByDescending(
                                    minion => FreezePriority ? CurrentMinionValues[minion.NetworkId].LaneClearHealth : 1 / CurrentMinionValues[minion.NetworkId].LaneClearHealth).ToList();
                            LaneClearMinion = entry.Value.FirstOrDefault(Player.Instance.IsInAutoAttackRange);
                        }
                        break;

                    case TargetMinionType.LastHit:
                        CurrentMinionsLists[entry.Key] = entry.Value.OrderByDescending(o => o.MaxHealth).ThenBy(o => CurrentMinionValues[o.NetworkId].LastHitHealth).ToList();
                        LastHitMinion = entry.Value.FirstOrDefault(Player.Instance.IsInAutoAttackRange);
                        break;

                    case TargetMinionType.PriorityLastHitWaiting:
                        CurrentMinionsLists[entry.Key] = entry.Value.OrderByDescending(o => o.MaxHealth).ThenBy(o => CurrentMinionValues[o.NetworkId].LaneClearHealth).ToList();
                        PriorityLastHitWaitingMinion = entry.Value.FirstOrDefault(Player.Instance.IsInAutoAttackRange);
                        break;

                    case TargetMinionType.UnKillable:
                        CurrentMinionsLists[entry.Key] = entry.Value.OrderBy(o => CurrentMinionValues[o.NetworkId].LastHitHealth).ToList();
                        break;
                }
            }
        }

        internal class CalculatedMinionValue
        {
            internal Obj_AI_Minion Handle { get; set; }

            internal int LastHitTime { get; set; }
            internal int LaneClearTime { get; set; }

            internal float LastHitHealth { get; set; }
            internal float LaneClearHealth { get; set; }

            internal bool IsUnkillable
            {
                get { return LastHitHealth < 0; }
            }
            internal bool IsLastHittable
            {
                get { return LastHitHealth <= GetAutoAttackDamage(Handle); }
            }
            internal bool IsAlmostLastHittable
            {
                get
                {
                    var health = Handle.HasTurretTargetting() ? LastHitHealth : LaneClearHealth;
                    var percentMod = Handle.IsSiegeMinion() ? 1.5f : 1f;
                    return health <= percentMod * GetAutoAttackDamage(Handle) && health < Handle.Health;
                }
            }

            internal bool IsLaneClearMinion
            {
                get
                {
                    if (_onlyLastHit)
                    {
                        return false;
                    }
                    var turretNear = EntityManager.Turrets.Allies.FirstOrDefault(t => t.Distance(Handle, true) <= TurretRangeSqr);
                    if (turretNear != null)
                    {
                        if (Math.Abs(LaneClearHealth - Handle.Health) < float.Epsilon)
                        {
                            var turretDamage = turretNear.GetAutoAttackDamage(Handle);
                            var damage = GetAutoAttackDamage(Handle);
                            for (var minionHealth = Handle.Health; minionHealth > 0 && turretDamage > 0; minionHealth -= turretDamage)
                            {
                                if (minionHealth <= damage)
                                {
                                    return false;
                                }
                            }
                            return true;
                        }
                        return false;
                    }
                    var laneClearPercentMod = 2 * (Player.Instance.FlatCritChanceMod >= 0.5f && Player.Instance.FlatCritChanceMod < 1f ? Player.Instance.GetCriticalStrikePercentMod() : 1f);
                    return LaneClearHealth > laneClearPercentMod * GetAutoAttackDamage(Handle) || Math.Abs(LaneClearHealth - Handle.Health) < float.Epsilon;
                }
            }

            internal CalculatedMinionValue(Obj_AI_Minion minion)
            {
                Handle = minion;
                LastHitHealth = Handle.Health;
                LaneClearHealth = Handle.Health;
            }
        }

        #endregion

        #region Mode Key Handling

        public static void RegisterKeyBind(KeyBind key, ActiveModes mode)
        {
            key.OnValueChange += delegate(ValueBase<bool> sender, ValueBase<bool>.ValueChangeArgs args)
            {
                if (args.NewValue)
                {
                    if (!ActiveModesFlags.HasFlag(mode))
                    {
                        ActiveModesFlags = ActiveModesFlags | mode;
                    }
                }
                else
                {
                    if (ActiveModesFlags.HasFlag(mode))
                    {
                        ActiveModesFlags = ActiveModesFlags ^ mode;
                    }
                }
            };
        }

        #endregion

        #region Drawing

        internal static void OnDraw(EventArgs args)
        {
            // Draw a circle indicating our auto attack range
            if (DrawRange)
            {
                Circle.Draw(Color.LightGreen, Player.Instance.GetAutoAttackRange(), Player.Instance);
            }

            // Azir soldier range
            if (Player.Instance.Hero == Champion.Azir && DrawAzirRange)
            {
                foreach (var soldier in _validAzirSoldiers.Values)
                {
                    Circle.Draw(Color.LightGreen, soldier.GetAutoAttackRange(), soldier);
                }
            }

            // Draw the hold radius
            if (DrawHoldRadius)
            {
                Circle.Draw(Color.LightGreen, HoldRadius, Player.Instance);
            }

            // Enemy auto attack range
            if (DrawEnemyRange)
            {
                foreach (var enemy in EntityManager.Heroes.Enemies.Where(o => o.IsValidTarget()))
                {
                    var range = enemy.GetAutoAttackRange(Player.Instance);
                    var isInRange = enemy.IsInRange(Player.Instance, range);
                    Circle.Draw(isInRange ? EnemyRangeColorInRange : EnemyRangeColorNotInRange, range, enemy);
                }
            }

            // Draw lasthittable minions
            if (DrawLastHitMarker)
            {
                if (LastHitMinion != null)
                {
                    Circle.Draw(Color.White, Math.Max(LastHitMinion.BoundingRadius, 65), 2, LastHitMinion);
                }
                if (PriorityLastHitWaitingMinion != null && !PriorityLastHitWaitingMinion.IdEquals(LastHitMinion))
                {
                    Circle.Draw(Color.Orange, Math.Max(PriorityLastHitWaitingMinion.BoundingRadius, 65), 2, PriorityLastHitWaitingMinion);
                }
            }
        }

        #endregion
    }
}
