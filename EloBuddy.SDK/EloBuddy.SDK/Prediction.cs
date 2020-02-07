using System;
using System.Collections.Generic;
using System.Linq;
using System.Text.RegularExpressions;
using EloBuddy.SDK.Constants;
using EloBuddy.SDK.Enumerations;
using EloBuddy.SDK.Events;
using EloBuddy.SDK.Menu;
using EloBuddy.SDK.Menu.Values;
using EloBuddy.SDK.Spells;
using EloBuddy.SDK.Utils;
using SharpDX;

// ReSharper disable MemberHidesStaticFromOuterClass
// ReSharper disable once CompareOfFloatsByEqualityOperator

namespace EloBuddy.SDK
{
    public static class Prediction
    {
        internal static void Initialize()
        {
            // Initialize classes
            Health.Initialize();
            Position.Initialize();
            Position.Collision.Initialize();
            Manager.Initialize();
        }

        public static class Manager
        {
            // ReSharper disable once InconsistentNaming
            internal static Menu.Menu Menu;
            private static ComboBox SelectedComboBox
            {
                get { return Menu["PredictionSelected"].Cast<ComboBox>(); }
            }
            public static string PredictionSelected
            {
                get { return Strings.ContainsKey(SelectedComboBox.CurrentValue) ? Strings[SelectedComboBox.CurrentValue] : "SDK Prediction"; }
                set
                {
                    for (var i = 0; i < Strings.Count; i++)
                    {
                        var str = Strings[i];
                        if (str == value)
                        {
                            SelectedComboBox.CurrentValue = i;
                        }
                    }
                }
            }
            private static readonly Dictionary<int, Func<PredictionInput, PredictionOutput>> Suscribers = new Dictionary<int, Func<PredictionInput, PredictionOutput>>();
            private static readonly Dictionary<int, string> Strings = new Dictionary<int, string>();

            internal static void Initialize()
            {
            }

            public class PredictionInput
            {
                private Vector3? _from;
                public Vector3 From
                {
                    get { return _from ?? Player.Instance.ServerPosition; }
                    set { _from = value; }
                }
                private Vector3? _rangeCheckFrom;
                public Vector3 RangeCheckFrom
                {
                    get { return _rangeCheckFrom ?? From; }
                    set { _rangeCheckFrom = value; }
                }
                public HashSet<CollisionType> CollisionTypes = new HashSet<CollisionType>();
                public float Delay;
                public float Radius = 1f;
                public float Range = float.MaxValue;
                public float Speed = float.MaxValue;
                public SkillShotType Type = SkillShotType.Circular;
                public Obj_AI_Base Target;
            }

            public class PredictionOutput
            {
                public PredictionOutput(PredictionInput input)
                {
                    Input = input;
                    CastPosition = input.Target.ServerPosition;
                    PredictedPosition = input.Target.ServerPosition;
                }

                public PredictionInput Input;
                public List<GameObject> CollisionGameObjects = new List<GameObject>();
                public HitChance HitChance = HitChance.Impossible;
                public Vector3 CastPosition;
                public Vector3 PredictedPosition;
                private float _hitChancePercent;
                public float HitChancePercent
                {
                    get { return !Collides ? _hitChancePercent : 0f; }
                    set
                    {
                        _hitChancePercent = value;
                        RealHitChancePercent = value;
                    }
                }
                public bool Collides
                {
                    get { return CollisionGameObjects.Count > 0; }
                }
                public float RealHitChancePercent;
                public Obj_AI_Base[] CollisionObjects
                {
                    get { return GetCollisionObjects<Obj_AI_Base>(); }
                }

                public T[] GetCollisionObjects<T>()
                {
                    return CollisionGameObjects.Where(unit => unit.GetType() == typeof (T)).Cast<T>().ToArray();
                }

                public GameObject[] GetCollisionObjects(CollisionType type)
                {
                    switch (type)
                    {
                        case CollisionType.AiHeroClient:
                            return CollisionGameObjects.Where(unit => unit is AIHeroClient).ToArray();
                        case CollisionType.ObjAiMinion:
                            return CollisionGameObjects.Where(unit => unit is Obj_AI_Minion).ToArray();
                        case CollisionType.YasuoWall:
                            return CollisionGameObjects.Where(unit => !(unit is Obj_AI_Minion) && !(unit is AIHeroClient)).ToArray();
                    }
                    return new GameObject[] { };
                }
            }

            public static void Suscribe(string addonName, Func<PredictionInput, PredictionOutput> func)
            {
                if (SelectedComboBox.Overlay.Children.All(i => i.TextValue != addonName))
                {
                    SelectedComboBox.Add(addonName);
                }
                if (Strings.All(i => i.Value != addonName))
                {
                    var count = Strings.Count;
                    Suscribers[count] = func;
                    Strings[count] = addonName;
                }
            }

            public static PredictionOutput GetPrediction(PredictionInput input)
            {
                var selected = Menu["PredictionSelected"].Cast<ComboBox>().CurrentValue;
                return Suscribers.ContainsKey(selected) ? Suscribers[selected](input) : new PredictionOutput(input);
            }
        }

        public static class Health
        {
            internal static readonly Dictionary<int, List<IncomingAttack>> IncomingAttacks = new Dictionary<int, List<IncomingAttack>>();

            internal static void Initialize()
            {
                // Listen to required events
                Obj_AI_Base.OnBasicAttack += OnBasicAttack;
                Spellbook.OnStopCast += OnStopCast;
                Game.OnTick += OnGameUpdate;
                GameObject.OnDelete += OnDelete;
            }

            private static void OnDelete(GameObject sender, EventArgs args)
            {
                var baseObject = sender as Obj_AI_Base;
                if (baseObject != null)
                {
                    foreach (var attack in IncomingAttacks.ToArray().SelectMany(entry => entry.Value))
                    {
                        if (attack.Target.IdEquals(baseObject))
                        {
                            attack.Target = null;
                        }
                        if (attack.Source.IdEquals(baseObject))
                        {
                            attack.Source = null;
                        }
                    }
                }
            }

            internal static void OnBasicAttack(Obj_AI_Base sender, GameObjectProcessSpellCastEventArgs args)
            {
                if (sender.IsMe)
                {
                    return;
                }
                var target = args.Target as Obj_AI_Minion;
                if (target == null)
                {
                    return;
                }
                if (!sender.Team.IsAlly() || !Player.Instance.IsInRange(sender, 2000))
                {
                    return;
                }
                switch (sender.Type)
                {
                    case GameObjectType.obj_AI_Turret:
                        if (ObjectNames.BaseTurrets.Contains(sender.BaseSkinName))
                        {
                            return;
                        }
                        break;
                    case GameObjectType.obj_AI_Minion:
                        break;
                    case GameObjectType.AIHeroClient:
                        break;
                    default:
                        return;
                }
                if (!IncomingAttacks.ContainsKey(sender.NetworkId))
                {
                    IncomingAttacks.Add(sender.NetworkId, new List<IncomingAttack>());
                }
                else
                {
                    // Mark all current attacks as inactive attack
                    foreach (var attack in IncomingAttacks[sender.NetworkId])
                    {
                        attack.IsActiveAttack = false;
                    }
                }

                // Add incoming attack
                IncomingAttacks[sender.NetworkId].Add(new IncomingAttack(sender, target, (int) args.SData.MissileSpeed));
            }

            internal static void OnStopCast(Obj_AI_Base sender, SpellbookStopCastEventArgs args)
            {
                if (args.DestroyMissile && args.StopAnimation)
                {
                    if (sender.IsMelee)
                    {
                        // Remove the current attack for melee fighters
                        IncomingAttacks.Remove(sender.NetworkId);
                    }
                    else
                    {
                        // Remove the last cast for ranged casters
                        if (IncomingAttacks.ContainsKey(sender.NetworkId) && IncomingAttacks[sender.NetworkId].Count > 0)
                        {
                            IncomingAttacks[sender.NetworkId].RemoveAt(IncomingAttacks[sender.NetworkId].Count - 1);
                        }
                    }
                }
            }

            internal static void OnGameUpdate(EventArgs args)
            {
                // Remove inactive and invalid attacks
                VerifyAttacks();
            }

            internal static void VerifyAttacks()
            {
                foreach (var entry in IncomingAttacks.ToArray())
                {
                    var sourceIsValid = ObjectManager.GetUnitByNetworkId<Obj_AI_Base>((uint) entry.Key).IsValidTarget();
                    foreach (var attack in entry.Value.ToArray())
                    {
                        attack._sourceIsValid = sourceIsValid;
                        if (attack.ShouldRemove)
                        {
                            IncomingAttacks[entry.Key].Remove(attack);
                        }
                    }
                    if (IncomingAttacks[entry.Key].Count == 0)
                    {
                        IncomingAttacks.Remove(entry.Key);
                    }
                }
            }

            private static float Angle(Vector2 u, Vector2 v)
            {
                var cos = (u.X * v.X + u.Y * v.Y) / (Math.Sqrt(u.X.Pow() + u.Y.Pow()) * Math.Sqrt(v.X.Pow() + v.Y.Pow()));
                return (float) Math.Acos(cos);
            }

            internal static float GetPredictedMissileTravelTime(Obj_AI_Base minion, Vector3 sourcePosition, float missileDelay, float missileSpeed)
            {
                return GetPredictedMissileTravelTime(minion, sourcePosition.To2D(), missileDelay, missileSpeed);
            }

            internal static float GetPredictedMissileTravelTime(Obj_AI_Base minion, Vector2 sourcePosition, float missileDelay, float missileSpeed)
            {
                if (missileSpeed > 0 && missileSpeed < float.MaxValue)
                {
                    var minionPosition = minion.ServerPosition;
                    if (minion.IsMoving && minion.Path.Length == 2)
                    {
                        var pathEndPosition = minion.Path.LastOrDefault();
                        var direction = (pathEndPosition - minionPosition).Normalized();
                        var totalPathLength = pathEndPosition.Distance(minionPosition);
                        var delayDistance = missileDelay * minion.MoveSpeed;
                        if (delayDistance <= totalPathLength)
                        {
                            var positionAfterDelay = minionPosition + direction * delayDistance;
                            var bAngle = Angle((positionAfterDelay.To2D() - sourcePosition).Normalized(), direction.To2D());
                            var sinB = Math.Sin(bAngle);
                            var sinC = Math.Sin(minion.MoveSpeed / missileSpeed * sinB);
                            var cAngle = Math.Asin(sinC);
                            var aAngle = Math.PI - bAngle - cAngle;
                            if (aAngle >= 0)
                            {
                                var startDistance = positionAfterDelay.Distance(sourcePosition);
                                var sinA = Math.Sin(aAngle);
                                var playerDistance = (float) (sinC / sinA * startDistance);
                                var predictedPos = positionAfterDelay + direction * playerDistance;
                                minionPosition = playerDistance + delayDistance <= totalPathLength ? predictedPos : pathEndPosition;
                            }
                            else
                            {
                                minionPosition = pathEndPosition;
                            }
                        }
                        else
                        {
                            minionPosition = pathEndPosition;
                        }
                    }
                    return sourcePosition.Distance(minionPosition) / missileSpeed;
                }
                return 0f;
            }

            /// <summary>
            /// Returns a dictionary that contains the minions and their predicted healths, using a dictionary that contains the minion and the delay wanted in milliseconds. 
            /// </summary>
            public static Dictionary<Obj_AI_Base, float> GetPrediction(Dictionary<Obj_AI_Base, int> minionsTime)
            {
                var minionsHealth = minionsTime.Keys.ToDictionary(minion => minion, minion => minion.Health);
                foreach (var attack in from entry in IncomingAttacks from attack in entry.Value where minionsTime.ContainsKey(attack.Target) select attack)
                {
                    minionsHealth[attack.Target] -= attack.GetDamage(minionsTime[attack.Target]);
                }
                return minionsHealth;
            }

            /// <summary>
            /// Returns the unit health after a set time delay milliseconds. 
            /// </summary>
            public static float GetPrediction(Obj_AI_Base target, int time)
            {
                return (target.Health + target.AllShield + target.AttackShield) - IncomingAttacks.Sum(entry => entry.Value.Where(o => o.EqualsTarget(target)).Sum(attack => attack.GetDamage(time)));
            }

            internal class IncomingAttack
            {
                internal Obj_AI_Base Source { get; set; }
                internal Obj_AI_Base Target { get; set; }
                private float? _cachedDamage;
                internal float Damage
                {
                    get
                    {
                        if (!_cachedDamage.HasValue)
                        {
                            _cachedDamage = Source.GetAutoAttackDamage(Target, true);
                        }
                        return _cachedDamage.Value;
                    }
                }

                internal int StartTick { get; set; }
                internal int AttackCastDelay { get; set; }
                internal int AttackDelay { get; set; }
                internal int MissileSpeed { get; set; }

                internal int MissileFlightTime
                {
                    get
                    {
                        if (_sourceIsRanged)
                        {
                            return (int) (1000 * GetPredictedMissileTravelTime(Target, SourcePosition, Math.Max(0f, AttackCastDelay - (Core.GameTickCount - StartTick)) / 1000f, MissileSpeed));
                        }
                        return 0;
                    }
                }

                internal Vector2 SourcePosition { get; set; }

                internal bool _sourceIsRanged;
                internal bool _sourceIsValid = true;
                internal bool _arrived;

                internal bool IsActiveAttack { get; set; }

                internal bool ShouldRemove
                {
                    get { return Target == null || Target.IsDead || 3000 < Core.GameTickCount - StartTick || _arrived; }
                }

                internal bool EqualsTarget(Obj_AI_Base target)
                {
                    return Target.NetworkId == target.NetworkId;
                }

                public IncomingAttack(Obj_AI_Base source, Obj_AI_Base target, int missileSpeed = int.MaxValue)
                {
                    // Apply properties
                    Source = source;
                    Target = target;
                    SourcePosition = source.ServerPosition.To2D();
                    AttackCastDelay = (int) (Source.AttackCastDelay * 1000);
                    AttackDelay = (int) (Source.AttackDelay * 1000);
                    MissileSpeed = missileSpeed;
                    StartTick = Core.GameTickCount;
                    IsActiveAttack = true;
                    _sourceIsRanged = Source.IsRanged;
                }

                public float GetDamage(int delay)
                {
                    var damage = 0f;
                    if (!ShouldRemove)
                    {
                        delay += Game.Ping - 100 + Orbwalker.ExtraFarmDelay;
                        // Calculate the time when the missile will hit the target
                        var timeTillHit = StartTick + AttackCastDelay + MissileFlightTime - Core.GameTickCount;
                        if (timeTillHit <= -250)
                        {
                            _arrived = true;
                        }
                        if (IsActiveAttack && _sourceIsValid)
                        {
                            // Calculate amount of attacks within the given delay time
                            var attackAmount = 0;
                            while (timeTillHit < delay)
                            {
                                // Prevent taking into account attacks that have already hit the target
                                if (timeTillHit > 0)
                                {
                                    attackAmount++;
                                }
                                timeTillHit += AttackDelay;
                            }

                            // Set the damage times the attack amount
                            damage += Damage * attackAmount;
                        }
                        else if (timeTillHit < delay && timeTillHit > 0)
                        {
                            // Attack will hit in time
                            if (_sourceIsRanged || _sourceIsValid)
                            {
                                damage += Damage;
                            }
                        }
                    }
                    return damage;
                }
            }
        }

        public static class Position
        {
            internal class StoredPath
            {
                /// <summary>
                /// The path
                /// </summary>
                internal List<Vector2> Path;

                /// <summary>
                /// The tick
                /// </summary>
                internal int Tick;

                /// <summary>
                /// Gets the time.
                /// </summary>
                /// <value>The time.</value>
                internal double Time
                {
                    get { return (Core.GameTickCount - Tick) / 1000d; }
                }

                /// <summary>
                /// Gets the waypoint count.
                /// </summary>
                /// <value>The waypoint count.</value>
                internal int WaypointCount
                {
                    get { return Path.Count; }
                }

                /// <summary>
                /// Gets the start point.
                /// </summary>
                /// <value>The start point.</value>
                internal Vector2 StartPoint
                {
                    get { return Path.FirstOrDefault(); }
                }

                /// <summary>
                /// Gets the end point.
                /// </summary>
                /// <value>The end point.</value>
                internal Vector2 EndPoint
                {
                    get { return Path.LastOrDefault(); }
                }
            }

            internal static class PathTracker
            {
                /// <summary>
                /// The maximum time
                /// </summary>
                private const double MaxTime = 1.5d;

                /// <summary>
                /// The stored paths
                /// </summary>
                private static readonly Dictionary<int, List<StoredPath>> StoredPaths = new Dictionary<int, List<StoredPath>>();

                /// <summary>
                /// Initializes static members of the <see cref="PathTracker"/> class.
                /// </summary>
                static PathTracker()
                {
                    Obj_AI_Base.OnNewPath += Obj_AI_Hero_OnNewPath;
                }

                /// <summary>
                /// Fired when a unit changes it's path.
                /// </summary>
                /// <param name="sender">The sender.</param>
                /// <param name="args">The <see cref="GameObjectNewPathEventArgs"/> instance containing the event data.</param>
                private static void Obj_AI_Hero_OnNewPath(Obj_AI_Base sender, GameObjectNewPathEventArgs args)
                {
                    if (!(sender is AIHeroClient))
                    {
                        return;
                    }

                    if (!StoredPaths.ContainsKey(sender.NetworkId))
                    {
                        StoredPaths.Add(sender.NetworkId, new List<StoredPath>());
                    }

                    var newPath = new StoredPath { Tick = Core.GameTickCount, Path = args.Path.ToList().To2D() };
                    StoredPaths[sender.NetworkId].Add(newPath);

                    if (StoredPaths[sender.NetworkId].Count > 50)
                    {
                        StoredPaths[sender.NetworkId].RemoveRange(0, 40);
                    }
                }

                /// <summary>
                /// Gets the stored paths.
                /// </summary>
                /// <param name="unit">The unit.</param>
                /// <param name="maxT">The maximum t.</param>
                /// <returns>List&lt;StoredPath&gt;.</returns>
                internal static List<StoredPath> GetStoredPaths(Obj_AI_Base unit, double maxT)
                {
                    return StoredPaths.ContainsKey(unit.NetworkId) ? StoredPaths[unit.NetworkId].Where(p => p.Time < maxT).ToList() : new List<StoredPath>();
                }

                /// <summary>
                /// Gets the current path.
                /// </summary>
                /// <param name="unit">The unit.</param>
                /// <returns>StoredPath.</returns>
                public static StoredPath GetCurrentPath(Obj_AI_Base unit)
                {
                    return StoredPaths.ContainsKey(unit.NetworkId) ? StoredPaths[unit.NetworkId].LastOrDefault() : new StoredPath();
                }

                /// <summary>
                /// Gets the tendency.
                /// </summary>
                /// <param name="unit">The unit.</param>
                /// <returns>Vector3.</returns>
                public static Vector3 GetTendency(Obj_AI_Base unit)
                {
                    var paths = GetStoredPaths(unit, MaxTime);
                    var result = new Vector2();

                    foreach (var path in paths)
                    {
                        var k = 1; //(MaxTime - path.Time);
                        result = result + k * (path.EndPoint - unit.ServerPosition.To2D() /*path.StartPoint*/).Normalized();
                    }

                    result /= paths.Count;

                    return result.To3D();
                }

                /// <summary>
                /// Gets the mean speed.
                /// </summary>
                /// <param name="unit">The unit.</param>
                /// <param name="maxT">The maximum t.</param>
                /// <returns>System.Double.</returns>
                public static double GetMeanSpeed(Obj_AI_Base unit, double maxT)
                {
                    var paths = GetStoredPaths(unit, MaxTime);
                    var distance = 0d;
                    if (paths.Count > 0)
                    {
                        //Assume that the unit was moving for the first path:
                        distance += (maxT - paths[0].Time) * unit.MoveSpeed;

                        for (var i = 0; i < paths.Count - 1; i++)
                        {
                            var currentPath = paths[i];
                            var nextPath = paths[i + 1];

                            if (currentPath.WaypointCount > 0)
                            {
                                distance += Math.Min((currentPath.Time - nextPath.Time) * unit.MoveSpeed, currentPath.Path.PathLength());
                            }
                        }

                        //Take into account the last path:
                        var lastPath = paths.Last();
                        if (lastPath.WaypointCount > 0)
                        {
                            distance += Math.Min(lastPath.Time * unit.MoveSpeed, lastPath.Path.PathLength());
                        }
                    }
                    else
                    {
                        return unit.MoveSpeed;
                    }

                    return distance / maxT;
                }
            }

            internal static class CollisionEx
            {
                /// <summary>
                /// The tick yasuo casted wind wall.
                /// </summary>
                private static int _wallCastT;

                /// <summary>
                /// The yasuo wind wall casted position.
                /// </summary>
                private static Vector2 _yasuoWallCastedPos;

                /// <summary>
                /// Initializes static members of the <see cref="Collision"/> class.
                /// </summary>
                static CollisionEx()
                {
                    Obj_AI_Base.OnProcessSpellCast += Obj_AI_Hero_OnProcessSpellCast;
                }

                /// <summary>
                /// Fired when the game processes a spell cast.
                /// </summary>
                /// <param name="sender">The sender.</param>
                /// <param name="args">The <see cref="GameObjectProcessSpellCastEventArgs"/> instance containing the event data.</param>
                private static void Obj_AI_Hero_OnProcessSpellCast(Obj_AI_Base sender, GameObjectProcessSpellCastEventArgs args)
                {
                    if (sender.IsValid && sender.Team != ObjectManager.Player.Team && args.SData.Name == "YasuoWMovingWall")
                    {
                        _wallCastT = Core.GameTickCount;
                        _yasuoWallCastedPos = sender.ServerPosition.To2D();
                    }
                }

                /// <summary>
                /// Returns the list of the units that the skillshot will hit before reaching the set positions.
                /// </summary>
                /// <param name="positions">The positions.</param>
                /// <param name="input">The input.</param>
                /// <returns>List&lt;Obj_AI_Base&gt;.</returns>
                internal static List<Obj_AI_Base> GetCollision(List<Vector3> positions, PredictionInput input)
                {
                    var result = new List<Obj_AI_Base>();

                    foreach (var position in positions)
                    {
                        foreach (var objectType in input.CollisionObjects)
                        {
                            switch (objectType)
                            {
                                case CollisionableObjectsEx.Minions:
                                    foreach (var minion in
                                        ObjectManager.Get<Obj_AI_Minion>().Where(minion => minion.IsValidTarget(Math.Min(input.Range + input.Radius + 100, 2000), true, input.RangeCheckFrom)))
                                    {
                                        input.Unit = minion;
                                        var minionPrediction = PredictionEx.GetPrediction(input, false, false);
                                        if (minionPrediction.UnitPosition.To2D().Distance(input.From.To2D(), position.To2D(), true, true) <= Math.Pow((input.Radius + 15 + minion.BoundingRadius), 2))
                                        {
                                            result.Add(minion);
                                        }
                                    }
                                    break;
                                case CollisionableObjectsEx.Heroes:
                                    foreach (var hero in
                                        EntityManager.Heroes.Enemies.FindAll(hero => hero.IsValidTarget(Math.Min(input.Range + input.Radius + 100, 2000), true, input.RangeCheckFrom)))
                                    {
                                        input.Unit = hero;
                                        var prediction = PredictionEx.GetPrediction(input, false, false);
                                        if (prediction.UnitPosition.To2D().Distance(input.From.To2D(), position.To2D(), true, true) <= Math.Pow((input.Radius + 50 + hero.BoundingRadius), 2))
                                        {
                                            result.Add(hero);
                                        }
                                    }
                                    break;

                                case CollisionableObjectsEx.Allies:
                                    foreach (var hero in
                                        EntityManager.Heroes.Allies.FindAll(
                                            hero => Vector3.Distance(ObjectManager.Player.ServerPosition, hero.ServerPosition) <= Math.Min(input.Range + input.Radius + 100, 2000)))
                                    {
                                        input.Unit = hero;
                                        var prediction = PredictionEx.GetPrediction(input, false, false);
                                        if (prediction.UnitPosition.To2D().Distance(input.From.To2D(), position.To2D(), true, true) <= Math.Pow((input.Radius + 50 + hero.BoundingRadius), 2))
                                        {
                                            result.Add(hero);
                                        }
                                    }
                                    break;

                                case CollisionableObjectsEx.Walls:
                                    var step = position.Distance(input.From) / 20;
                                    for (var i = 0; i < 20; i++)
                                    {
                                        var p = input.From.To2D().Extend(position.To2D(), step * i);
                                        /*
                                        if (p.IsWall())
                                        {
                                            result.Add(ObjectManager.Player);
                                        }
                                        */
                                    }
                                    break;

                                case CollisionableObjectsEx.YasuoWall:

                                    if (Core.GameTickCount - _wallCastT > 4000)
                                    {
                                        break;
                                    }

                                    GameObject wall = null;
                                    foreach (var gameObject in
                                        ObjectManager.Get<GameObject>()
                                            .Where(gameObject => gameObject.IsValid && Regex.IsMatch(gameObject.Name, "_w_windwall_enemy_0.\\.troy", RegexOptions.IgnoreCase)))
                                    {
                                        wall = gameObject;
                                    }
                                    if (wall == null)
                                    {
                                        break;
                                    }
                                    var level = wall.Name.Substring(wall.Name.Length - 6, 1);
                                    var wallWidth = (300 + 50 * Convert.ToInt32(level));

                                    var wallDirection = (wall.Position.To2D() - _yasuoWallCastedPos).Normalized().Perpendicular();
                                    var wallStart = wall.Position.To2D() + wallWidth / 2f * wallDirection;
                                    var wallEnd = wallStart - wallWidth * wallDirection;

                                    if (wallStart.Intersection(wallEnd, position.To2D(), input.From.To2D()).Intersects)
                                    {
                                        var t = Core.GameTickCount + (wallStart.Intersection(wallEnd, position.To2D(), input.From.To2D()).Point.Distance(input.From) / input.Speed + input.Delay) * 1000;
                                        if (t < _wallCastT + 4000)
                                        {
                                            result.Add(ObjectManager.Player);
                                        }
                                    }

                                    break;
                            }
                        }
                    }

                    return result.Distinct().ToList();
                }
            }

            internal enum HitChanceEx
            {
                Immobile = 8,
                Dashing = 7,
                VeryHigh = 6,
                High = 5,
                Medium = 4,
                Low = 3,
                Impossible = 2,
                OutOfRange = 1,
                Collision = 0
            }

            internal enum SkillshotTypeEx
            {
                SkillshotLine,
                SkillshotCircle,
                SkillshotCone
            }

            internal enum CollisionableObjectsEx
            {
                Minions,
                Heroes,
                YasuoWall,
                Walls,
                Allies
            }

            internal class PredictionInput
            {
                /// <summary>
                /// The position that the skillshot will be launched from.
                /// </summary>
                private Vector3 _from;

                /// <summary>
                /// The position to check the range from.
                /// </summary>
                private Vector3 _rangeCheckFrom;

                /// <summary>
                /// If set to <c>true</c> the prediction will hit as many enemy heroes as posible.
                /// </summary>
                internal bool Aoe = false;

                /// <summary>
                /// <c>true</c> if the spell collides with units.
                /// </summary>
                internal bool Collision = false;

                /// <summary>
                /// Array that contains the unit types that the skillshot can collide with.
                /// </summary>
                internal CollisionableObjectsEx[] CollisionObjects =
                {
                    CollisionableObjectsEx.Minions, CollisionableObjectsEx.YasuoWall
                };

                /// <summary>
                /// The skillshot delay in seconds.
                /// </summary>
                internal float Delay;

                /// <summary>
                /// The skillshot width's radius or the angle in case of the cone skillshots.
                /// </summary>
                internal float Radius = 1f;

                /// <summary>
                /// The skillshot range in units.
                /// </summary>
                internal float Range = float.MaxValue;

                /// <summary>
                /// The skillshot speed in units per second.
                /// </summary>
                internal float Speed = float.MaxValue;

                /// <summary>
                /// The skillshot type.
                /// </summary>
                internal SkillshotTypeEx Type = SkillshotTypeEx.SkillshotLine;

                /// <summary>
                /// The unit that the prediction will made for.
                /// </summary>
                internal Obj_AI_Base Unit = ObjectManager.Player;

                /// <summary>
                /// Set to true to increase the prediction radius by the unit bounding radius.
                /// </summary>
                internal bool UseBoundingRadius = true;

                /// <summary>
                /// The position from where the skillshot missile gets fired.
                /// </summary>
                /// <value>From.</value>
                internal Vector3 From
                {
                    get { return _from.To2D().IsValid() ? _from : ObjectManager.Player.ServerPosition; }
                    set { _from = value; }
                }

                /// <summary>
                /// The position from where the range is checked.
                /// </summary>
                /// <value>The range check from.</value>
                internal Vector3 RangeCheckFrom
                {
                    get { return _rangeCheckFrom.To2D().IsValid() ? _rangeCheckFrom : (From.To2D().IsValid() ? From : ObjectManager.Player.ServerPosition); }
                    set { _rangeCheckFrom = value; }
                }

                /// <summary>
                /// Gets the real radius.
                /// </summary>
                /// <value>The real radius.</value>
                internal float RealRadius
                {
                    get { return UseBoundingRadius ? Radius + Unit.BoundingRadius : Radius; }
                }
            }

            internal class PredictionOutput
            {
                /// <summary>
                /// The AoE target hit.
                /// </summary>
                internal int _aoeTargetsHitCount;

                /// <summary>
                /// The calculated cast position
                /// </summary>
                private Vector3 _castPosition;

                /// <summary>
                /// The predicted unit position
                /// </summary>
                private Vector3 _unitPosition;

                /// <summary>
                /// The list of the targets that the spell will hit (only if aoe was enabled).
                /// </summary>
                internal List<AIHeroClient> AoeTargetsHit = new List<AIHeroClient>();

                /// <summary>
                /// The list of the units that the skillshot will collide with.
                /// </summary>
                internal List<Obj_AI_Base> CollisionObjects = new List<Obj_AI_Base>();

                /// <summary>
                /// Returns the hitchance.
                /// </summary>
                internal HitChanceEx Hitchance = HitChanceEx.Impossible;

                /// <summary>
                /// The input
                /// </summary>
                internal PredictionInput Input;

                /// <summary>
                /// The position where the skillshot should be casted to increase the accuracy.
                /// </summary>
                /// <value>The cast position.</value>
                internal Vector3 CastPosition
                {
                    get { return _castPosition.IsValid() && _castPosition.To2D().IsValid() ? _castPosition.SetZ() : Input.Unit.ServerPosition; }
                    set { _castPosition = value; }
                }

                /// <summary>
                /// The number of targets the skillshot will hit (only if aoe was enabled).
                /// </summary>
                /// <value>The aoe targets hit count.</value>
                internal int AoeTargetsHitCount
                {
                    get { return Math.Max(_aoeTargetsHitCount, AoeTargetsHit.Count); }
                }

                /// <summary>
                /// The position where the unit is going to be when the skillshot reaches his position.
                /// </summary>
                /// <value>The unit position.</value>
                internal Vector3 UnitPosition
                {
                    get { return _unitPosition.To2D().IsValid() ? _unitPosition.SetZ() : Input.Unit.ServerPosition; }
                    set { _unitPosition = value; }
                }
            }

            internal static class PredictionEx
            {
                /// <summary>
                /// Initializes this instance.
                /// </summary>
                internal static void Initialize()
                {
                }

                /// <summary>
                /// Gets the prediction.
                /// </summary>
                /// <param name="unit">The unit.</param>
                /// <param name="delay">The delay.</param>
                /// <returns>PredictionOutput.</returns>
                internal static PredictionOutput GetPrediction(Obj_AI_Base unit, float delay)
                {
                    return GetPrediction(new PredictionInput { Unit = unit, Delay = delay });
                }

                /// <summary>
                /// Gets the prediction.
                /// </summary>
                /// <param name="unit">The unit.</param>
                /// <param name="delay">The delay.</param>
                /// <param name="radius">The radius.</param>
                /// <returns>PredictionOutput.</returns>
                internal static PredictionOutput GetPrediction(Obj_AI_Base unit, float delay, float radius)
                {
                    return GetPrediction(new PredictionInput { Unit = unit, Delay = delay, Radius = radius });
                }

                /// <summary>
                /// Gets the prediction.
                /// </summary>
                /// <param name="unit">The unit.</param>
                /// <param name="delay">The delay.</param>
                /// <param name="radius">The radius.</param>
                /// <param name="speed">The speed.</param>
                /// <returns>PredictionOutput.</returns>
                internal static PredictionOutput GetPrediction(Obj_AI_Base unit, float delay, float radius, float speed)
                {
                    return GetPrediction(new PredictionInput { Unit = unit, Delay = delay, Radius = radius, Speed = speed });
                }

                /// <summary>
                /// Gets the prediction.
                /// </summary>
                /// <param name="unit">The unit.</param>
                /// <param name="delay">The delay.</param>
                /// <param name="radius">The radius.</param>
                /// <param name="speed">The speed.</param>
                /// <param name="collisionable">The collisionable objects.</param>
                /// <returns>PredictionOutput.</returns>
                internal static PredictionOutput GetPrediction(Obj_AI_Base unit, float delay, float radius, float speed, CollisionableObjectsEx[] collisionable)
                {
                    return GetPrediction(new PredictionInput { Unit = unit, Delay = delay, Radius = radius, Speed = speed, CollisionObjects = collisionable });
                }

                /// <summary>
                /// Gets the prediction.
                /// </summary>
                /// <param name="input">The input.</param>
                /// <returns>PredictionOutput.</returns>
                public static PredictionOutput GetPrediction(PredictionInput input)
                {
                    return GetPrediction(input, true, true);
                }

                /// <summary>
                /// Gets the prediction.
                /// </summary>
                /// <param name="input">The input.</param>
                /// <param name="ft">if set to <c>true</c>, will add extra delay to the spell..</param>
                /// <param name="checkCollision">if set to <c>true</c>, checks collision.</param>
                /// <returns>PredictionOutput.</returns>
                internal static PredictionOutput GetPrediction(PredictionInput input, bool ft, bool checkCollision)
                {
                    PredictionOutput result = null;

                    if (!input.Unit.IsValidTarget(float.MaxValue, false))
                    {
                        return new PredictionOutput();
                    }

                    if (ft)
                    {
                        //Increase the delay due to the latency and server tick:
                        input.Delay += Game.Ping / 2000f + 0.06f;

                        if (input.Aoe)
                        {
                            //return AoePrediction.GetPrediction(input);
                        }
                    }

                    //Target too far away.
                    if (Math.Abs(input.Range - float.MaxValue) > float.Epsilon && input.Unit.Distance(input.RangeCheckFrom, true) > Math.Pow(input.Range * 1.5, 2))
                    {
                        return new PredictionOutput { Input = input };
                    }

                    //Unit is dashing.
                    if (input.Unit.IsDashing())
                    {
                        result = GetDashingPrediction(input);
                    }
                    else
                    {
                        //Unit is immobile.
                        var remainingImmobileT = UnitIsImmobileUntil(input.Unit);
                        if (remainingImmobileT >= 0d)
                        {
                            result = GetImmobilePrediction(input, remainingImmobileT);
                        }
                    }

                    //Normal prediction
                    if (result == null)
                    {
                        result = GetPositionOnPath(input, input.Unit.GetWaypoints(), input.Unit.MoveSpeed);
                    }

                    //Check if the unit position is in range
                    if (Math.Abs(input.Range - float.MaxValue) > float.Epsilon)
                    {
                        if (result.Hitchance >= HitChanceEx.High && input.RangeCheckFrom.Distance(input.Unit.Position, true) > Math.Pow(input.Range + input.RealRadius * 3 / 4, 2))
                        {
                            result.Hitchance = HitChanceEx.Medium;
                        }

                        if (input.RangeCheckFrom.Distance(result.UnitPosition, true) > Math.Pow(input.Range + (input.Type == SkillshotTypeEx.SkillshotCircle ? input.RealRadius : 0), 2))
                        {
                            result.Hitchance = HitChanceEx.OutOfRange;
                        }

                        if (input.RangeCheckFrom.Distance(result.CastPosition, true) > Math.Pow(input.Range, 2))
                        {
                            if (result.Hitchance != HitChanceEx.OutOfRange)
                            {
                                result.CastPosition = input.RangeCheckFrom + input.Range * (result.UnitPosition - input.RangeCheckFrom).To2D().Normalized().To3D();
                            }
                            else
                            {
                                result.Hitchance = HitChanceEx.OutOfRange;
                            }
                        }
                    }

                    //Check for collision
                    if (checkCollision && input.Collision)
                    {
                        var positions = new List<Vector3> { result.UnitPosition, result.CastPosition, input.Unit.Position };
                        var originalUnit = input.Unit;
                        result.CollisionObjects = CollisionEx.GetCollision(positions, input);
                        result.CollisionObjects.RemoveAll(x => x.NetworkId == originalUnit.NetworkId);
                        result.Hitchance = result.CollisionObjects.Count > 0 ? HitChanceEx.Collision : result.Hitchance;
                    }

                    //Set hit chance
                    if (result.Hitchance == HitChanceEx.High || result.Hitchance == HitChanceEx.VeryHigh)
                    {
                        result = WayPointAnalysis(result, input);
                    }

                    return result;
                }

                internal static PredictionOutput WayPointAnalysis(PredictionOutput result, PredictionInput input)
                {
                    if (!(input.Unit is AIHeroClient) || input.Radius == 1)
                    {
                        result.Hitchance = HitChanceEx.VeryHigh;
                        return result;
                    }

                    if (UnitTracker.GetSpecialSpellEndTime(input.Unit) > 100 || input.Unit.HasBuff("Recall") || (UnitTracker.GetLastStopMoveTime(input.Unit) < 100 && input.Unit.IsRooted))
                    {
                        result.Hitchance = HitChanceEx.VeryHigh;
                        result.CastPosition = input.Unit.Position;
                        return result;
                    }

                    if (UnitTracker.GetLastVisableTime(input.Unit) < 100)
                    {
                        result.Hitchance = HitChanceEx.Medium;
                        return result;
                    }

                    var path = input.Unit.GetWaypoints();

                    var lastWaypiont = path.Last().To3D();

                    var distanceUnitToWaypoint = lastWaypiont.Distance(input.Unit.ServerPosition);
                    var distanceFromToUnit = input.From.Distance(input.Unit.ServerPosition);
                    var distanceFromToWaypoint = lastWaypiont.Distance(input.From);

                    var pos1 = lastWaypiont.To2D() - input.Unit.Position.To2D();
                    var pos2 = input.From.To2D() - input.Unit.Position.To2D();
                    var getAngle = pos1.AngleBetween(pos2);

                    var speedDelay = distanceFromToUnit / input.Speed;

                    if (Math.Abs(input.Speed - float.MaxValue) < float.Epsilon)
                        speedDelay = 0;

                    var totalDelay = speedDelay + input.Delay;
                    var moveArea = input.Unit.MoveSpeed * totalDelay;
                    var fixRange = moveArea * 0.35f;
                    float pathMinLen = 1000;

                    if (input.Type == SkillshotTypeEx.SkillshotCircle)
                    {
                        fixRange -= input.Radius / 2;
                    }

                    if (distanceFromToWaypoint <= distanceFromToUnit && distanceFromToUnit > input.Range - fixRange)
                    {
                        result.Hitchance = HitChanceEx.Medium;
                        return result;
                    }

                    if (distanceUnitToWaypoint > 0)
                    {
                        if (getAngle < 20 || getAngle > 160 || (getAngle > 130 && distanceUnitToWaypoint > 400))
                        {
                            result.Hitchance = HitChanceEx.VeryHigh;
                            return result;
                        }

                        var points = new List<Vector2>(); //CirclePoints(15, 350, input.Unit.Position).Where(x => x.IsWall()).ToList();

                        if (points.Count > 2)
                        {
                            var runOutWall = true;
                            foreach (var point in points)
                            {
                                if (input.Unit.Position.Distance(point) > lastWaypiont.Distance(point))
                                {
                                    runOutWall = false;
                                }
                            }
                            if (runOutWall)
                            {
                                result.Hitchance = HitChanceEx.VeryHigh;
                                return result;
                            }
                        }
                        else if (UnitTracker.GetLastNewPathTime(input.Unit) > 250 && input.Delay < 0.3)
                        {
                            result.Hitchance = HitChanceEx.VeryHigh;
                            return result;
                        }
                    }

                    if (distanceUnitToWaypoint > 0 && distanceUnitToWaypoint < 100)
                    {
                        result.Hitchance = HitChanceEx.Medium;
                        return result;
                    }

                    if (input.Unit.GetWaypoints().Count == 1)
                    {
                        if (UnitTracker.GetLastAutoAttackTime(input.Unit) < 0.1d && totalDelay < 0.7)
                        {
                            result.Hitchance = HitChanceEx.VeryHigh;
                            return result;
                        }
                        if (input.Unit.Spellbook.IsAutoAttacking)
                        {
                            result.Hitchance = HitChanceEx.High;
                            return result;
                        }
                        else if (UnitTracker.GetLastStopMoveTime(input.Unit) < 800)
                        {
                            result.Hitchance = HitChanceEx.High;
                            return result;
                        }
                        else
                        {
                            result.Hitchance = HitChanceEx.VeryHigh;
                            return result;
                        }
                    }

                    if (UnitTracker.SpamSamePlace(input.Unit))
                    {
                        result.Hitchance = HitChanceEx.VeryHigh;
                        return result;
                    }

                    if (distanceFromToUnit < 250)
                    {
                        result.Hitchance = HitChanceEx.VeryHigh;
                        return result;
                    }
                    else if (input.Unit.MoveSpeed < 250)
                    {
                        result.Hitchance = HitChanceEx.VeryHigh;
                        return result;
                    }
                    else if (distanceFromToWaypoint < 250)
                    {
                        result.Hitchance = HitChanceEx.VeryHigh;
                        return result;
                    }

                    if (distanceUnitToWaypoint > pathMinLen)
                    {
                        result.Hitchance = HitChanceEx.VeryHigh;
                        return result;
                    }

                    if (input.Unit.HealthPercent < 20 || ObjectManager.Player.HealthPercent < 20)
                    {
                        result.Hitchance = HitChanceEx.VeryHigh;
                        return result;
                    }

                    if (input.Type == SkillshotTypeEx.SkillshotCircle)
                    {
                        if (UnitTracker.GetLastNewPathTime(input.Unit) < 100 && distanceUnitToWaypoint > fixRange)
                        {
                            result.Hitchance = HitChanceEx.VeryHigh;
                            return result;
                        }
                    }

                    return result;
                }

                public static List<Vector3> CirclePoints(float CircleLineSegmentN, float radius, Vector3 position)
                {
                    var points = new List<Vector3>();
                    for (var i = 1; i <= CircleLineSegmentN; i++)
                    {
                        var angle = i * 2 * Math.PI / CircleLineSegmentN;
                        var point = new Vector3(position.X + radius * (float) Math.Cos(angle), position.Y + radius * (float) Math.Sin(angle), position.Z);
                        points.Add(point);
                    }
                    return points;
                }

                internal class PathInfo
                {
                    public Vector2 Position { get; set; }
                    public float Time { get; set; }
                }

                internal class Spells
                {
                    public string name { get; set; }
                    public double duration { get; set; }
                }

                internal class UnitTrackerInfo
                {
                    public int NetworkId { get; set; }
                    public int AaTick { get; set; }
                    public int NewPathTick { get; set; }
                    public int StopMoveTick { get; set; }
                    public int LastInvisableTick { get; set; }
                    public int SpecialSpellFinishTick { get; set; }
                    public List<PathInfo> PathBank = new List<PathInfo>();
                }

                internal static class UnitTracker
                {
                    public static List<UnitTrackerInfo> UnitTrackerInfoList = new List<UnitTrackerInfo>();
                    private static readonly List<AIHeroClient> Champion = new List<AIHeroClient>();
                    private static readonly List<Spells> spells = new List<Spells>();

                    static UnitTracker()
                    {
                        spells.Add(new Spells() { name = "katarinar", duration = 1 }); //Katarinas R
                        spells.Add(new Spells() { name = "drain", duration = 1 }); //Fiddle W
                        spells.Add(new Spells() { name = "crowstorm", duration = 1 }); //Fiddle R
                        spells.Add(new Spells() { name = "consume", duration = 0.5 }); //Nunu Q
                        spells.Add(new Spells() { name = "absolutezero", duration = 1 }); //Nunu R
                        spells.Add(new Spells() { name = "staticfield", duration = 0.5 }); //Blitzcrank R
                        spells.Add(new Spells() { name = "cassiopeiapetrifyinggaze", duration = 0.5 }); //Cassio's R
                        spells.Add(new Spells() { name = "ezrealtrueshotbarrage", duration = 1 }); //Ezreal's R
                        spells.Add(new Spells() { name = "galioidolofdurand", duration = 1 }); //Ezreal's R                                                                   
                        spells.Add(new Spells() { name = "luxmalicecannon", duration = 1 }); //Lux R
                        spells.Add(new Spells() { name = "reapthewhirlwind", duration = 1 }); //Jannas R
                        spells.Add(new Spells() { name = "jinxw", duration = 0.6 }); //jinxW
                        spells.Add(new Spells() { name = "jinxr", duration = 0.6 }); //jinxR
                        spells.Add(new Spells() { name = "missfortunebullettime", duration = 1 }); //MissFortuneR
                        spells.Add(new Spells() { name = "shenstandunited", duration = 1 }); //ShenR
                        spells.Add(new Spells() { name = "threshe", duration = 0.4 }); //ThreshE
                        spells.Add(new Spells() { name = "threshrpenta", duration = 0.75 }); //ThreshR
                        spells.Add(new Spells() { name = "threshq", duration = 0.75 }); //ThreshQ
                        spells.Add(new Spells() { name = "infiniteduress", duration = 1 }); //Warwick R
                        spells.Add(new Spells() { name = "meditate", duration = 1 }); //yi W
                        spells.Add(new Spells() { name = "alzaharnethergrasp", duration = 1 }); //Malza R
                        spells.Add(new Spells() { name = "lucianq", duration = 0.5 }); //Lucian Q
                        spells.Add(new Spells() { name = "caitlynpiltoverpeacemaker", duration = 0.5 }); //Caitlyn Q
                        spells.Add(new Spells() { name = "velkozr", duration = 0.5 }); //Velkoz R 
                        spells.Add(new Spells() { name = "jhinr", duration = 2 }); //Jhin R 

                        foreach (var hero in ObjectManager.Get<AIHeroClient>())
                        {
                            Champion.Add(hero);
                            UnitTrackerInfoList.Add(new UnitTrackerInfo()
                            {
                                NetworkId = hero.NetworkId,
                                AaTick = Core.GameTickCount,
                                StopMoveTick = Core.GameTickCount,
                                NewPathTick = Core.GameTickCount,
                                SpecialSpellFinishTick = Core.GameTickCount,
                                LastInvisableTick = Core.GameTickCount
                            });
                        }

                        Obj_AI_Base.OnProcessSpellCast += Obj_AI_Base_OnProcessSpellCast;
                        Obj_AI_Base.OnNewPath += Obj_AI_Hero_OnNewPath;
                    }

                    private static void Obj_AI_Hero_OnNewPath(Obj_AI_Base sender, GameObjectNewPathEventArgs args)
                    {
                        if (sender is AIHeroClient)
                        {
                            var item = UnitTrackerInfoList.Find(x => x.NetworkId == sender.NetworkId);
                            if (args.Path.Count() == 1) // STOP MOVE DETECTION
                                item.StopMoveTick = Core.GameTickCount;

                            item.NewPathTick = Core.GameTickCount;
                            item.PathBank.Add(new PathInfo() { Position = args.Path.Last().To2D(), Time = Core.GameTickCount });

                            if (item.PathBank.Count > 3)
                                item.PathBank.RemoveAt(0);
                        }
                    }

                    private static void Obj_AI_Base_OnProcessSpellCast(Obj_AI_Base sender, GameObjectProcessSpellCastEventArgs args)
                    {
                        if (sender is AIHeroClient)
                        {
                            if (args.SData.IsAutoAttack())
                                UnitTrackerInfoList.Find(x => x.NetworkId == sender.NetworkId).AaTick = Core.GameTickCount;
                            else
                            {
                                var foundSpell = spells.Find(x => args.SData.Name.ToLower() == x.name.ToLower());
                                if (foundSpell != null)
                                {
                                    UnitTrackerInfoList.Find(x => x.NetworkId == sender.NetworkId).SpecialSpellFinishTick = Core.GameTickCount + (int) (foundSpell.duration * 1000);
                                }
                                else if (sender.Spellbook.IsAutoAttacking || sender.IsRooted || !sender.CanMove)
                                {
                                    UnitTrackerInfoList.Find(x => x.NetworkId == sender.NetworkId).SpecialSpellFinishTick = Core.GameTickCount + 100;
                                }
                            }
                        }
                    }

                    public static bool SpamSamePlace(Obj_AI_Base unit)
                    {
                        var TrackerUnit = UnitTrackerInfoList.Find(x => x.NetworkId == unit.NetworkId);
                        if (TrackerUnit.PathBank.Count < 3)
                            return false;
                        if (TrackerUnit.PathBank[2].Time - TrackerUnit.PathBank[1].Time < 180 && Core.GameTickCount - TrackerUnit.PathBank[2].Time < 90)
                        {
                            var C = TrackerUnit.PathBank[1].Position;
                            var A = TrackerUnit.PathBank[2].Position;

                            var B = unit.Position.To2D();

                            var AB = Math.Pow(A.X - B.X, 2) + Math.Pow(A.Y - B.Y, 2);
                            var BC = Math.Pow(B.X - C.X, 2) + Math.Pow(B.Y - C.Y, 2);
                            var AC = Math.Pow(A.X - C.X, 2) + Math.Pow(A.Y - C.Y, 2);

                            if (TrackerUnit.PathBank[1].Position.Distance(TrackerUnit.PathBank[2].Position) < 50)
                            {
                                return true;
                            }
                            else if (Math.Cos((AB + BC - AC) / (2 * Math.Sqrt(AB) * Math.Sqrt(BC))) * 180 / Math.PI < 31)
                            {
                                return true;
                            }
                            else
                                return false;
                        }
                        else
                            return false;
                    }

                    public static List<Vector2> GetPathWayCalc(Obj_AI_Base unit)
                    {
                        var points = new List<Vector2>();
                        points.Add(unit.ServerPosition.To2D());
                        return points;
                    }

                    public static double GetSpecialSpellEndTime(Obj_AI_Base unit)
                    {
                        var TrackerUnit = UnitTrackerInfoList.Find(x => x.NetworkId == unit.NetworkId);
                        return TrackerUnit.SpecialSpellFinishTick - Core.GameTickCount;
                    }

                    public static double GetLastAutoAttackTime(Obj_AI_Base unit)
                    {
                        var TrackerUnit = UnitTrackerInfoList.Find(x => x.NetworkId == unit.NetworkId);
                        return Core.GameTickCount - TrackerUnit.AaTick;
                    }

                    public static double GetLastNewPathTime(Obj_AI_Base unit)
                    {
                        var TrackerUnit = UnitTrackerInfoList.Find(x => x.NetworkId == unit.NetworkId);
                        return Core.GameTickCount - TrackerUnit.NewPathTick;
                    }

                    public static double GetLastVisableTime(Obj_AI_Base unit)
                    {
                        var TrackerUnit = UnitTrackerInfoList.Find(x => x.NetworkId == unit.NetworkId);

                        return Core.GameTickCount - TrackerUnit.LastInvisableTick;
                    }

                    public static double GetLastStopMoveTime(Obj_AI_Base unit)
                    {
                        var TrackerUnit = UnitTrackerInfoList.Find(x => x.NetworkId == unit.NetworkId);

                        return Core.GameTickCount - TrackerUnit.StopMoveTick;
                    }
                }

                /// <summary>
                /// Gets the dashing prediction.
                /// </summary>
                /// <param name="input">The input.</param>
                /// <returns>PredictionOutput.</returns>
                internal static PredictionOutput GetDashingPrediction(PredictionInput input)
                {
                    var dashData = input.Unit.GetDashInfo();
                    var result = new PredictionOutput { Input = input };

                    //Normal dashes.
                    if (true /*!dashData.IsBlink*/)
                    {
                        //Mid air:
                        var endP = dashData.Path.Last();
                        var dashPred = GetPositionOnPath(input, new List<Vector2> { input.Unit.ServerPosition.To2D(), endP }, dashData.Speed);
                        if (dashPred.Hitchance >= HitChanceEx.High && dashPred.UnitPosition.To2D().Distance(input.Unit.Position.To2D(), endP, true) < 200)
                        {
                            dashPred.CastPosition = dashPred.UnitPosition;
                            dashPred.Hitchance = HitChanceEx.Dashing;
                            return dashPred;
                        }

                        //At the end of the dash:
                        if (dashData.Path.PathLength() > 200)
                        {
                            var timeToPoint = input.Delay / 2f + input.From.To2D().Distance(endP) / input.Speed - 0.25f;
                            if (timeToPoint <= input.Unit.Distance(endP) / dashData.Speed + input.RealRadius / input.Unit.MoveSpeed)
                            {
                                return new PredictionOutput { CastPosition = endP.To3D(), UnitPosition = endP.To3D(), Hitchance = HitChanceEx.Dashing };
                            }
                        }

                        result.CastPosition = dashData.Path.Last().To3D();
                        result.UnitPosition = result.CastPosition;

                        //Figure out where the unit is going.
                    }

                    return result;
                }

                /// <summary>
                /// Gets the immobile prediction.
                /// </summary>
                /// <param name="input">The input.</param>
                /// <param name="remainingImmobileT">The remaining immobile t.</param>
                /// <returns>PredictionOutput.</returns>
                internal static PredictionOutput GetImmobilePrediction(PredictionInput input, double remainingImmobileT)
                {
                    var timeToReachTargetPosition = input.Delay + input.Unit.Distance(input.From) / input.Speed;

                    if (timeToReachTargetPosition <= remainingImmobileT + input.RealRadius / input.Unit.MoveSpeed)
                    {
                        return new PredictionOutput { CastPosition = input.Unit.ServerPosition, UnitPosition = input.Unit.Position, Hitchance = HitChanceEx.Immobile };
                    }

                    return new PredictionOutput
                    {
                        Input = input,
                        CastPosition = input.Unit.ServerPosition,
                        UnitPosition = input.Unit.ServerPosition,
                        Hitchance = HitChanceEx.High
                        /*timeToReachTargetPosition - remainingImmobileT + input.RealRadius / input.Unit.MoveSpeed < 0.4d ? HitChance.High : HitChance.Medium*/
                    };
                }

                /// <summary>
                /// Gets the standard prediction.
                /// </summary>
                /// <param name="input">The input.</param>
                /// <returns>PredictionOutput.</returns>
                internal static PredictionOutput GetStandardPrediction(PredictionInput input)
                {
                    var speed = input.Unit.MoveSpeed;

                    if (input.Unit.Distance(input.From, true) < 200 * 200)
                    {
                        //input.Delay /= 2;
                        speed /= 1.5f;
                    }

                    var result = GetPositionOnPath(input, input.Unit.GetWaypoints(), speed);

                    return result;
                }

                /// <summary>
                /// Gets the time the unit is immobile untill.
                /// </summary>
                /// <param name="unit">The unit.</param>
                /// <returns>System.Double.</returns>
                internal static double UnitIsImmobileUntil(Obj_AI_Base unit)
                {
                    var result =
                        unit.Buffs.Where(
                            buff =>
                                buff.IsActive && Game.Time <= buff.EndTime &&
                                (buff.Type == BuffType.Charm || buff.Type == BuffType.Knockup || buff.Type == BuffType.Stun || buff.Type == BuffType.Suppression || buff.Type == BuffType.Snare))
                            .Aggregate(0d, (current, buff) => Math.Max(current, buff.EndTime));
                    return (result - Game.Time);
                }

                /// <summary>
                /// Gets the position on path.
                /// </summary>
                /// <param name="input">The input.</param>
                /// <param name="path">The path.</param>
                /// <param name="speed">The speed.</param>
                /// <returns>PredictionOutput.</returns>
                internal static PredictionOutput GetPositionOnPath(PredictionInput input, List<Vector2> path, float speed = -1)
                {
                    if (input.Unit.Distance(input.From, true) < 250 * 250)
                    {
                        //input.Delay /= 2;
                        speed /= 1.5f;
                    }

                    speed = (Math.Abs(speed - (-1)) < float.Epsilon) ? input.Unit.MoveSpeed : speed;

                    if (path.Count <= 1 || (input.Unit.Spellbook.IsAutoAttacking && !input.Unit.IsDashing()))
                    {
                        return new PredictionOutput { Input = input, UnitPosition = input.Unit.ServerPosition, CastPosition = input.Unit.ServerPosition, Hitchance = HitChanceEx.High };
                    }

                    var pLength = path.PathLength();

                    //Skillshots with only a delay
                    if (pLength >= input.Delay * speed - input.RealRadius && Math.Abs(input.Speed - float.MaxValue) < float.Epsilon)
                    {
                        var tDistance = input.Delay * speed - input.RealRadius;

                        for (var i = 0; i < path.Count - 1; i++)
                        {
                            var a = path[i];
                            var b = path[i + 1];
                            var d = a.Distance(b);

                            if (d >= tDistance)
                            {
                                var direction = (b - a).Normalized();

                                var cp = a + direction * tDistance;
                                var p = a + direction * ((i == path.Count - 2) ? Math.Min(tDistance + input.RealRadius, d) : (tDistance + input.RealRadius));

                                return new PredictionOutput { Input = input, CastPosition = cp.To3D(), UnitPosition = p.To3D(), Hitchance = HitChanceEx.High };
                            }

                            tDistance -= d;
                        }
                    }

                    //Skillshot with a delay and speed.
                    if (pLength >= input.Delay * speed - input.RealRadius && Math.Abs(input.Speed - float.MaxValue) > float.Epsilon)
                    {
                        var d = input.Delay * speed - input.RealRadius;
                        if (input.Type == SkillshotTypeEx.SkillshotLine || input.Type == SkillshotTypeEx.SkillshotCone)
                        {
                            if (input.From.Distance(input.Unit.ServerPosition, true) < 200 * 200)
                            {
                                d = input.Delay * speed;
                            }
                        }

                        path = path.CutPath(d);
                        var tT = 0f;
                        for (var i = 0; i < path.Count - 1; i++)
                        {
                            var a = path[i];
                            var b = path[i + 1];
                            var tB = a.Distance(b) / speed;
                            var direction = (b - a).Normalized();
                            a = a - speed * tT * direction;
                            var sol = Geometry.VectorMovementCollision(a, b, speed, input.From.To2D(), input.Speed, tT);
                            var t = (float) sol[0];
                            var pos = (Vector2) sol[1];

                            if (pos.IsValid() && t >= tT && t <= tT + tB)
                            {
                                if (pos.Distance(b, true) < 20)
                                    break;
                                var p = pos + input.RealRadius * direction;

                                if (input.Type == SkillshotTypeEx.SkillshotLine && false)
                                {
                                    var alpha = (input.From.To2D() - p).AngleBetween(a - b);
                                    if (alpha > 30 && alpha < 180 - 30)
                                    {
                                        var beta = (float) Math.Asin(input.RealRadius / p.Distance(input.From));
                                        var cp1 = input.From.To2D() + (p - input.From.To2D()).Rotated(beta);
                                        var cp2 = input.From.To2D() + (p - input.From.To2D()).Rotated(-beta);

                                        pos = cp1.Distance(pos, true) < cp2.Distance(pos, true) ? cp1 : cp2;
                                    }
                                }

                                return new PredictionOutput { Input = input, CastPosition = pos.To3D(), UnitPosition = p.To3D(), Hitchance = HitChanceEx.High };
                            }
                            tT += tB;
                        }
                    }

                    var position = path.Last();
                    return new PredictionOutput { Input = input, CastPosition = position.To3D(), UnitPosition = position.To3D(), Hitchance = HitChanceEx.Medium };
                }
            }

            internal static Menu.Menu Menu { get; set; }

            internal static int Ping
            {
                get { return Game.Ping; }
            }

            internal static int ExtraHitbox
            {
                get { return Menu["extraHitboxRadius"].Cast<Slider>().CurrentValue + 10; }
            }

            internal static int RangeAdjustment
            {
                get { return Menu["skillshotRangeAdjustment"].Cast<Slider>().CurrentValue; }
            }

            internal static void Initialize()
            {
                Menu = MainMenu.AddMenu("Prediction", "Prediction");
                Manager.Menu = Menu;
                Manager.Menu.Add("PredictionSelected", new ComboBox("Prediction Selected:", new List<string> { "SDK Prediction" }, 0));
                Manager.Suscribe("SDK Prediction", GetSdkPrediction);
                Manager.Suscribe("SDK Beta Prediction", GetSdkBetaPrediction);

                Menu.AddGroupLabel("General");
                Menu.Add("skillshotRangeAdjustment", new Slider("Skillshot range scale {0}%", 100, 50, 120));
                Menu.AddLabel("It allows you to adjust the skillshot range.");

                Menu.AddGroupLabel("Collision");
                Menu.Add("extraHitboxRadius", new Slider("Extra Hitbox Radius", 0, 0, 80));
                Menu.AddLabel("Add more hitbox to objects when calculating collision.");
                Obj_AI_Base.OnProcessSpellCast += OnBasicAttack;
                Obj_AI_Base.OnProcessSpellCast += OnProcessSpellCast;
                Obj_AI_Base.OnNewPath += OnNewPath;
            }

            private static Dictionary<int, List<Vector2>> _pathGroup = new Dictionary<int, List<Vector2>>();
            private static Dictionary<int, Vector2> _tendencyDestination = new Dictionary<int, Vector2>();
            private static Dictionary<int, float> _moveTime = new Dictionary<int, float>();

            private static void OnNewPath(Obj_AI_Base sender, GameObjectNewPathEventArgs args)
            {
                if (!args.IsDash && sender.Type == GameObjectType.AIHeroClient)
                {
                    List<Vector2> group;

                    if (!_pathGroup.TryGetValue(sender.NetworkId, out group))
                    {
                        group = new List<Vector2>();
                        _pathGroup[sender.NetworkId] = group;
                    }

                    var destination = args.Path.Last().To2D();
                    var center = group.Any() ? group.ToArray().CenterPoint() : destination;

                    const int tolerance = 200;

                    if (destination.Distance(center, true) <= tolerance.Pow())
                    {
                        group.Add(destination);
                        _tendencyDestination[sender.NetworkId] = group.ToArray().CenterPoint();
                    }
                    else
                    {
                        group.Clear();
                        _tendencyDestination[sender.NetworkId] = destination;
                    }
                }

                if (!args.IsDash && sender.Type == GameObjectType.AIHeroClient)
                {
                    _moveTime[sender.NetworkId] = Game.Time;
                }
            }

            private static Vector2 TendencyDestination(Obj_AI_Base unit)
            {
                var dash = unit.GetDashInfo();

                if (dash != null)
                {
                    return dash.EndPos.To2D();
                }

                if (unit.Type == GameObjectType.AIHeroClient)
                {
                    Vector2 tendency;

                    if (_tendencyDestination.TryGetValue(unit.NetworkId, out tendency))
                    {
                        return tendency;
                    }
                }

                return unit.Path.Last().To2D();
            }

            private static float TendencyFactor(Obj_AI_Base unit)
            {
                if (unit.Type == GameObjectType.AIHeroClient)
                {
                    float time;
                    _moveTime.TryGetValue(unit.NetworkId, out time);

                    List<Vector2> group;
                    _pathGroup.TryGetValue(unit.NetworkId, out group);

                    var factor = 1f;

                    if (Game.Time - time >= 0.15)
                    {
                        factor += 0.5f;
                    }

                    if (group != null && group.Count >= 3)
                    {
                        factor += 0.4f;
                    }

                    return factor;
                }

                return 2;
            }

            private static Dictionary<int, Tuple<float, float>> _heroActionDuration = new Dictionary<int, Tuple<float, float>>();

            private static void OnBasicAttack(Obj_AI_Base sender, GameObjectProcessSpellCastEventArgs args)
            {
                if (sender.Type == GameObjectType.AIHeroClient)
                {
                    var time = sender.AttackCastDelay * 1000;
                    _heroActionDuration[sender.NetworkId] = new Tuple<float, float>(Game.Time, time);
                }
            }

            private static void OnProcessSpellCast(Obj_AI_Base sender, GameObjectProcessSpellCastEventArgs args)
            {
                //=======================
                // 19/2/2016, patch 6.3
                // broken properties: sender.CanMove, args.SData.CastTime 
                //=======================

                if (sender.Type == GameObjectType.AIHeroClient)
                {
                    var time = args.SData.CastTime * 1000;

                    if (time == 0 && !sender.IsMoving)
                    {
                        time = 150;
                    }

                    _heroActionDuration[sender.NetworkId] = new Tuple<float, float>(Game.Time, time);
                }
            }

            private static float ActionDuration(Obj_AI_Base unit)
            {
                if (unit.Type == GameObjectType.AIHeroClient)
                {
                    Tuple<float, float> action;

                    if (_heroActionDuration.TryGetValue(unit.NetworkId, out action))
                    {
                        var time = action.Item2 - (Game.Time - action.Item1) * 1000;

                        return Math.Max(0, time);
                    }
                }

                return 0;
            }

            private static Obj_AI_Base[] GatherCollisionObjects(Vector2 center, float range, Obj_AI_Base[] ignoreUnits = null)
            {
                ignoreUnits = ignoreUnits ?? new Obj_AI_Base[] { };

                return
                    ObjectManager.Get<Obj_AI_Base>()
                        .Where(
                            unit =>
                                unit.IsValidTarget() && unit.IsEnemy && !unit.IsStructure() && unit.MaxHealth > 6 && unit.IsInRange(center, range) &&
                                ignoreUnits.All(ignore => ignore.Index != unit.Index))
                        .ToArray();
            }

            private static Vector2[] TerrainCollisionPoints(Geometry.Polygon polygon)
            {
                var list = new List<Vector2>();
                var points = polygon.Points;

                for (var i = 0; i < points.Count; i++)
                {
                    var point = points[i];
                    /*
                    if (point.IsWall())
                    {
                        list.Add(point);
                    }
                    */
                }

                return list.ToArray();
            }

            private static bool CollidesWithTerrain(Geometry.Polygon polygon)
            {
                return TerrainCollisionPoints(polygon).Length > 0;
            }

            private static bool CollidesWithTerrain(PredictionData data, Vector2 castPos)
            {
                return CollidesWithTerrain(data.GetSkillshotPolygon(castPos));
            }

            private static Manager.PredictionInput TransformPredictionData(Obj_AI_Base target, PredictionData data)
            {
                var input = new Manager.PredictionInput
                {
                    Target = target,
                    Delay = data.Delay / 1000f,
                    Speed = data.Speed,
                    From = data.SourcePosition,
                    RangeCheckFrom = data.SourcePosition,
                    Radius = data.Type == PredictionData.PredictionType.Cone ? data.Angle : data.Radius,
                    Range = data.Range,
                    CollisionTypes = TransformToCollisionTypes(data.AllowCollisionCount),
                    Type = TransformToSkillShotType(data.Type),
                };
                return input;
            }

            private static PredictionData.PredictionType TransformToPredictionType(SkillShotType type)
            {
                switch (type)
                {
                    case SkillShotType.Linear:
                        return PredictionData.PredictionType.Linear;
                    case SkillShotType.Circular:
                        return PredictionData.PredictionType.Circular;
                    case SkillShotType.Cone:
                        return PredictionData.PredictionType.Cone;
                }
                return PredictionData.PredictionType.Circular;
            }

            private static SkillShotType TransformToSkillShotType(PredictionData.PredictionType type)
            {
                switch (type)
                {
                    case PredictionData.PredictionType.Linear:
                        return SkillShotType.Linear;
                    case PredictionData.PredictionType.Circular:
                        return SkillShotType.Circular;
                    case PredictionData.PredictionType.Cone:
                        return SkillShotType.Cone;
                }
                return SkillShotType.Circular;
            }

            private static HashSet<CollisionType> TransformToCollisionTypes(int allowedCollisionCount)
            {
                var list = new HashSet<CollisionType>();
                switch (allowedCollisionCount)
                {
                    case 0:
                        list.Add(CollisionType.AiHeroClient);
                        list.Add(CollisionType.ObjAiMinion);
                        break;
                    case 1:
                        list.Add(CollisionType.ObjAiMinion);
                        break;
                    case -1:
                        list.Add(CollisionType.ObjAiMinion);
                        break;
                }
                return list;
            }

            private static int TransformToCollisionCount(ICollection<CollisionType> collisionTypes)
            {
                var allowedCollisionCount = int.MaxValue;
                if (collisionTypes.Contains(CollisionType.AiHeroClient))
                {
                    allowedCollisionCount = 0;
                }
                else if (collisionTypes.Contains(CollisionType.ObjAiMinion))
                {
                    allowedCollisionCount = -1;
                }
                return allowedCollisionCount;
            }

            private static PredictionResult TransformToPredictionResult(Manager.PredictionOutput output)
            {
                return new PredictionResult(output.CastPosition, output.PredictedPosition, output.HitChancePercent, output.CollisionObjects, TransformToCollisionCount(output.Input.CollisionTypes),
                    output.HitChance);
            }

            private static SkillshotTypeEx TransformToSkillshotTypeEx(SkillShotType type)
            {
                switch (type)
                {
                    case SkillShotType.Linear:
                        return SkillshotTypeEx.SkillshotLine;
                    case SkillShotType.Circular:
                        return SkillshotTypeEx.SkillshotCircle;
                    case SkillShotType.Cone:
                        return SkillshotTypeEx.SkillshotCone;
                }
                return SkillshotTypeEx.SkillshotCircle;
            }

            private static Manager.PredictionOutput GetSdkBetaPrediction(Manager.PredictionInput input)
            {
                var betaInput = new PredictionInput
                {
                    Collision = input.CollisionTypes.Count > 0,
                    Delay = input.Delay,
                    From = input.From,
                    Radius = input.Radius,
                    Range = input.Range,
                    Speed = input.Speed,
                    Type = TransformToSkillshotTypeEx(input.Type),
                    Unit = input.Target
                };

                var result = PredictionEx.GetPrediction(betaInput);
                var hit = (int) result.Hitchance;
                var output = new Manager.PredictionOutput(input)
                {
                    CastPosition = result.CastPosition,
                    PredictedPosition = result.UnitPosition,
                    HitChancePercent = (int) (result.Hitchance - 1) * 25 - 10,
                    HitChance = hit < 3 ? HitChance.Impossible : (HitChance) hit,
                };
                output.CollisionGameObjects.AddRange(result.CollisionObjects.ToArray());
                return output;
            }

            private static Manager.PredictionOutput GetSdkPrediction(Manager.PredictionInput input)
            {
                if (input == null || input.Target == null)
                {
                    throw new ArgumentNullException("input");
                }
                var sdkInput = new PredictionData(TransformToPredictionType(input.Type), (int) input.Range, (int) input.Radius, (int) input.Radius, (int) (1000 * input.Delay), (int) input.Speed,
                    TransformToCollisionCount(input.CollisionTypes), input.From);
                var sdkOutput = GetPrediction(input.Target, sdkInput);
                var output = new Manager.PredictionOutput(input)
                {
                    CastPosition = sdkOutput.CastPosition,
                    HitChancePercent = sdkOutput.HitChancePercent,
                    HitChance = sdkOutput.HitChance,
                    PredictedPosition = sdkOutput.UnitPosition,
                };
                output.CollisionGameObjects.AddRange(sdkOutput.CollisionObjects);
                return output;
            }

            public static Manager.PredictionOutput GetPrediction(Manager.PredictionInput input)
            {
                if (input == null || input.Target == null)
                {
                    throw new ArgumentNullException("input");
                }
                return Manager.GetPrediction(input);
            }

            /// <summary>
            /// Predict cast position and collision for a specific target.
            /// </summary>
            /// <param name="target"> The target to predict.</param>
            /// <param name="data"> The prediction data.</param>
            /// <param name="skipCollision"> Skip collision checks.</param>
            public static PredictionResult GetPrediction(Obj_AI_Base target, PredictionData data, bool skipCollision = false)
            {
                if (target == null)
                {
                    throw new ArgumentNullException("target");
                }

                if (data == null)
                {
                    throw new ArgumentNullException("data");
                }

                if (!Manager.PredictionSelected.Equals("SDK Prediction"))
                {
                    return TransformToPredictionResult(Manager.GetPrediction(TransformPredictionData(target, data)));
                }
                Vector3 unitPos;
                Vector3 start;
                Vector3 end;
                float speed;

                var delay = data.Delay + Ping;
                var dash = target.GetDashInfo();

                if (dash != null)
                {
                    const int dashSpeed = 3000;

                    speed = dashSpeed;
                    start = dash.StartPos;
                    end = dash.EndPos;

                    unitPos = end;
                }
                else if (!target.IsMoving)
                {
                    speed = 0;
                    start = target.ServerPosition;
                    end = target.ServerPosition;

                    unitPos = end;
                }
                else
                {
                    var path = GetRealPath(target);

                    speed = target.MoveSpeed;
                    start = PredictUnitPosition(target, delay).To3DWorld();
                    end = path.Last();

                    unitPos = start;

                    for (var i = 0; i < path.Length - 1; i++)
                    {
                        var pathStart = path[i];
                        var pathEnd = path[i + 1];

                        if (Geometry.PointInLineSegment(pathStart.To2D(), pathEnd.To2D(), start.To2D()))
                        {
                            end = pathEnd;
                            break;
                        }
                    }

                    var point = Collision.GetCollisionPoint(start.To2D(), end.To2D(), data.SourcePosition.To2D(), speed, data.Speed);

                    if (!Geometry.PointInLineSegment(start.To2D(), end.To2D(), point) && end != path.Last())
                    {
                        return PredictionResult.ResultImpossible(data);
                    }
                }

                var castPosition = Collision.GetCollisionPoint(start.To2D(), end.To2D(), data.SourcePosition.To2D(), speed, data.Speed);
                var tendency = TendencyDestination(target);
                var hitbox = target.BoundingRadius;

                if (!Geometry.PointInLineSegment(start.To2D(), end.To2D(), castPosition))
                {
                    var oldPoint = castPosition;
                    castPosition = end.To2D();

                    if (tendency != end.To2D())
                    {
                        var distance1 = castPosition.Distance(oldPoint, true);
                        var distance2 = castPosition.Distance(tendency, true);

                        if (distance1 > distance2)
                        {
                            castPosition = castPosition.Extend(tendency, castPosition.Distance(tendency));
                        }
                        else
                        {
                            castPosition = castPosition.Extend(tendency, data.Radius + hitbox / 4);
                        }
                    }
                }
                else
                {
                    castPosition = castPosition.Extend(start, data.Radius / 2);
                }

                if (CollidesWithTerrain(data, castPosition))
                {
                    castPosition = castPosition.Extend(start, data.Radius);
                    castPosition = castPosition.Extend(tendency, data.Radius / 2);
                }

                var scanRange = data.Range * 2f;
                var collisionObjects = new List<Obj_AI_Base>();

                if (!skipCollision)
                {
                    var collisionCheck = data.GetCollisionCalculator(castPosition);

                    foreach (var unit in GatherCollisionObjects(data.SourcePosition.To2D(), scanRange, new[] { target }))
                    {
                        if (collisionCheck(target, unit))
                        {
                            collisionObjects.Add(unit);
                        }
                    }
                }

                if (!data.GetSkillshotPolygon(castPosition).IsInside(unitPos))
                {
                    return PredictionResult.ResultImpossible(data, castPosition.To3DWorld(), unitPos);
                }

                var actionDuration = ActionDuration(target);
                var travelTime = data.SourcePosition.Distance(castPosition) / data.Speed;
                var escapeTime = hitbox / target.MoveSpeed;
                var reactionTime = Math.Max(0, travelTime - escapeTime - actionDuration);
                var minimumTime = Math.Max(0.001, data.Range / data.Speed);
                var bonus = (float) Math.Max(0.05, (1 - reactionTime / minimumTime) * 100);

                var hitchance = 10 + bonus * TendencyFactor(target);

                if (target.Type != GameObjectType.AIHeroClient)
                {
                    hitchance = 80;
                }

                var hitChanceOverride = HitChance.Unknown;

                if (target.GetMovementBlockedDebuffDuration() > travelTime)
                {
                    hitChanceOverride = HitChance.Immobile;
                    hitchance = 100;
                }

                if (dash != null)
                {
                    hitChanceOverride = HitChance.Dashing;
                    hitchance = 90;
                }

                return new PredictionResult(castPosition.To3DWorld(), unitPos, hitchance, collisionObjects.ToArray(), data.AllowCollisionCount, hitChanceOverride);
            }

            /// <summary>
            /// Predicts all the possible positions to hit as many targets as possible from a predifined group of targets.
            /// </summary>
            /// <param name="targets"> The targets to predict. If null then the enemy heroes will be chosen instead.</param>
            /// <param name="data"> The prediction data.</param>
            public static PredictionResult[] GetPredictionAoe(Obj_AI_Base[] targets, PredictionData data)
            {
                if (data == null)
                {
                    throw new ArgumentNullException("data");
                }

                targets = targets ?? EntityManager.Heroes.Enemies.Cast<Obj_AI_Base>().ToArray();

                var results = new Dictionary<Obj_AI_Base, PredictionResult>();
                foreach (var unit in targets)
                {
                    var result = GetPrediction(unit, data, true);

                    if (result.HitChance > HitChance.Collision)
                    {
                        results.Add(unit, result);
                    }
                }

                if (results.Count == 0)
                {
                    return new PredictionResult[] { };
                }

                var predictionResults = new List<PredictionResult>();

                foreach (var group in data.GetAoeGroups(results))
                {
                    var castPoint = group.ToArray().CenterPoint();
                    var collisionCheck = data.GetCollisionCalculator(castPoint);
                    var collisionObjects = new List<Obj_AI_Base>();

                    foreach (var unit in GatherCollisionObjects(data.SourcePosition.To2D(), data.Range * 2))
                    {
                        if (collisionCheck(unit, unit))
                        {
                            collisionObjects.Add(unit);
                        }
                    }

                    predictionResults.Add(new PredictionResult(castPoint.To3DWorld(), Vector3.Zero, 100, collisionObjects.ToArray(), -1));
                }

                return predictionResults.ToArray();
            }

            public static PredictionResult PredictLinearMissile(
                Obj_AI_Base target,
                float range,
                int radius,
                int delay,
                float speed,
                int allowedCollisionCount,
                Vector3? sourcePosition = null,
                bool ignoreCollision = false)
            {
                var data = new PredictionData(PredictionData.PredictionType.Linear, (int) range, radius, 0, delay, (int) speed, allowedCollisionCount, sourcePosition);
                return GetPrediction(target, data, ignoreCollision);
            }

            public static PredictionResult PredictCircularMissile(Obj_AI_Base target, float range, int radius, int delay, float speed, Vector3? sourcePosition = null, bool ignoreCollision = false)
            {
                var data = new PredictionData(PredictionData.PredictionType.Circular, (int) range, radius, 0, delay, (int) speed, -1, sourcePosition);
                return GetPrediction(target, data, ignoreCollision);
            }

            public static PredictionResult PredictConeSpell(Obj_AI_Base target, float range, int angle, int delay, float speed, Vector3? sourcePosition = null, bool ignoreCollision = false)
            {
                var data = new PredictionData(PredictionData.PredictionType.Cone, (int) range, 0, angle, delay, (int) speed, -1, sourcePosition);
                return GetPrediction(target, data, ignoreCollision);
            }

            public static PredictionResult[] PredictCircularMissileAoe(Obj_AI_Base[] targets, float range, int radius, int delay, float speed, Vector3? sourcePosition = null)
            {
                var data = new PredictionData(PredictionData.PredictionType.Circular, (int) range, radius, 0, delay, (int) speed, -1, sourcePosition);
                return GetPredictionAoe(targets, data);
            }

            public static PredictionResult[] PredictConeSpellAoe(Obj_AI_Base[] targets, float range, int angle, int delay, float speed, Vector3? sourcePosition = null)
            {
                var data = new PredictionData(PredictionData.PredictionType.Circular, (int) range, 0, angle, delay, (int) speed, -1, sourcePosition);
                return GetPredictionAoe(targets, data);
            }

            /// <summary>
            /// Returns the actual path of a unit.
            /// </summary>
            /// <param name="unit"> The unit.</param>
            public static Vector3[] GetRealPath(Obj_AI_Base unit)
            {
                const int tolerance = 50;
                var path = unit.Path.ToList();

                for (var i = path.Count - 1; i > 0; i--)
                {
                    var start = path[i].To2D();
                    var end = path[i - 1].To2D();

                    if (unit.ServerPosition.Distance(start, end, true) <= tolerance.Pow())
                    {
                        path.RemoveRange(0, i);
                        break;
                    }
                }

                return new[] { unit.Position }.Concat(path).ToArray();
            }

            /// <summary>
            /// Predicts the position of a moving unit after a specified amount of time.
            /// </summary>
            /// <param name="unit"> The unit.</param>
            /// <param name="time"> The time in milliseconds.</param>
            public static Vector2 PredictUnitPosition(Obj_AI_Base unit, int time)
            {
                //TODO: take into account movement speed buffs/debuffs durations

                var totalDistMoved = (time / 1000F) * unit.MoveSpeed;
                var path = GetRealPath(unit);

                for (var i = 0; i < path.Length - 1; i++)
                {
                    var lineStart = path[i].To2D();
                    var lineEnd = path[i + 1].To2D();
                    var dist = lineStart.Distance(lineEnd);

                    if (dist > totalDistMoved)
                    {
                        return lineStart.Extend(lineEnd, totalDistMoved);
                    }

                    totalDistMoved -= dist;
                }

                return (path.Length == 0 ? unit.ServerPosition : path.Last()).To2D();
            }

            /// <summary>
            /// Holds all the necessary data needed to perform prediction.
            /// </summary>
            public class PredictionData
            {
                public enum PredictionType
                {
                    Linear,
                    Circular,
                    Cone,
                }

                public int Range { get; internal set; }
                public int Radius { get; internal set; }
                public int Angle { get; internal set; }
                public int Delay { get; internal set; }
                public float Speed { get; internal set; }
                public int AllowCollisionCount { get; internal set; }
                public Vector3 SourcePosition { get; internal set; }
                public PredictionType Type { get; internal set; }

                /// <summary>
                /// Initializes a new instance of the PredictionData class.
                /// </summary>
                /// <param name="type"> The prediction type.</param>
                /// <param name="range"> The maximum range of the skillshot.</param>
                /// <param name="radius"> The radius of the skillshot.</param>
                /// <param name="angle"> The angle in degrees of the skillshot. It is used only in cone spells.</param>
                /// <param name="delay"> The delay (or cast time) of the skillshot.</param>
                /// <param name="speed"> The movement speed of the missile.</param>
                /// <param name="allowCollisionCount"> The number of collision objects the skillshot can pass through. It is used only in Linear skillshots</param>
                /// <param name="sourcePosition"> The start position of the skillshot. Player's position is considered the starting point by default.</param>
                public PredictionData(PredictionType type, int range, int radius, int angle, int delay, int speed, int allowCollisionCount = 0, Vector3? sourcePosition = null)
                {
                    Type = type;
                    Range = (int) (range * RangeAdjustment / 100f);
                    Radius = radius;
                    Angle = angle;
                    Delay = delay;
                    AllowCollisionCount = allowCollisionCount;
                    Speed = speed <= 0 ? int.MaxValue : speed;
                    SourcePosition = sourcePosition ?? Player.Instance.ServerPosition;
                }

                internal Geometry.Polygon GetSkillshotPolygon(Vector2 castPos)
                {
                    switch (Type)
                    {
                        case PredictionType.Linear:
                            return new Geometry.Polygon.Rectangle(SourcePosition, SourcePosition.Extend(castPos, Range).To3DWorld(), Radius * 2);
                        case PredictionType.Circular:
                            return new Geometry.Polygon.Circle(castPos, Radius);
                        case PredictionType.Cone:
                            return new Geometry.Polygon.Sector(SourcePosition, castPos.To3DWorld(), (float) (Angle * Math.PI / 180), Range);
                        default:
                            return null;
                    }
                }

                internal delegate bool CollisionCheck(Obj_AI_Base target, Obj_AI_Base unit);

                internal CollisionCheck GetCollisionCalculator(Vector2 castPos)
                {
                    switch (Type)
                    {
                        case PredictionType.Linear:
                            return (target, unit) =>
                            {
                                if (unit.IsMoving)
                                {
                                    return Collision.LinearMissileCollision(unit, SourcePosition.To2D(), castPos, Speed, Radius * 2, Delay, ExtraHitbox);
                                }

                                return
                                    Geometry.SegmentCircleIntersectionPriority(SourcePosition.To2D(), castPos, castPos, target.BoundingRadius + Radius, unit.ServerPosition.To2D(),
                                        unit.BoundingRadius + Radius + ExtraHitbox) == 2;
                            };

                        case PredictionType.Circular:
                            return (target, unit) => { return Collision.CircularMissileCollision(unit, SourcePosition.To2D(), castPos, Speed, Radius, Delay); };

                        case PredictionType.Cone:
                            var sector = GetSkillshotPolygon(castPos);

                            return (target, unit) =>
                            {
                                var time = Delay + Ping;
                                var pos = PredictUnitPosition(unit, time);
                                return sector.IsInside(pos);
                            };

                        default:
                            return null;
                    }
                }

                internal List<List<Vector2>> GetAoeGroups(Dictionary<Obj_AI_Base, PredictionResult> results)
                {
                    var groups = new List<List<Vector2>>();

                    switch (Type)
                    {
                        case PredictionType.Linear:
                            Logger.Warn("AoE prediction for linear skillshots is not available yet");
                            return new List<List<Vector2>>();

                        case PredictionType.Circular:
                            var groupRange = Radius;

                            foreach (var unit in results.Keys)
                            {
                                var result = results[unit];
                                var newGroup = new List<Vector2> { result.UnitPosition.To2D() };

                                newGroup.AddRange(
                                    results.Where(r => r.Value.UnitPosition.Distance(result.UnitPosition, true) <= (groupRange + r.Key.BoundingRadius / 2).Pow())
                                        .Select(r => r.Value.UnitPosition.To2D()));

                                if (groups.All(g => g.Count(p => newGroup.Contains(p)) != newGroup.Count))
                                {
                                    groups.Add(newGroup);
                                }
                            }
                            break;

                        case PredictionType.Cone:

                            var sectorLength = (float) (2 * Angle * Math.PI / 360) * Range;
                            var sourcePosition2D = SourcePosition.To2D();

                            foreach (var unit in results.Keys)
                            {
                                var result = results[unit];
                                var newGroup = new List<Vector2> { result.UnitPosition.To2D() };

                                foreach (var r in results)
                                {
                                    var sector = new Geometry.Polygon.Sector(sourcePosition2D, r.Value.UnitPosition.To2D().Extend(result.UnitPosition, sectorLength / 2),
                                        (float) (Angle * Math.PI / 180), Range);

                                    if (sector.IsInside(result.UnitPosition))
                                    {
                                        newGroup.Add(r.Value.UnitPosition.To2D());
                                    }
                                }

                                if (groups.All(g => g.Count(p => newGroup.Contains(p)) != newGroup.Count))
                                {
                                    groups.Add(newGroup);
                                }
                            }
                            break;
                    }

                    return groups;
                }
            }

            /// <summary>
            /// Computes collision between objects.
            /// </summary>
            public static class Collision
            {
                private static float _lastYasuoWallCasted;
                private static Vector3 _lastYasuoWallStart;
                internal static void Initialize()
                {
                    if (EntityManager.Heroes.AllHeroes.Any(i => i.Hero == Champion.Yasuo))
                    {
                        Obj_AI_Base.OnProcessSpellCast += delegate (Obj_AI_Base sender, GameObjectProcessSpellCastEventArgs args)
                        {
                            if (sender.BaseSkinName == "Yasuo" && sender.IsEnemy && args.Slot == SpellSlot.W)
                            {
                                _lastYasuoWallCasted = Game.Time;
                                _lastYasuoWallStart = sender.ServerPosition;
                            }
                        };
                    }
                }

                /// <summary>
                /// Determines whether two moving objects will collide within a predetermined path. The movement is considered linear.
                /// </summary>
                /// <param name="start"> The starting point of the first object.</param>
                /// <param name="destination"> The destination point of the first object.</param>
                /// <param name="hitbox"> The hitbox radius of the first object.</param>
                /// <param name="speed"> The movement speed of the first object.</param>
                /// <param name="isUnit"> Determines whether the object is a unit. Set to true if the first object disappears after reaching its destination.</param>                
                /// <param name="start2"> The starting point of the second object.</param>
                /// <param name="destination2"> The destination point of the second object.</param>
                /// <param name="hitbox2"> The hitbox radius of the second object.</param>
                /// <param name="speed2"> The movement speed of the second object.</param>
                /// <param name="isUnit2"> Determines whether the object is a unit. Set to true if the second object disappears after reaching its destination.</param>
                public static bool MovingObjectsCollision(
                    Vector2 start,
                    Vector2 destination,
                    float hitbox,
                    float speed,
                    bool isUnit,
                    Vector2 start2,
                    Vector2 destination2,
                    float hitbox2,
                    float speed2,
                    bool isUnit2)
                {
                    // constants
                    var a = speed2;
                    var b = start2.Distance(destination2);
                    var c = destination2.X;
                    var d = start2.X;
                    var f = start.Distance(destination);
                    var j = destination.X;
                    var k = speed;
                    var m = start.X;
                    var n = destination2.Y;
                    var p = start2.Y;
                    var q = destination.Y;
                    var w = start.Y;
                    var h = (hitbox + hitbox2).Pow();

                    // function distance(t) - h = a*t^2 + b*t + c
                    // solve for distance(t) <= h
                    var theta = -1 / (b * b * f * f);
                    var da = (-a * a * c * c * f * f + 2 * a * a * c * d * f * f - a * a * d * d * f * f - a * a * f * f * n * n + 2 * a * a * f * f * n * p - a * a * f * f * p * p +
                              2 * a * b * c * f * j * k - 2 * a * b * c * f * k * m - 2 * a * b * d * f * j * k + 2 * a * b * d * f * k * m + 2 * a * b * f * k * n * q - 2 * a * b * f * k * n * w -
                              2 * a * b * f * k * p * q + 2 * a * b * f * k * p * w - b * b * j * j * k * k + 2 * b * b * j * k * k * m - b * b * k * k * m * m - b * b * k * k * q * q +
                              2 * b * b * k * k * q * w - b * b * k * k * w * w) * theta;
                    var db = (-2 * a * b * c * d * f * f + 2 * a * b * c * f * f * m + 2 * a * b * d * d * f * f - 2 * a * b * d * f * f * m - 2 * a * b * f * f * n * p + 2 * a * b * f * f * n * w +
                              2 * a * b * f * f * p * p - 2 * a * b * f * f * p * w + 2 * b * b * d * f * j * k - 2 * b * b * d * f * k * m - 2 * b * b * f * j * k * m + 2 * b * b * f * k * m * m +
                              2 * b * b * f * k * p * q - 2 * b * b * f * k * p * w - 2 * b * b * f * k * q * w + 2 * b * b * f * k * w * w) * theta;
                    var dc = (b * b * (-d * d) * f * f + 2 * b * b * d * f * f * m + b * b * f * f * h - b * b * f * f * m * m - b * b * f * f * p * p + 2 * b * b * f * f * p * w -
                              b * b * f * f * w * w) * theta;
                    var dd = db * db - 4 * da * dc;

                    // division with 0
                    if (b == 0 || f == 0)
                    {
                        return true;
                    }

                    // static function
                    if (da == 0 && db == 0)
                    {
                        return dc <= 0;
                    }

                    // no solutions
                    if (dd < 0)
                    {
                        return dc <= 0;
                    }

                    //TODO: check if da == 0
                    //var solution0 = -dc / db; 

                    var solution1 = (-db + Math.Sqrt(dd)) / (2 * da);
                    var solution2 = (-db - Math.Sqrt(dd)) / (2 * da);

                    var time1 = start.Distance(destination) / speed;
                    var time2 = start2.Distance(destination2) / speed2;

                    // check only for t[0, maxTime]
                    var minTime = 0F;
                    var maxTime = time1 > time2 ? time2 : time1;

                    // function changes polarity during [t, maxTime]
                    if (solution1 >= minTime && solution1 <= maxTime)
                    {
                        return true;
                    }

                    // function changes polarity during [t, maxTime]
                    if (solution2 >= minTime && solution2 <= maxTime)
                    {
                        return true;
                    }

                    // check when one object stops moving and f(0) > 0
                    if ((time1 > time2 || time2 > time1) && dc > 0)
                    {
                        // missiles disappear
                        if ((time2 > time1 && !isUnit) || (time1 > time2 && !isUnit2))
                        {
                            return false;
                        }

                        var time = (time1 > time2 ? time2 : time1);
                        var lineStart = time1 > time2 ? start : start2;
                        var lineEnd = time1 > time2 ? destination : destination2;
                        var velocity = time1 > time2 ? speed : speed2;
                        var circlePos = time1 > time2 ? destination2 : destination;
                        var circleRadius = hitbox + hitbox2;

                        lineStart = lineStart.Extend(lineEnd, velocity * time);

                        // check for intersection
                        return Geometry.SegmentCircleIntersectionPriority(lineStart, lineEnd, circlePos, circleRadius, circlePos, circleRadius) > 0;
                    }

                    //  check function value for t = 0, (f(0) = c)
                    return dc <= 0;
                }

                /// <summary>
                /// Determines whether a unit will collide with a linear missile along a predetermined path.
                /// </summary>
                /// <param name="unit"> The unit to check collision.</param>
                /// <param name="missileStartPos"> The starting point of the missile.</param>
                /// <param name="missileEndPos"> The destination point of the missile.</param>
                /// <param name="missileSpeed"> Missile's speed.</param>
                /// <param name="missileWidth"> Missile's width. The width is equal to the double of the missile's hitbox radius.</param>
                /// <param name="delay"> The time (in milliseconds) it will take for the missile to spawn.</param>
                /// <param name="extraRadius"> The extra hitbox radius you can assign to the unit. Default value: 0.</param>
                public static bool LinearMissileCollision(Obj_AI_Base unit, Vector2 missileStartPos, Vector2 missileEndPos, float missileSpeed, int missileWidth, int delay, int extraRadius = 0)
                {
                    var hitboxRadius = unit.BoundingRadius + extraRadius;

                    if (!unit.IsMoving)
                    {
                        return
                            Geometry.SegmentCircleIntersectionPriority(missileStartPos, missileEndPos, unit.ServerPosition.To2D(), hitboxRadius, unit.ServerPosition.To2D(),
                                hitboxRadius + missileWidth / 2) > 0;
                    }

                    var time = delay + Ping;
                    var position = PredictUnitPosition(unit, time);
                    var path = GetRealPath(unit);

                    var t = 0f;
                    for (var i = 0; i < path.Length - 1; i++)
                    {
                        var start = path[i].To2D();
                        var end = path[i + 1].To2D();

                        if (position != Vector2.Zero)
                        {
                            if (!Geometry.PointInLineSegment(start, end, position))
                            {
                                continue;
                            }

                            start = position;
                            position = Vector2.Zero;
                        }

                        var missilePos = missileStartPos.Extend(missileEndPos, t * missileSpeed);

                        if (!Geometry.PointInLineSegment(missileStartPos, missileEndPos, missilePos))
                        {
                            break;
                        }

                        if (MovingObjectsCollision(missilePos, missileEndPos, missileWidth / 2, missileSpeed, false, start, end, hitboxRadius, unit.MoveSpeed, true))
                        {
                            return true;
                        }

                        t += start.Distance(end) / unit.MoveSpeed;
                    }

                    return false;
                }

                /// <summary>
                /// Determines whether a unit will collide with a circular missile along a predetermined path. Circular missiles cause collision only at the destination point.
                /// </summary>
                /// <param name="unit"> The unit to check collision.</param>
                /// <param name="missileStartPos"> The starting point of the missile.</param>
                /// <param name="missileEndPos"> The destination point of the missile.</param>
                /// <param name="missileSpeed"> Missile's speed.</param>
                /// <param name="missileRadius"> Missile's collision radius.</param>
                /// <param name="delay"> The time (in milliseconds) it will take for the missile to spawn.</param>
                /// <param name="extraRadius"> The extra hitbox radius you can assign to the unit. Default value: 0.</param>
                public static bool CircularMissileCollision(Obj_AI_Base unit, Vector2 missileStartPos, Vector2 missileEndPos, float missileSpeed, int missileRadius, int delay, int extraRadius = 0)
                {
                    var time = delay + Ping + (missileSpeed > 0 ? missileStartPos.Distance(missileEndPos) / missileSpeed : 0F);
                    var position = PredictUnitPosition(unit, (int) time);
                    return missileEndPos.Distance(position, true) <= (missileRadius + unit.BoundingRadius + extraRadius).Pow();
                }

                /// <summary>
                /// Computes the point where an object can collide with another object moving along a predetermined path. There may not always exist a valid point.
                /// </summary>
                /// <param name="start"> The starting point of the pretedermined path.</param>
                /// <param name="end"> The ending point of the predetermined path.</param>
                /// <param name="position"> The current position of the object.</param>
                /// <param name="speed"> The movement speed of the object moving along the predetermined path.</param>
                /// <param name="speed2"> The movement speed of the object.</param>
                public static Vector2 GetCollisionPoint(Vector2 start, Vector2 end, Vector2 position, float speed, float speed2)
                {
                    if (start == end)
                    {
                        return start;
                    }

                    var pointA = start;
                    var pointB = end;
                    var pointC = position;

                    var vectorC = new Vector2(pointC.X - pointA.X, pointC.Y - pointA.Y);
                    var vectorB = new Vector2(pointB.X - pointA.X, pointB.Y - pointA.Y);

                    var cosine = (vectorC.X * vectorB.X + vectorC.Y * vectorB.Y) /
                                 (Math.Sqrt(Math.Pow(vectorC.X, 2) + Math.Pow(vectorC.Y, 2)) * Math.Sqrt(Math.Pow(vectorB.X, 2) + Math.Pow(vectorB.Y, 2)));

                    var distanceCA = pointA.Distance(pointC);

                    var distanceAP =
                        (float)
                            Math.Abs((speed.Pow() * distanceCA * cosine - Math.Sqrt(speed.Pow() * distanceCA.Pow() * (speed2.Pow() + speed.Pow() * cosine.Pow() - speed.Pow()))) /
                                     (speed2.Pow() - speed.Pow()));

                    return pointA.Extend(pointB, distanceAP);
                }

                /// <summary>
                /// Returns the point that intersects with Start and End, returns Vector3.Zero if doesn't collide.
                /// </summary>
                /// <param name="start"> The start point.</param>
                /// <param name="end"> The end point.</param>
                public static Vector3 GetYasuoWallCollision(Vector3 start, Vector3 end)
                {
                    if (Game.Time - _lastYasuoWallCasted <= 4)
                    {
                        var nearestYasuoWall =
                            ObjectManager.Get<Obj_GeneralParticleEmitter>()
                                .Where(i => i.IsValid && i.IsVisible && !i.IsDead && Regex.IsMatch(i.Name, "_w_windwall_enemy_0.\\.troy", RegexOptions.IgnoreCase)).OrderBy(i => i.Distance(_lastYasuoWallStart, true)).FirstOrDefault();
                        if (nearestYasuoWall != null)
                        {
                            var level = nearestYasuoWall.Name.Substring(nearestYasuoWall.Name.Length - 6, 1);
                            var wallWidth = 300 + 50 * Convert.ToInt32(level);
                            var wallDirection = (nearestYasuoWall.Position - _lastYasuoWallStart).To2D().Normalized().Perpendicular();
                            var wallStart = nearestYasuoWall.Position.To2D() + wallWidth / 2f * wallDirection;
                            var wallEnd = nearestYasuoWall.Position.To2D() - wallWidth / 2f * wallDirection;
                            var intersection = wallStart.Intersection(wallEnd, start.To2D(), end.To2D());
                            if (intersection.Intersects)
                            {
                                return intersection.Point.To3DWorld();
                            }
                        }
                    }
                    return Vector3.Zero;
                }
            }
        }
    }

    public class PredictionResult
    {
        private readonly int _allowedCollisionCount;
        private readonly HitChance _hitChanceOverride;

        public readonly Vector3 CastPosition;
        public readonly Obj_AI_Base[] CollisionObjects;
        public readonly float HitChancePercent;
        public readonly Vector3 UnitPosition;

        public bool Collision
        {
            get { return CollisionObjects.Length > _allowedCollisionCount && _allowedCollisionCount >= 0; }
        }

        public HitChance HitChance
        {
            get
            {
                if (Collision)
                {
                    return HitChance.Collision;
                }
                if (_hitChanceOverride > HitChance.Unknown)
                {
                    return _hitChanceOverride;
                }
                if (HitChancePercent == 0F)
                {
                    return HitChance.Impossible;
                }
                if (HitChancePercent >= 70F)
                {
                    return HitChance.High;
                }
                if (HitChancePercent >= 50F)
                {
                    return HitChance.Medium;
                }
                if (HitChancePercent >= 25F)
                {
                    return HitChance.AveragePoint;
                }
                if (HitChancePercent > 0F)
                {
                    return HitChance.Low;
                }
                return HitChance.Unknown;
            }
        }

        public T[] GetCollisionObjects<T>()
        {
            return CollisionObjects.Where(unit => unit.GetType() == typeof (T)).Cast<T>().ToArray();
        }

        public PredictionResult(
            Vector3 castPosition,
            Vector3 unitPosition,
            float hitChancePercent,
            Obj_AI_Base[] collisionMinions,
            int allowedCollisionCount,
            HitChance hitChanceOverride = HitChance.Unknown)
        {
            if (hitChancePercent < 0)
            {
                hitChancePercent = 0F;
            }

            if (hitChancePercent > 100)
            {
                hitChancePercent = 100F;
            }

            CastPosition = castPosition;
            UnitPosition = unitPosition;
            HitChancePercent = hitChancePercent;
            CollisionObjects = collisionMinions ?? (new Obj_AI_Base[] { });
            _allowedCollisionCount = allowedCollisionCount;
            _hitChanceOverride = hitChanceOverride;

            if (Collision)
            {
                HitChancePercent = 0;
            }
        }

        internal static PredictionResult ResultImpossible(Prediction.Position.PredictionData data, Vector3? castPos = null, Vector3? unitPos = null)
        {
            return new PredictionResult(castPos ?? Vector3.Zero, unitPos ?? Vector3.Zero, 0, null, data.AllowCollisionCount, HitChance.Impossible);
        }
    }
}
