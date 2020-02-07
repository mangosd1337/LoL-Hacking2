using System;
using System.Collections.Generic;
using System.Linq;
using EloBuddy.SDK.Constants;
using EloBuddy.SDK.Rendering;
using EloBuddy.SDK.Spells;
using SharpDX;

// ReSharper disable SwitchStatementMissingSomeCases

// ReSharper disable MemberCanBePrivate.Global

namespace EloBuddy.SDK
{
    /// <summary>
    /// Misc
    /// </summary>
    public static partial class Extensions
    {
        #region GameObject Fast Checks

        public static bool IsMinion(this Obj_AI_Base target)
        {
            return ObjectNames.Minions.Contains(target.BaseSkinName);
        }

        public static bool IsStructure(this GameObject target)
        {
            var type = target.Type;
            return (type == GameObjectType.obj_AI_Turret || type == GameObjectType.obj_BarracksDampener ||
                    type == GameObjectType.obj_HQ);
        }

        public static bool IsWard(this GameObject unit)
        {
            return unit.Type == GameObjectType.obj_Ward;
        }

        #endregion

        public static float GetAutoAttackRange(this Obj_AI_Base source, AttackableUnit target = null)
        {
            var result = source.AttackRange + source.BoundingRadius +
                         (target != null ? (target.BoundingRadius - 30) : 35);
            var hero = source as AIHeroClient;
            if (hero != null && target != null)
            {
                switch (hero.Hero)
                {
                    case Champion.Caitlyn:
                        var targetBase = target as Obj_AI_Base;
                        if (targetBase != null && targetBase.HasBuff("caitlynyordletrapinternal"))
                        {
                            result += 650f;
                        }
                        break;
                }
            }
            else if (source is Obj_AI_Turret)
            {
                return 750f + source.BoundingRadius;
            }
            else if (source.BaseSkinName == "AzirSoldier")
            {
                result += Orbwalker.AzirSoldierAutoAttackRange - source.BoundingRadius;
            }
            return result;
        }

        public static bool IdEquals(this GameObject source, GameObject target)
        {
            if (source == null || target == null)
            {
                return false;
            }

            return source.NetworkId == target.NetworkId;
        }
    }

    /// <summary>
    /// Geometry
    /// </summary>
    public static partial class Extensions
    {
        #region SharpDX.Rectangle

        public static Rectangle Negate(this Rectangle rectangle)
        {
            return new Rectangle(-rectangle.X, -rectangle.Y, -rectangle.Width, -rectangle.Height);
        }

        public static Rectangle Add(this Rectangle rectangle1, Rectangle rectangle2)
        {
            return new Rectangle(rectangle1.X + rectangle2.X, rectangle1.Y + rectangle2.Y,
                rectangle1.Width + rectangle2.Width, rectangle1.Height + rectangle2.Height);
        }

        public static Rectangle Substract(this Rectangle rectangle1, Rectangle rectangle2)
        {
            return new Rectangle(rectangle1.X - rectangle2.X, rectangle1.Y - rectangle2.Y,
                rectangle1.Width - rectangle2.Width, rectangle1.Height - rectangle2.Height);
        }

        public static Rectangle Multiply(this Rectangle rectangle1, Rectangle rectangle2)
        {
            return new Rectangle(rectangle1.X * rectangle2.X, rectangle1.Y * rectangle2.Y,
                rectangle1.Width * rectangle2.Width, rectangle1.Height * rectangle2.Height);
        }

        public static Rectangle Divide(this Rectangle rectangle1, Rectangle rectangle2)
        {
            return new Rectangle(rectangle1.X / rectangle2.X, rectangle1.Y / rectangle2.Y,
                rectangle1.Width / rectangle2.Width, rectangle1.Height / rectangle2.Height);
        }

        public static bool IsInside(this Rectangle rectangle, Vector2 position)
        {
            return position.X >= rectangle.X && position.Y >= rectangle.Y &&
                   position.X < rectangle.BottomRight.X && position.Y < rectangle.BottomRight.Y;
        }

        public static bool IsCompletlyInside(this Rectangle rectangle1, Rectangle rectangle2)
        {
            return rectangle2.X >= rectangle1.X && rectangle2.Y >= rectangle1.Y &&
                   rectangle2.BottomRight.X <= rectangle1.BottomRight.X &&
                   rectangle2.BottomRight.Y <= rectangle1.BottomRight.Y;
        }

        public static bool IsPartialInside(this Rectangle rectangle1, Rectangle rectangle2)
        {
            return rectangle2.X >= rectangle1.X && rectangle2.X <= rectangle1.BottomRight.X ||
                   rectangle2.Y >= rectangle1.Y && rectangle2.Y <= rectangle1.BottomRight.Y;
        }

        public static bool IsNear(this Rectangle rectangle, Vector2 position, int distance)
        {
            return
                new Rectangle(rectangle.X - distance, rectangle.Y - distance, rectangle.Width + distance,
                    rectangle.Height + distance).IsInside(position);
        }

        #endregion
    }

    /// <summary>
    /// Numeric and Math
    /// </summary>
    public static partial class Extensions
    {
        public static int Pow(this int number)
        {
            return number * number;
        }

        public static uint Pow(this uint number)
        {
            return number * number;
        }

        public static double Pow(this double number)
        {
            return number * number;
        }

        public static float Pow(this float number)
        {
            return number * number;
        }

        public static double Sqrt(this int number)
        {
            return Math.Sqrt(number);
        }

        public static double Sqrt(this uint number)
        {
            return Math.Sqrt(number);
        }

        public static double Sqrt(this double number)
        {
            return Math.Sqrt(number);
        }

        public static double Sqrt(this float number)
        {
            return Math.Sqrt(number);
        }
    }

    /// <summary>
    /// Buffs
    /// </summary>
    public static partial class Extensions
    {
        // http://leagueoflegends.wikia.com/wiki/Crowd_control
        private static readonly HashSet<BuffType> BlockedMovementBuffTypes = new HashSet<BuffType>
        {
            BuffType.Knockup,
            BuffType.Knockback,
            BuffType.Charm,
            BuffType.Fear,
            BuffType.Flee,
            BuffType.Taunt,
            BuffType.Snare,
            BuffType.Stun,
            BuffType.Suppression,
        };

        [Obsolete("GetMovementDebuffDuration is deprecated, please use GetMovementBlockedDebuffDuration instead.")]
        public static float GetMovementDebuffDuration(this Obj_AI_Base target)
        {
            return
                target.Buffs.Where(b => b.IsActive && Game.Time < b.EndTime && BlockedMovementBuffTypes.Contains(b.Type))
                    .Aggregate(0f, (current, buff) => Math.Max(current, buff.EndTime)) -
                Game.Time;
        }

        public static float GetMovementBlockedDebuffDuration(this Obj_AI_Base target)
        {
            return
                target.Buffs.Where(b => b.IsActive && Game.Time < b.EndTime && BlockedMovementBuffTypes.Contains(b.Type))
                    .Aggregate(0f, (current, buff) => Math.Max(current, buff.EndTime)) -
                Game.Time;
        }

        private static readonly HashSet<BuffType> ReducedMovementBuffTypes = new HashSet<BuffType>
        {
            BuffType.Slow,
            BuffType.Polymorph
        };

        public static float GetMovementReducedDebuffDuration(this Obj_AI_Base target)
        {
            return
                target.Buffs.Where(b => b.IsActive && Game.Time < b.EndTime && ReducedMovementBuffTypes.Contains(b.Type))
                    .Aggregate(0f, (current, buff) => Math.Max(current, buff.EndTime)) -
                Game.Time;
        }
    }

    /// <summary>
    /// Booleans, like IsXXX
    /// </summary>
    public static partial class Extensions
    {
        #region Validation

        public static bool IsValid(this BuffInstance buffInstance)
        {
            return buffInstance != null &&
                   buffInstance.IsValid &&
                   buffInstance.EndTime - Game.Time > 0;
        }

        public static bool IsValid(this Vector3 vector, bool checkWorldCoords = false)
        {
            return IsValid(vector.To2D(), checkWorldCoords);
        }

        public static bool IsValid(this Vector2 vector, bool checkWorldCoords = false)
        {
            if (vector.IsZero)
            {
                return false;
            }

            if (checkWorldCoords)
            {
                var navMeshCoords = vector.WorldToGrid();
                return navMeshCoords.X >= 0 && navMeshCoords.X <= NavMesh.Width &&
                       navMeshCoords.Y >= 0 && navMeshCoords.Y <= NavMesh.Height;
            }

            return true;
        }

        public static bool HasUndyingBuff(this AIHeroClient target, bool addHealthCheck = false)
        {
            switch (target.Hero)
            {
                case Champion.Aatrox:
                    if (target.HasBuff("aatroxpassivedeath"))
                    {
                        return true;
                    }
                    break;
                case Champion.Fiora:
                    if (target.HasBuff("FioraW"))
                    {
                        return true;
                    }
                    break;
                case Champion.Tryndamere:
                    if (target.HasBuff("UndyingRage") && (!addHealthCheck || target.Health <= 30))
                    {
                        return true;
                    }
                    break;

                case Champion.Vladimir:
                    if (target.HasBuff("VladimirSanguinePool"))
                    {
                        return true;
                    }
                    break;
            }

            if (EntityManager.Heroes.ContainsKayle && target.HasBuff("JudicatorIntervention"))
            {
                return true;
            }

            if (EntityManager.Heroes.ContainsKindred &&
                target.HasBuff("kindredrnodeathbuff") && (!addHealthCheck || target.HealthPercent <= 10))
            {
                return true;
            }

            if (EntityManager.Heroes.ContainsZilean && (target.HasBuff("ChronoShift") ||
                                                        target.HasBuff("chronorevive")) &&
                (!addHealthCheck || target.HealthPercent <= 10))
            {
                return true;
            }

            return false;
        }

        public static bool IsValidTarget(
            this AttackableUnit target,
            float? range = null,
            bool onlyEnemyTeam = false,
            Vector3? rangeCheckFrom = null)
        {
            if (target == null || !target.IsValid || target.IsDead || !target.IsVisible
                /* TODO: Check if IsVisible is correct */|| !target.IsTargetable || target.IsInvulnerable)
            {
                return false;
            }

            if (onlyEnemyTeam && Player.Instance.Team == target.Team)
            {
                return false;
            }

            var baseObject = target as Obj_AI_Base;
            if (baseObject != null && !baseObject.IsHPBarRendered)
            {
                return false;
            }
            if (range.HasValue)
            {
                range = range.Value.Pow();
                var unitPosition = baseObject != null ? baseObject.ServerPosition : target.Position;
                return rangeCheckFrom.HasValue
                    ? rangeCheckFrom.Value.Distance(unitPosition, true) < range
                    : Player.Instance.ServerPosition.DistanceSquared(unitPosition) < range;
            }
            return true;
        }

        public static bool IsValidMissile(this MissileClient source, bool checkTarget = true)
        {
            return ObjectManager.Get<MissileClient>().Count(w => w.MemoryAddress == source.MemoryAddress) == 1 &&
                   source.IsValid &&
                   (source.Target == null ||
                    (source.Target.IsValid &&
                     (!checkTarget || !source.IsInRange(source.Target, source.Target.BoundingRadius))));
        }

        #endregion

        public static bool IsInFountainRange(this Obj_AI_Base hero, bool enemyFountain = false)
        {
            return hero.IsVisible && enemyFountain
                ? ObjectManager.Get<Obj_SpawnPoint>()
                    .Any(s => s.Team != hero.Team && hero.Distance(s.Position, true) < 1562500)
                : ObjectManager.Get<Obj_SpawnPoint>()
                    .Any(s => s.Team == hero.Team && hero.Distance(s.Position, true) < 1562500);
        }

        public static bool IsInShopRange(this AIHeroClient hero)
        {
            return hero.IsVisible && ObjectManager.Get<Obj_Shop>().Any(s => hero.Distance(s.Position, true) < 1562500);
        }

        public static bool IsRecalling(this AIHeroClient unit)
        {
            return unit.Buffs.Any(buff => buff.Type == BuffType.Aura && buff.Name.ToLower().Contains("recall"));
        }

        public static Vector2 Direction(this Obj_AI_Base source)
        {
            const bool finnPlease = true;
            var v = source.Direction.To2D();
            return finnPlease ? v : v.Perpendicular();
        }

        public static bool IsFacing(this Obj_AI_Base source, Vector3 position)
        {
            return (source != null && source.IsValid &&
                    source.Direction().AngleBetween((position - source.Position).To2D()) < 90);
        }

        public static bool IsFacing(this Obj_AI_Base source, Obj_AI_Base target)
        {
            return source.IsFacing(target.Position);
        }

        public static bool IsFacing(this Vector3 position1, Vector3 position2)
        {
            return position1.To2D().Perpendicular().AngleBetween((position2 - position1).To2D()) < 90;
        }

        public static bool IsBothFacing(this Obj_AI_Base source, Obj_AI_Base target)
        {
            return source.IsFacing(target) && target.IsFacing(source);
        }

        public static bool IsValid(this Obj_AI_Base unit)
        {
            return unit != null && unit.IsValid;
        }

        public static bool IsInAutoAttackRange(this Obj_AI_Base source, AttackableUnit target)
        {
            var hero = source as AIHeroClient;
            if (hero != null)
            {
                switch (hero.Hero)
                {
                    case Champion.Azir:
                        if (hero.IsMe && Orbwalker.ValidAzirSoldiers.Any(i => i.IsInAutoAttackRange(target)))
                        {
                            return true;
                        }
                        break;
                }
            }
            return source.IsInRange(target, GetAutoAttackRange(source, target));
        }

        public static bool IsAlly(this GameObjectTeam team)
        {
            return team == Player.Instance.Team;
        }

        public static bool IsEnemy(this GameObjectTeam team)
        {
            switch (team)
            {
                case GameObjectTeam.Unknown:
                case GameObjectTeam.Neutral:
                    return false;

                default:
                    return team != Player.Instance.Team;
            }
        }

        public static bool IsNeutral(this GameObjectTeam team)
        {
            switch (team)
            {
                case GameObjectTeam.Chaos:
                case GameObjectTeam.Order:
                case GameObjectTeam.Unknown:
                    return false;

                case GameObjectTeam.Neutral:
                    return true;
            }

            return false;
        }
    }

    /// <summary>
    /// Spells related
    /// </summary>
    public static partial class Extensions
    {
        public static int GetHighestSpellRange(this AIHeroClient target)
        {
            var qSpell = target.Spellbook.GetSpell(SpellSlot.Q);
            var wSpell = target.Spellbook.GetSpell(SpellSlot.W);
            var eSpell = target.Spellbook.GetSpell(SpellSlot.E);
            var rSpell = target.Spellbook.GetSpell(SpellSlot.R);

            if (qSpell == null || wSpell == null || eSpell == null || rSpell == null)
            {
                return 0;
            }

            var spellList = new List<SpellDataInst>
            {
                qSpell,
                wSpell,
                eSpell,
                rSpell
            };

            var highestSpell = spellList.OrderByDescending(spell => spell.SData.CastRangeDisplayOverride > 0 ? spell.SData.CastRangeDisplayOverride : spell.SData.CastRange).FirstOrDefault();
            if (highestSpell != null)
            {
                return (int) (highestSpell.SData.CastRangeDisplayOverride > 0 ? highestSpell.SData.CastRangeDisplayOverride : highestSpell.SData.CastRange);
            }
            return 0;
        }

        public static int GetLowestSpellRange(this AIHeroClient target)
        {
            var qSpell = target.Spellbook.GetSpell(SpellSlot.Q);
            var wSpell = target.Spellbook.GetSpell(SpellSlot.W);
            var eSpell = target.Spellbook.GetSpell(SpellSlot.E);
            var rSpell = target.Spellbook.GetSpell(SpellSlot.R);

            if (qSpell == null || wSpell == null || eSpell == null || rSpell == null)
            {
                return 0;
            }

            var spellList = new List<SpellDataInst>
            {
                qSpell,
                wSpell,
                eSpell,
                rSpell
            };

            var highestSpell = spellList.OrderBy(spell => spell.SData.CastRangeDisplayOverride > 0 ? spell.SData.CastRangeDisplayOverride : spell.SData.CastRange).FirstOrDefault();
            if (highestSpell != null)
            {
                return (int)(highestSpell.SData.CastRangeDisplayOverride > 0 ? highestSpell.SData.CastRangeDisplayOverride : highestSpell.SData.CastRange);
            }
            return 0;
        }
    }

    /// <summary>
    /// SpellSlot
    /// </summary>
    public static partial class Extensions
    {
        public static SpellSlot GetSpellSlotFromName(this AIHeroClient target, string spellName)
        {
            foreach (
                var spell in
                target.Spellbook.Spells.Where(
                    spell => string.Equals(spell.Name, spellName, StringComparison.CurrentCultureIgnoreCase)))
            {
                return spell.Slot;
            }
            return SpellSlot.Unknown;
        }

        public static SpellSlot FindSummonerSpellSlotFromName(this AIHeroClient target, string spellName)
        {
            foreach (
                var spell in
                target.Spellbook.Spells.Where(
                    spell =>
                        (spell.Slot == SpellSlot.Summoner1 || spell.Slot == SpellSlot.Summoner2) &&
                        spell.Name.ToLower().Contains(spellName.ToLower())))
            {
                return spell.Slot;
            }
            return SpellSlot.Unknown;
        }
    }

    /// <summary>
    /// Item Related
    /// </summary>
    public static partial class Extensions
    {
        public static bool HasItem(this IEnumerable<InventorySlot> inventory, params int[] itemIds)
        {
            return inventory.Any(itemId => itemIds.Contains((int) itemId.Id));
        }

        public static bool HasItem(this IEnumerable<InventorySlot> inventory, params ItemId[] items)
        {
            return inventory.Any(itemId => items.Contains(itemId.Id));
        }

        public static bool HasItem(this Obj_AI_Base target, params int[] itemIds)
        {
            return target.InventoryItems.HasItem(itemIds);
        }

        public static bool HasItem(this Obj_AI_Base target, params ItemId[] itemIds)
        {
            return target.InventoryItems.HasItem(itemIds);
        }
    }

    /// <summary>
    /// Turret Related
    /// </summary>
    public static partial class Extensions
    {
        public static Obj_AI_Base LastTarget(this Obj_AI_Turret turret)
        {
            if (!Orbwalker.LastTargetTurrets.ContainsKey(turret.NetworkId))
            {
                Orbwalker.LastTargetTurrets[turret.NetworkId] = null;
            }
            else if (Orbwalker.LastTargetTurrets[turret.NetworkId] != null &&
                     !Orbwalker.LastTargetTurrets[turret.NetworkId].IsValidTarget())
            {
                Orbwalker.LastTargetTurrets[turret.NetworkId] = null;
            }
            return Orbwalker.LastTargetTurrets[turret.NetworkId];
        }
    }

    /// <summary>
    /// Health Related
    /// </summary>
    public static partial class Extensions
    {
        public static float TotalHealth(this Obj_AI_Base target)
        {
            var result = target.Health;
            var hero = target as AIHeroClient;
            if (hero != null)
            {
                switch (hero.Hero)
                {
                    case Champion.Kled:
                        result += target.KledSkaarlHP;
                        break;
                }
            }
            return result;
        }

        public static float TotalMaxHealth(this Obj_AI_Base target)
        {
            var result = target.MaxHealth;
            var hero = target as AIHeroClient;
            if (hero != null)
            {
                switch (hero.Hero)
                {
                    case Champion.Kled:
                        result += target.MaxKledSkaarlHP;
                        break;
                }
            }
            return result;
        }

        public static float TotalShield(this Obj_AI_Base target)
        {
            var result = target.AllShield + target.AttackShield + target.MagicShield;
            var hero = target as AIHeroClient;
            if (hero != null)
            {
                switch (hero.Hero)
                {
                    case Champion.Blitzcrank:
                        if (!target.HasBuff("BlitzcrankManaBarrierCD") && !target.HasBuff("ManaBarrier"))
                        {
                            result += target.Mana / 2;
                        }
                        break;
                }
            }
            return result;
        }

        public static float TotalShieldHealth(this Obj_AI_Base target)
        {
            return target.TotalHealth() + target.TotalShield();
        }

        public static float TotalShieldMaxHealth(this Obj_AI_Base target)
        {
            return target.TotalMaxHealth() + target.TotalShield();
        }
    }

    /// <summary>
    /// Vectors
    /// </summary>
    public static partial class Extensions
    {
        #region Distance

        public static float Distance(this Obj_AI_Base target1, GameObject target2, bool squared = false)
        {
            return Distance(target1.ServerPosition.To2D(), target2.Position.To2D(), squared);
        }

        public static float Distance(this Obj_AI_Base target, Vector3 pos, bool squared = false)
        {
            return Distance(target.ServerPosition.To2D(), pos.To2D(), squared);
        }

        public static float Distance(this Obj_AI_Base target, Vector2 pos, bool squared = false)
        {
            return Distance(target.ServerPosition.To2D(), pos, squared);
        }

        public static float Distance(this Obj_AI_Base target1, Obj_AI_Base target2, bool squared = false)
        {
            return Distance(target1.ServerPosition.To2D(), target2.ServerPosition.To2D(), squared);
        }

        public static float Distance(this GameObject target1, Obj_AI_Base target2, bool squared = false)
        {
            return Distance(target1.Position.To2D(), target2.ServerPosition.To2D(), squared);
        }

        public static float Distance(this GameObject target, Vector3 pos, bool squared = false)
        {
            return Distance(target.Position.To2D(), pos.To2D(), squared);
        }

        public static float Distance(this GameObject target, Vector2 pos, bool squared = false)
        {
            return Distance(target.Position.To2D(), pos, squared);
        }

        public static float Distance(this GameObject target1, GameObject target2, bool squared = false)
        {
            return Distance(target1.Position.To2D(), target2.Position.To2D(), squared);
        }

        public static float Distance(this Vector3 pos, Obj_AI_Base target, bool squared = false)
        {
            return Distance(pos.To2D(), target.ServerPosition.To2D(), squared);
        }

        public static float Distance(this Vector3 pos, GameObject target, bool squared = false)
        {
            return Distance(pos.To2D(), target.Position.To2D(), squared);
        }

        public static float Distance(this Vector3 pos1, Vector2 pos2, bool squared = false)
        {
            return Distance(pos1.To2D(), pos2, squared);
        }

        public static float Distance(this Vector3 pos1, Vector3 pos2, bool squared = false)
        {
            return Distance(pos1.To2D(), pos2.To2D(), squared);
        }

        public static float Distance(this Vector2 pos, Obj_AI_Base target, bool squared = false)
        {
            return Distance(pos, target.ServerPosition.To2D(), squared);
        }

        public static float Distance(this Vector2 pos, GameObject target, bool squared = false)
        {
            return Distance(pos, target.Position.To2D(), squared);
        }

        public static float Distance(this Vector2 pos1, Vector3 pos2, bool squared = false)
        {
            return Distance(pos1, pos2.To2D(), squared);
        }

        public static float Distance(this Vector2 pos1, Vector2 pos2, bool squared = false)
        {
            if (squared)
            {
                return Vector2.DistanceSquared(pos1, pos2);
            }
            else
            {
                return Vector2.Distance(pos1, pos2);
            }
        }

        public static float Distance(this Vector2 point, Vector2 segmentStart, Vector2 segmentEnd, bool squared = false)
        {
            var a =
                Math.Abs((segmentEnd.Y - segmentStart.Y) * point.X - (segmentEnd.X - segmentStart.X) * point.Y +
                         segmentEnd.X * segmentStart.Y - segmentEnd.Y * segmentStart.X);
            return (squared ? a.Pow() : a) / segmentStart.Distance(segmentEnd, squared);
        }

        public static float Distance(this Vector3 point, Vector2 segmentStart, Vector2 segmentEnd, bool squared = false)
        {
            return point.To2D().Distance(segmentStart, segmentEnd, squared);
        }

        public static float DistanceSquared(this Vector2 pos1, Vector2 pos2)
        {
            return Vector2.DistanceSquared(pos1, pos2);
        }

        public static float DistanceSquared(this Vector3 pos1, Vector3 pos2)
        {
            return DistanceSquared(pos1.To2D(), pos2.To2D());
        }

        public static float DistanceSquared(this Vector2 pos1, Vector3 pos2)
        {
            return DistanceSquared(pos1, pos2.To2D());
        }

        public static float DistanceSquared(this Vector3 pos1, Vector2 pos2)
        {
            return DistanceSquared(pos1.To2D(), pos2);
        }

        public static bool IsInRange(this Vector2 source, Vector2 target, float range)
        {
            return source.Distance(target, true) < range.Pow();
        }

        public static bool IsInRange(this Vector2 source, Vector3 target, float range)
        {
            return IsInRange(source, target.To2D(), range);
        }

        public static bool IsInRange(this Vector2 source, GameObject target, float range)
        {
            return IsInRange(source, target.Position.To2D(), range);
        }

        public static bool IsInRange(this Vector2 source, Obj_AI_Base target, float range)
        {
            return IsInRange(source, target.ServerPosition.To2D(), range);
        }

        public static bool IsInRange(this Vector3 source, Vector2 target, float range)
        {
            return IsInRange(source.To2D(), target, range);
        }

        public static bool IsInRange(this Vector3 source, Vector3 target, float range)
        {
            return IsInRange(source.To2D(), target, range);
        }

        public static bool IsInRange(this Vector3 source, GameObject target, float range)
        {
            return IsInRange(source.To2D(), target, range);
        }

        public static bool IsInRange(this Vector3 source, Obj_AI_Base target, float range)
        {
            return IsInRange(source.To2D(), target, range);
        }

        public static bool IsInRange(this GameObject source, Vector2 target, float range)
        {
            return IsInRange(source.Position.To2D(), target, range);
        }

        public static bool IsInRange(this GameObject source, Vector3 target, float range)
        {
            return IsInRange(source.Position.To2D(), target, range);
        }

        public static bool IsInRange(this GameObject source, GameObject target, float range)
        {
            return IsInRange(source.Position.To2D(), target, range);
        }

        public static bool IsInRange(this GameObject source, Obj_AI_Base target, float range)
        {
            return IsInRange(source.Position.To2D(), target, range);
        }

        public static bool IsInRange(this Obj_AI_Base source, Vector2 target, float range)
        {
            return IsInRange(source.ServerPosition.To2D(), target, range);
        }

        public static bool IsInRange(this Obj_AI_Base source, Vector3 target, float range)
        {
            return IsInRange(source.ServerPosition.To2D(), target, range);
        }

        public static bool IsInRange(this Obj_AI_Base source, GameObject target, float range)
        {
            return IsInRange(source.ServerPosition.To2D(), target, range);
        }

        public static bool IsInRange(this Obj_AI_Base source, Obj_AI_Base target, float range)
        {
            return IsInRange(source.ServerPosition.To2D(), target, range);
        }

        #endregion

        #region Vector Conversions

        public static List<Vector2> To2D(this List<Vector3> points)
        {
            var l = new List<Vector2>();
            l.AddRange(points.Select(v => v.To2D()));
            return l;
        }

        public static Vector2 To2D(this Vector3 vector)
        {
            return new Vector2(vector.X, vector.Y);
        }

        public static Vector3 To3D(this Vector2 vector, int height = 0)
        {
            return new Vector3(vector.X, vector.Y, height);
        }

        public static Vector3 To3DWorld(this Vector2 vector)
        {
            return new Vector3(vector.X, vector.Y, NavMesh.GetHeightForPosition(vector.X, vector.Y));
        }

        public static Vector2 Normalized(this Vector2 vector)
        {
            return Vector2.Normalize(vector);
        }

        public static Vector3 Normalized(this Vector3 vector)
        {
            return Vector3.Normalize(vector);
        }

        public static Vector2 WorldToScreen(this Vector3 vector)
        {
            return Drawing.WorldToScreen(vector);
        }

        public static Vector3 ScreenToWorld(this Vector2 vector)
        {
            return Drawing.ScreenToWorld(vector.X, vector.Y);
        }

        public static Vector2 WorldToGrid(this Vector3 vector)
        {
            return WorldToGrid(vector.To2D());
        }

        public static Vector2 WorldToGrid(this Vector2 vector)
        {
            return NavMesh.WorldToGrid(vector.X, vector.Y);
        }

        public static Vector3 GridToWorld(this Vector3 vector)
        {
            return GridToWorld(vector.To2D());
        }

        public static Vector3 GridToWorld(this Vector2 vector)
        {
            return NavMesh.GridToWorld((short) vector.X, (short) vector.Y);
        }

        public static Vector2 WorldToMinimap(this Vector3 vector)
        {
            return TacticalMap.WorldToMinimap(vector);
        }

        public static Vector3 MinimapToWorld(this Vector2 vector)
        {
            return TacticalMap.MinimapToWorld(vector.X, vector.Y);
        }

        public static NavMeshCell ToNavMeshCell(this Vector3 vector)
        {
            return ToNavMeshCell(vector.To2D());
        }

        public static NavMeshCell ToNavMeshCell(this Vector2 vector)
        {
            var gridCoords = vector.WorldToGrid();
            return NavMesh.GetCell((short) gridCoords.X, (short) gridCoords.Y);
        }

        public static float AngleBetween(this Vector3 vector3, Vector3 toVector3)
        {
            var magnitudeA = Math.Sqrt((vector3.X * vector3.X) + (vector3.Y * vector3.Y) + (vector3.Z * vector3.Z));
            var magnitudeB =
                Math.Sqrt((toVector3.X * toVector3.X) + (toVector3.Y * toVector3.Y) + (toVector3.Z * toVector3.Z));

            var dotProduct = (vector3.X * toVector3.X) + (vector3.Y * toVector3.Y) + (vector3.Z + toVector3.Z);
            return (float) Math.Acos(dotProduct / magnitudeA * magnitudeB);
        }

        #endregion

        #region Vector Extending

        public static Vector2 Extend(this Vector3 source, GameObject target, float range)
        {
            return source.To2D().Extend(target.Position.To2D(), range);
        }

        public static Vector2 Extend(this Vector3 source, Obj_AI_Base target, float range)
        {
            return source.To2D().Extend(target.ServerPosition.To2D(), range);
        }

        public static Vector2 Extend(this Vector3 source, Vector3 target, float range)
        {
            return source.To2D().Extend(target.To2D(), range);
        }

        public static Vector2 Extend(this Vector3 source, Vector2 target, float range)
        {
            return source.To2D().Extend(target, range);
        }

        public static Vector2 Extend(this Vector2 source, GameObject target, float range)
        {
            return source.Extend(target.Position.To2D(), range);
        }

        public static Vector2 Extend(this Vector2 source, Obj_AI_Base target, float range)
        {
            return source.Extend(target.ServerPosition.To2D(), range);
        }

        public static Vector2 Extend(this Vector2 source, Vector3 target, float range)
        {
            return source.Extend(target.To2D(), range);
        }

        public static Vector2 Extend(this Vector2 source, Vector2 target, float range)
        {
            return source + range * (target - source).Normalized();
        }

        #endregion

        #region Collision

        public static CollisionFlags GetCollisionFlags(this Vector2 vector)
        {
            return NavMesh.GetCollisionFlags(vector.X, vector.Y);
        }

        public static CollisionFlags GetCollisionFlags(this Vector3 vector)
        {
            return GetCollisionFlags(vector.To2D());
        }

        #region Bools

        public static bool IsWall(this Vector2 vector)
        {
            return NavMesh.GetCollisionFlags(vector.X, vector.Y).HasFlag(CollisionFlags.Wall);
        }

        public static bool IsWall(this Vector3 vector)
        {
            return IsWall(vector.To2D());
        }

        public static bool IsBuilding(this Vector2 vector)
        {
            return NavMesh.GetCollisionFlags(vector.X, vector.Y).HasFlag(CollisionFlags.Building);
        }

        public static bool IsBuilding(this Vector3 vector)
        {
            return IsBuilding(vector.To2D());
        }

        public static bool IsGrass(this Vector2 vector)
        {
            return NavMesh.GetCollisionFlags(vector.X, vector.Y).HasFlag(CollisionFlags.Grass);
        }

        public static bool IsGrass(this Vector3 vector)
        {
            return IsGrass(vector.To2D());
        }

        public static bool IsUnderTurret(this Vector2 position)
        {
            return
                EntityManager.Turrets.AllTurrets.Any(
                    turret => turret.Distance(position, true) <= turret.GetAutoAttackRange().Pow());
        }

        public static bool IsUnderTurret(this Vector3 position)
        {
            return IsUnderTurret(position.To2D());
        }

        public static bool IsUnderTurret(this Obj_AI_Base target)
        {
            return IsUnderTurret(target.ServerPosition);
        }

        public static bool IsUnderEnemyturret(this Obj_AI_Base target)
        {
            return
                EntityManager.Turrets.AllTurrets.Any(
                    turret => target.Team != turret.Team && turret.IsInAutoAttackRange(target));
        }

        public static bool IsUnderHisturret(this Obj_AI_Base target)
        {
            return
                EntityManager.Turrets.AllTurrets.Any(
                    turret => target.Team == turret.Team && turret.IsInAutoAttackRange(target));
        }

        public static Vector3[] RealPath(this Obj_AI_Base unit)
        {
            return Prediction.Position.GetRealPath(unit);
        }

        public static bool IsOnScreen(this Vector2 p)
        {
            return p.X <= Drawing.Width && p.X >= 0 && p.Y <= Drawing.Height && p.Y >= 0;
        }

        public static bool IsOnScreen(this Vector3 p)
        {
            return p.WorldToScreen().IsOnScreen();
        }

        #endregion Bools

        #endregion Collision

        #region PathRelated

        internal static List<Vector2> GetWaypoints(this Obj_AI_Base unit)
        {
            var result = new List<Vector2>();

            if (unit.IsVisible)
            {
                result.Add(unit.ServerPosition.To2D());
                var path = unit.Path;
                if (path.Length > 0)
                {
                    var first = path[0].To2D();
                    if (first.Distance(result[0], true) > 40)
                    {
                        result.Add(first);
                    }

                    for (int i = 1; i < path.Length; i++)
                    {
                        result.Add(path[i].To2D());
                    }
                }
            }
            //else if (WaypointTracker.StoredPaths.ContainsKey(unit.NetworkId))
            //{
            //    var path = WaypointTracker.StoredPaths[unit.NetworkId];
            //    var timePassed = (Utils.TickCount - WaypointTracker.StoredTick[unit.NetworkId]) / 1000f;
            //    if (path.PathLength() >= unit.MoveSpeed * timePassed)
            //    {
            //        result = CutPath(path, (int)(unit.MoveSpeed * timePassed));
            //    }
            //}

            return result;
        }

        public static List<Vector2> CutPath(this List<Vector2> path, float distance)
        {
            var result = new List<Vector2>();
            var Distance = distance;
            if (distance < 0)
            {
                path[0] = path[0] + distance * (path[1] - path[0]).Normalized();
                return path;
            }

            for (var i = 0; i < path.Count - 1; i++)
            {
                var dist = path[i].Distance(path[i + 1]);
                if (dist > Distance)
                {
                    result.Add(path[i] + Distance * (path[i + 1] - path[i]).Normalized());
                    for (var j = i + 1; j < path.Count; j++)
                    {
                        result.Add(path[j]);
                    }

                    break;
                }
                Distance -= dist;
            }
            return result.Count > 0 ? result : new List<Vector2> { path.Last() };
        }

        #endregion PathRelated

        public static Vector3 GetMissileFixedYPosition(this MissileClient target)
        {
            var pos = target.Position;
            return new Vector3(pos.X, pos.Y, pos.Z - 100);
        }
    }

    /// <summary>
    /// Count Extensions
    /// </summary>
    public static partial class Extensions
    {
        #region NoPred

        public static int CountEnemiesInRange(this Vector3 position, float range)
        {
            return CountEnemiesInRange(position.To2D(), range);
        }

        public static int CountEnemiesInRange(this Vector2 position, float range)
        {
            var rangeSqr = range.Pow();
            return EntityManager.Heroes.Enemies.Count(o => o.IsValidTarget() && o.Distance(position, true) < rangeSqr);
        }

        public static int CountEnemiesInRange(this GameObject target, float range)
        {
            var baseObject = target as Obj_AI_Base;
            return CountEnemiesInRange(baseObject != null ? baseObject.ServerPosition : target.Position, range);
        }

        public static int CountAlliesInRange(this Vector3 position, float range)
        {
            return CountAlliesInRange(position.To2D(), range);
        }

        public static int CountAlliesInRange(this Vector2 position, float range)
        {
            var rangeSqr = range.Pow();
            return EntityManager.Heroes.Allies.Count(o => o.IsValidTarget() && o.Distance(position, true) < rangeSqr);
        }

        public static int CountAlliesInRange(this GameObject target, float range)
        {
            var baseObject = target as Obj_AI_Base;
            return CountAlliesInRange(baseObject != null ? baseObject.ServerPosition : target.Position, range);
        }

        public static int CountAllyMinionsInRange(this Vector3 position, float range)
        {
            return CountAllyMinionsInRange(position.To2D(), range);
        }

        public static int CountAllyMinionsInRange(this Vector2 position, float range)
        {
            var rangeSqr = range.Pow();
            return
                EntityManager.MinionsAndMonsters.AlliedMinions.Count(
                    o => o.IsValidTarget() && o.Distance(position, true) < rangeSqr);
        }

        public static int CountAllyMinionsInRange(this GameObject target, float range)
        {
            var baseObject = target as Obj_AI_Base;
            return CountAllyMinionsInRange(baseObject != null ? baseObject.ServerPosition : target.Position, range);
        }

        public static int CountEnemyMinionsInRange(this Vector3 position, float range)
        {
            return CountEnemyMinionsInRange(position.To2D(), range);
        }

        public static int CountEnemyMinionsInRange(this Vector2 position, float range)
        {
            var rangeSqr = range.Pow();
            return
                EntityManager.MinionsAndMonsters.EnemyMinions.Count(
                    o => o.IsValidTarget() && o.Distance(position, true) < rangeSqr);
        }

        public static int CountEnemyMinionsInRange(this GameObject target, float range)
        {
            var baseObject = target as Obj_AI_Base;
            return CountEnemyMinionsInRange(baseObject != null ? baseObject.ServerPosition : target.Position, range);
        }

        #endregion NoPred

        #region With Pred

        #region Heroes

        //Enemies
        public static int CountEnemyHeroesInRangeWithPrediction(this Vector2 position, int range, int delay = 250)
        {
            return
                EntityManager.Heroes.Enemies.Count(
                    e => e.IsValidTarget() && Prediction.Position.PredictUnitPosition(e, delay).IsInRange(position, range));
        }

        public static int CountEnemyHeroesInRangeWithPrediction(this Vector3 position, int range, int delay = 250)
        {
            return
                EntityManager.Heroes.Enemies.Count(
                    e => e.IsValidTarget() && Prediction.Position.PredictUnitPosition(e, delay).IsInRange(position, range));
        }

        public static int CountEnemyHeroesInRangeWithPrediction(this GameObject target, int range, int delay = 250)
        {
            return
                EntityManager.Heroes.Enemies.Count(
                    e => e.IsValidTarget() && Prediction.Position.PredictUnitPosition(e, delay).IsInRange(target, range));
        }

        //Allies
        public static int CountEnemyAlliesInRangeWithPrediction(this Vector2 position, int range, int delay = 250)
        {
            return
                EntityManager.Heroes.Allies.Count(
                    e => e.IsValidTarget() && Prediction.Position.PredictUnitPosition(e, delay).IsInRange(position, range));
        }

        public static int CountEnemyAlliesInRangeWithPrediction(this Vector3 position, int range, int delay = 250)
        {
            return
                EntityManager.Heroes.Allies.Count(
                    e => e.IsValidTarget() && Prediction.Position.PredictUnitPosition(e, delay).IsInRange(position, range));
        }

        public static int CountEnemyAlliesInRangeWithPrediction(this GameObject target, int range, int delay = 250)
        {
            return
                EntityManager.Heroes.Allies.Count(
                    e => e.IsValidTarget() && Prediction.Position.PredictUnitPosition(e, delay).IsInRange(target, range));
        }

        #endregion Heroes

        #region Minions

        //Enemies
        public static int CountEnemyMinionsInRangeWithPrediction(this Vector2 position, int range, int delay = 250)
        {
            return
                EntityManager.MinionsAndMonsters.EnemyMinions.Count(
                    e => e.IsValidTarget() && Prediction.Position.PredictUnitPosition(e, delay).IsInRange(position, range));
        }

        public static int CountEnemyMinionsInRangeWithPrediction(this Vector3 position, int range, int delay = 250)
        {
            return
                EntityManager.MinionsAndMonsters.EnemyMinions.Count(
                    e => e.IsValidTarget() && Prediction.Position.PredictUnitPosition(e, delay).IsInRange(position, range));
        }

        public static int CountEnemyMinionsInRangeWithPrediction(this GameObject target, int range, int delay = 250)
        {
            return
                EntityManager.MinionsAndMonsters.EnemyMinions.Count(
                    e => e.IsValidTarget() && Prediction.Position.PredictUnitPosition(e, delay).IsInRange(target.Position, range));
        }

        //Allies

        public static int CountAllyMinionsInRangeWithPrediction(this Vector2 position, int range, int delay = 250)
        {
            return
                EntityManager.MinionsAndMonsters.AlliedMinions.Count(
                    e => e.IsValidTarget() && Prediction.Position.PredictUnitPosition(e, delay).IsInRange(position, range));
        }

        public static int CountAllyMinionsInRangeWithPrediction(this Vector3 position, int range, int delay = 250)
        {
            return
                EntityManager.MinionsAndMonsters.AlliedMinions.Count(
                    e => e.IsValidTarget() && Prediction.Position.PredictUnitPosition(e, delay).IsInRange(position, range));
        }

        public static int CountAllyMinionsInRangeWithPrediction(this GameObject target, int range, int delay = 250)
        {
            return
                EntityManager.MinionsAndMonsters.AlliedMinions.Count(
                    e => e.IsValidTarget() && Prediction.Position.PredictUnitPosition(e, delay).IsInRange(target.Position, range));
        }

        #endregion Minions

        #endregion With Pred
    }

    /// <summary>
    /// Color Extensions
    /// </summary>
    public static partial class Extensions
    {
        public static Color ToSharpDX(this System.Drawing.Color color)
        {
            return new Color(color.R, color.G, color.B, color.A);
        }

        public static System.Drawing.Color ToSystem(this Color color)
        {
            return System.Drawing.Color.FromArgb(color.A, color.R, color.G, color.B);
        }
    }

    /// <summary>
    /// Drawings
    /// </summary>
    public static partial class Extensions
    {
        public static void DrawCircle(this GameObject target, int radius, System.Drawing.Color color, float lineWidth = 3f)
        {
            Circle.Draw(color.ToSharpDX(), radius, lineWidth, target);
        }

        public static void DrawCircle(this GameObject target, int radius, Color color, float lineWidth = 3f)
        {
            Circle.Draw(color, radius, lineWidth, target);
        }

        public static void DrawCircle(this Vector2 position, int radius, Color color, float lineWidth = 3f)
        {
            Circle.Draw(color, radius, lineWidth, position.To3D());
        }

        public static void DrawCircle(this Vector3 position, int radius, Color color, float lineWidth = 3f)
        {
            Circle.Draw(color, radius, lineWidth, position);
        }
    }

    /// <summary>
    /// Minion related
    /// </summary>
    public static partial class Extensions
    {
        private static readonly List<string> PetList = new List<string>
        {
            "annietibbers",
            "elisespiderling",
            "heimertyellow",
            "heimertblue",
            "malzaharvoidling",
            "shacobox",
            "yorickspectralghoul",
            "yorickdecayedghoul",
            "yorickravenousghoul",
            "zyrathornplant",
            "zyragraspingplant"
        };

        private static readonly List<string> CloneList = new List<string> { "leblanc", "shaco", "monkeyking" };


        public static bool IsPet(this Obj_AI_Minion minion)
        {
            var name = minion.CharData.BaseSkinName.ToLower();
            return PetList.Contains(name);
        }

        public static bool IsClone(this Obj_AI_Minion minion)
        {
            var name = minion.CharData.BaseSkinName.ToLower();
            return CloneList.Contains(name);
        }
    }
}
