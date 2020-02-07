using System;
using System.Collections.Generic;
using System.Linq;
using EloBuddy.SDK.Constants;
using EloBuddy.SDK.Utils;
using SharpDX;

// ReSharper disable MemberHidesStaticFromOuterClass

namespace EloBuddy.SDK
{
    public static class EntityManager
    {
        public enum UnitTeam
        {
            Ally,
            Both,
            Enemy
        }

        internal static void Initialize()
        {
            // Initialize all cached classes
            Heroes.Initialize();
            Turrets.Initialize();
        }



        public static class Heroes
        {
            internal static bool ContainsKayle;
            internal static bool ContainsKindred;
            internal static bool ContainsZilean;
            internal static bool ContainsKalista;
            internal static List<AIHeroClient> _allHeroes = new List<AIHeroClient>();
            /// <summary>
            /// A list containing all heroes in the current match
            /// </summary>
            public static List<AIHeroClient> AllHeroes
            {
                get { return new List<AIHeroClient>(_allHeroes); }
            }

            internal static List<AIHeroClient> _allies = new List<AIHeroClient>();
            /// <summary>
            /// A list containing only ally heroes in the current match (including local player)
            /// </summary>
            public static List<AIHeroClient> Allies
            {
                get { return new List<AIHeroClient>(_allies); }
            }

            internal static List<AIHeroClient> _enemies = new List<AIHeroClient>();
            /// <summary>
            /// A list containing only enemy heroes in the current match
            /// </summary>
            public static List<AIHeroClient> Enemies
            {
                get { return new List<AIHeroClient>(_enemies); }
            }

            internal static void Initialize()
            {
                _allHeroes = ObjectManager.Get<AIHeroClient>().ToList();
                _allies = new List<AIHeroClient>();
                _enemies = new List<AIHeroClient>();

                if (!Bootstrap.IsSpectatorMode)
                {
                    _allies = AllHeroes.FindAll(o => o.IsAlly);
                    _enemies = AllHeroes.FindAll(o => o.IsEnemy);
                    ContainsKalista = AllHeroes.Any(client => client.Hero == Champion.Kalista);
                    ContainsKayle = AllHeroes.Any(client => client.Hero == Champion.Kayle);
                    ContainsKindred = AllHeroes.Any(client => client.Hero == Champion.Kindred);
                    ContainsZilean = AllHeroes.Any(client => client.Hero == Champion.Zilean);
                    Logger.Info("EntityManager.Heroes: Allies ({0}) Enemies ({1})", _allies.Count, _enemies.Count);
                }
            }
        }

        public static class Turrets
        {
            internal static List<Obj_AI_Turret> _allTurrets = new List<Obj_AI_Turret>();
            /// <summary>
            /// A list containing all turrets in the current match
            /// </summary>
            public static List<Obj_AI_Turret> AllTurrets
            {
                get { return new List<Obj_AI_Turret>(_allTurrets); }
            }

            internal static List<Obj_AI_Turret> _allies = new List<Obj_AI_Turret>();
            /// <summary>
            /// A list containing only ally turrets in the current match
            /// </summary>
            public static List<Obj_AI_Turret> Allies
            {
                get { return new List<Obj_AI_Turret>(_allies); }
            }

            internal static List<Obj_AI_Turret> _enemies = new List<Obj_AI_Turret>();
            /// <summary>
            /// A list containing only enemy turrets in the current match
            /// </summary>
            public static List<Obj_AI_Turret> Enemies
            {
                get { return new List<Obj_AI_Turret>(_enemies); }
            }

            internal static void Initialize()
            {
                _allTurrets = ObjectManager.Get<Obj_AI_Turret>().ToList();
                _allies = new List<Obj_AI_Turret>();
                _enemies = new List<Obj_AI_Turret>();

                if (!Bootstrap.IsSpectatorMode)
                {
                    _allies = AllTurrets.FindAll(o => o.IsAlly);
                    _enemies = AllTurrets.FindAll(o => o.IsEnemy);
                }

                // Clearing
                GameObject.OnDelete += delegate(GameObject sender, EventArgs args)
                {
                    if (sender is Obj_AI_Turret)
                    {
                        _allTurrets.RemoveAll(o => o.IdEquals(sender));

                        if (!Bootstrap.IsSpectatorMode)
                        {
                            _allies.RemoveAll(o => o.IdEquals(sender));
                            _enemies.RemoveAll(o => o.IdEquals(sender));
                        }
                    }
                };
            }
        }

        public static class MinionsAndMonsters
        {
            public static IEnumerable<Obj_AI_Minion> Monsters
            {
                get { return ObjectManager.Get<Obj_AI_Minion>().Where(o => o.IsValidTarget() && o.IsMonster && o.MaxHealth > 3).ToArray(); }
            }

            public static IEnumerable<Obj_AI_Minion> Minions
            {
                get
                {
                    return
                        ObjectManager.Get<Obj_AI_Minion>()
                            .Where(o => o.IsValidTarget() && o.IsMinion && (o.Team == GameObjectTeam.Chaos || o.Team == GameObjectTeam.Order) && o.MaxHealth > 6)
                            .ToArray();
                }
            }

            public static IEnumerable<Obj_AI_Minion> Combined
            {
                get { return Monsters.Concat(Minions); }
            }

            public static IEnumerable<Obj_AI_Minion> CombinedAttackable
            {
                get { return Monsters.Concat(EnemyMinions); }
            }

            public static IEnumerable<Obj_AI_Minion> AlliedMinions
            {
                get { return Minions.Where(o => o.IsAlly).ToArray(); }
            }

            public static IEnumerable<Obj_AI_Minion> EnemyMinions
            {
                get { return Minions.Where(o => o.IsEnemy).ToArray(); }
            }

            public static IEnumerable<Obj_AI_Minion> OtherMinions
            {
                get
                {
                    return
                        ObjectManager.Get<Obj_AI_Minion>()
                            .Where(
                                o =>
                                    o.IsValidTarget() &&
                                    (
                                        (o.IsMinion && !o.IsMonster && o.MaxHealth <= 6 && !ObjectNames.InvalidTargets.Contains(o.BaseSkinName))
                                        /* Wards, Kalista's W, Heimer turrets, Teemo's R, etc... */
                                        ||
                                        (!o.IsMinion && !o.IsMonster) 
                                        /* Tibbers */
                                        ||
                                        (o.IsMonster && !o.IsMinion && o.MaxHealth <= 3 && o.Health > 0 && o.HasBuff("GangplankEBarrelActive") && o.GetBuff("GangplankEBarrelActive").Caster.IsEnemy)
                                        /* GP's Barrels */
                                        )
                            )
                            .ToArray();
                }
            }

            public static IEnumerable<Obj_AI_Minion> OtherAllyMinions
            {
                get { return OtherMinions.Where(o => o.IsAlly).ToArray(); }
            }
            public static IEnumerable<Obj_AI_Minion> OtherEnemyMinions
            {
                get { return OtherMinions.Where(o => o.IsEnemy).ToArray(); }
            }

            public static IEnumerable<Obj_AI_Minion> Get(
                EntityType type,
                UnitTeam minionTeam = UnitTeam.Enemy,
                Vector3? sourcePosition = null,
                float radius = float.MaxValue,
                bool addBoundingRadius = true)
            {
                // Filter the entity team and type
                IEnumerable<Obj_AI_Minion> entities;
                switch (type)
                {
                    case EntityType.Minion:

                        switch (minionTeam)
                        {
                            case UnitTeam.Ally:

                                entities = AlliedMinions;
                                break;

                            case UnitTeam.Enemy:

                                entities = EnemyMinions;
                                break;

                            default:

                                entities = Minions;
                                break;
                        }
                        break;

                    case EntityType.Monster:

                        entities = Monsters;
                        break;

                    default:

                        entities = CombinedAttackable;
                        break;
                }

                if (entities == null)
                {
                    return new List<Obj_AI_Minion>();
                }

                // Remove all invalid targets
                entities = entities.Where(o => (sourcePosition ?? Player.Instance.ServerPosition).IsInRange(o, radius + (addBoundingRadius ? o.BoundingRadius : 0)));

                // Sort the list decending by max health and return
                return entities.OrderByDescending(o => o.MaxHealth).ToList();
            }

            public static IEnumerable<Obj_AI_Minion> GetLaneMinions(UnitTeam minionTeam = UnitTeam.Enemy, Vector3? sourcePosition = null, float radius = float.MaxValue, bool addBoundingRadius = true)
            {
                return Get(EntityType.Minion, minionTeam, sourcePosition, radius, addBoundingRadius);
            }

            public static IEnumerable<Obj_AI_Minion> GetJungleMonsters(Vector3? sourcePosition = null, float radius = float.MaxValue, bool addBoundingRadius = true)
            {
                return Get(EntityType.Monster, UnitTeam.Both, sourcePosition, radius, addBoundingRadius);
            }

            public static FarmLocation GetCircularFarmLocation(IEnumerable<Obj_AI_Minion> entities, float width, int range, Vector2? sourcePosition = null)
            {
                var targets = entities.ToArray();
                switch (targets.Length)
                {
                    case 0:
                        return new FarmLocation();
                    case 1:
                        return new FarmLocation { CastPosition = targets[0].ServerPosition, HitNumber = 1 };
                }

                var startPos = sourcePosition ?? Player.Instance.ServerPosition.To2D();
                var minionCount = 0;
                var result = Vector2.Zero;

                var validTargets = targets.Select(o => o.ServerPosition.To2D()).Where(o => o.IsInRange(startPos, range)).ToArray();
                foreach (var pos in validTargets)
                {
                    var count = validTargets.Count(o => o.IsInRange(pos, width / 2));

                    if (count >= minionCount)
                    {
                        result = pos;
                        minionCount = count;
                    }
                }

                return new FarmLocation { CastPosition = result.To3DWorld(), HitNumber = minionCount };
            }

            public static FarmLocation GetCircularFarmLocation(IEnumerable<Obj_AI_Minion> entities, float width, int range, int delay, float speed, Vector2? sourcePosition = null)
            {
                var targets = entities.Cast<Obj_AI_Base>().ToArray();

                Vector3? source = null;
                if (sourcePosition.HasValue)
                {
                    source = sourcePosition.Value.To3DWorld();
                }

                var startPos = sourcePosition ?? Player.Instance.ServerPosition.To2D();
                var minionCount = 0;
                var result = Vector2.Zero;

                var validTargets =
                    targets.Select(o => Prediction.Position.PredictCircularMissile(o, range, (int) (width / 2f), delay, speed, source))
                        .Where(o => o.UnitPosition.IsInRange(startPos, range + width / 2))
                        .ToArray();
                foreach (var pos in validTargets)
                {
                    var count = validTargets.Count(o => o.UnitPosition.IsInRange(pos.UnitPosition, width / 2));

                    if (count >= minionCount)
                    {
                        result = pos.UnitPosition.To2D();
                        minionCount = count;
                    }
                }

                return new FarmLocation { CastPosition = result.To3DWorld(), HitNumber = minionCount };
            }

            public static FarmLocation GetLineFarmLocation(IEnumerable<Obj_AI_Minion> entities, float width, int range, Vector2? sourcePosition = null)
            {
                var targets = entities.ToArray();
                switch (targets.Length)
                {
                    case 0:
                        return new FarmLocation();
                    case 1:
                        return new FarmLocation { CastPosition = targets[0].ServerPosition, HitNumber = 1 };
                }

                var posiblePositions = new List<Vector2>(targets.Select(o => o.ServerPosition.To2D()));
                foreach (var target in targets)
                {
                    posiblePositions.AddRange(from t in targets where t.NetworkId != target.NetworkId select (t.ServerPosition.To2D() + target.ServerPosition.To2D()) / 2);
                }

                var startPos = sourcePosition ?? Player.Instance.ServerPosition.To2D();
                var minionCount = 0;
                var result = Vector2.Zero;

                foreach (var pos in posiblePositions.Where(o => o.IsInRange(startPos, range)))
                {
                    var endPos = startPos + range * (pos - startPos).Normalized();
                    var count = targets.Count(o => o.ServerPosition.To2D().Distance(startPos, endPos, true, true) <= width * width);

                    if (count >= minionCount)
                    {
                        result = endPos;
                        minionCount = count;
                    }
                }

                return new FarmLocation { CastPosition = result.To3DWorld(), HitNumber = minionCount };
            }

            public struct FarmLocation
            {
                public int HitNumber;
                public Vector3 CastPosition;
            }

            public enum EntityType
            {
                Minion,
                Monster,
                Both
            }
        }
    }
}
