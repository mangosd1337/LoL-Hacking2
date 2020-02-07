using System;
using System.Collections.Generic;
using System.Linq;
using EloBuddy.SDK.Enumerations;
using EloBuddy.SDK.Utils;
using SharpDX;

// ReSharper disable CoVariantArrayConversion

namespace EloBuddy.SDK
{
    public static class Spell
    {
        public enum CastFailures
        {
            TargetOutOfRange,
            SpellNotReady,
            MinimumHitChance,
            SpellHumanized
        }

        #region Abstract And Interface

        public interface ISpell
        {
            bool Cast();
            bool Cast(Obj_AI_Base targetEntity);
            bool Cast(Vector3 targetPosition);
        }

        /// <summary>
        /// Base of all spells
        /// </summary>
        public abstract class SpellBase : ISpell
        {
            public delegate void SpellCastedHandler(SpellBase spell, GameObjectProcessSpellCastEventArgs args);

            public event SpellCastedHandler OnSpellCasted;

            /// <summary>
            /// The SpellDataInst handle which this class wraps
            /// </summary>
            public SpellDataInst Handle
            {
                get { return Player.GetSpell(Slot); }
            }

            /// <summary>
            /// When any .Cast() method returns false, the failure code
            /// will be stored in this property
            /// </summary>
            public CastFailures LastCastFailure { get; protected set; }

            /// <summary>
            /// Returns true if the spell is learned
            /// </summary>
            public bool IsLearned
            {
                get { return Slot != SpellSlot.Unknown && Handle.IsLearned; }
            }

            public bool IsOnCooldown
            {
                get { return Slot != SpellSlot.Unknown && Handle.IsOnCooldown; }
            }

            public int Level
            {
                get { return Slot != SpellSlot.Unknown ? Handle.Level : 0; }
            }

            public string Name
            {
                get { return Slot != SpellSlot.Unknown ? Handle.Name : ""; }
            }

            public SpellSlot Slot { get; set; }

            /// <summary>
            /// Returns the spell state
            /// </summary>
            public SpellState State
            {
                get { return Slot != SpellSlot.Unknown ? Handle.State : SpellState.Unknown; }
            }

            /// <summary>
            /// Returns the togglestate of the spell
            /// </summary>
            public int ToggleState
            {
                get { return Handle.ToggleState;}
            }

            /// <summary>
            /// Returns the mana cost of the spell
            /// </summary>
            public int ManaCost
            {
                get { return (int) Handle.SData.ManaCostArray[Level - 1]; }
            }

            /// <summary>
            /// Returns the quantity of ammo that the spell has
            /// </summary>
            public int AmmoQuantity
            {
                get { return Handle.Ammo; }
            }

            public int CastDelay { get; set; }

            public virtual uint Range { get; set; }

            public uint RangeSquared
            {
                get { return Range*Range; }
            }

            public Vector3? RangeCheckSource { get; set; }

            /// <summary>
            /// Returns the damagetype
            /// </summary>
            public DamageType DamageType { get; set; }

            protected SpellBase(SpellSlot spellSlot, uint spellRange = uint.MaxValue,
                DamageType dmgType = DamageType.Mixed)
            {
                Slot = spellSlot;
                // Initialize properties 
                Range = spellRange;
                DamageType = dmgType;

                if (Slot != SpellSlot.Unknown)
                {
                    // Listen to required events
                    Obj_AI_Base.OnSpellCast += OnSpellCast;
                }
            }

            public virtual bool Cast()
            {
                return false;
            }

            public virtual bool Cast(Obj_AI_Base targetEntity)
            {
                return false;
            }

            public virtual bool Cast(Vector3 targetPosition)
            {
                return false;
            }

            /// <summary>
            /// Get target using target selector
            /// </summary>
            /// <returns></returns>
            public virtual AIHeroClient GetTarget()
            {
                return TargetSelector.GetTarget(Range, DamageType);
            }

            public virtual float GetHealthPrediction(Obj_AI_Base target)
            {
                return Prediction.Health.GetPrediction(target, CastDelay);
            }

            /// <summary>
            /// Simple way of checking if target is in range and it`s not null
            /// </summary>
            /// <param name="target"></param>
            /// <returns></returns>
            public bool CanCast(Obj_AI_Base target)
            {
                return target.IsValidTarget(Range) && IsReady();
            }

            public bool IsReady(uint extraTime = 0)
            {
                if (Slot == SpellSlot.Unknown)
                {
                    return false;
                }
                return extraTime == 0
                    ? Player.GetSpell(Slot).IsReady
                    : Player.GetSpell(Slot).CooldownExpires + extraTime/1000f - Game.Time < 0;
            }

            public bool IsInRange(Obj_AI_Base targetEntity)
            {
                return (RangeCheckSource ?? Player.Instance.ServerPosition).Distance(targetEntity, true) < RangeSquared;
            }

            public bool IsInRange(Vector3 targetPosition)
            {
                return (RangeCheckSource ?? Player.Instance.ServerPosition).Distance(targetPosition, true) <
                       RangeSquared;
            }

            protected virtual bool PrecheckCast(Vector3? position = null)
            {
                if (!IsReady())
                {
                    LastCastFailure = CastFailures.SpellNotReady;
                    return false;
                }
                if (Chat.IsOpen /*|| Shop.IsOpen*/)
                {
                    LastCastFailure = CastFailures.SpellHumanized;
                    return false;
                }
                return true;
            }

            internal void OnSpellCast(Obj_AI_Base sender, GameObjectProcessSpellCastEventArgs args)
            {
                if (sender.IsMe && OnSpellCasted != null && args.Slot == Slot)
                {
                    OnSpellCasted(this, args);
                }
            }
        }

        /// <summary>
        /// Base of Skillshot and targetted spells
        /// </summary>
        public abstract class Ranged : SpellBase
        {
            protected Ranged(SpellSlot spellSlot, uint spellRange, DamageType dmgType = DamageType.Mixed)
                : base(spellSlot, spellRange, dmgType)
            {
            }

            public override bool Cast()
            {
                throw new SpellCastException("Can't cast ranged spell without target!");
            }

            protected override bool PrecheckCast(Vector3? position = null)
            {
                if (!position.HasValue)
                {
                    throw new ArgumentNullException("position");
                }

                if (base.PrecheckCast(position))
                {
                    if (!IsInRange(position.Value))
                    {
                        LastCastFailure = CastFailures.TargetOutOfRange;
                        return false;
                    }
                    return true;
                }
                return false;
            }
        }

        #endregion Abstract And Interface

        #region Skillshot Class

        public class Skillshot : Ranged
        {
            public SkillShotType Type { get; protected set; }

            // Public property setters
            public int Speed { get; set; }
            public int Width { get; set; }

            public int Radius
            {
                get { return Width/2; }
            }

            public int ConeAngleDegrees { get; set; }

            public HitChance MinimumHitChance { get; set; }
            public int AllowedCollisionCount { get; set; }
            public bool HasCollision { get { return AllowedCollisionCount > 0; } }

            public Vector3? SourcePosition { get; set; }

            public Skillshot(SpellSlot spellSlot, uint spellRange, SkillShotType skillShotType, int castDelay = 250,
                int? spellSpeed = null, int? spellWidth = null, DamageType dmgType = DamageType.Mixed)
                : base(spellSlot, spellRange, dmgType)
            {
                // Initialize properties
                Type = skillShotType;

                // Optional parameters
                CastDelay = castDelay;
                Speed = spellSpeed ?? 0;
                Width = spellWidth ?? 0;
                ConeAngleDegrees = 90;

                // Default values for properties
                MinimumHitChance = HitChance.Medium;
            }

            public virtual PredictionResult GetPrediction(Obj_AI_Base target)
            {
                switch (Type)
                {
                    case SkillShotType.Circular:
                        return Prediction.Position.PredictCircularMissile(target, Range, Radius, CastDelay, Speed,
                            SourcePosition);

                    case SkillShotType.Linear:
                        return Prediction.Position.PredictLinearMissile(target, Range, Width, CastDelay, Speed,
                            AllowedCollisionCount, SourcePosition);

                    case SkillShotType.Cone:
                        return Prediction.Position.PredictConeSpell(target, Range, ConeAngleDegrees, CastDelay, Speed,
                            SourcePosition);

                    default:
                        Logger.Log(LogLevel.Warn, "Skillshot type '{0}' not implemented yet!", Type);
                        break;
                }
                return null;
            }

            public override float GetHealthPrediction(Obj_AI_Base target)
            {
                var time = CastDelay;

                if (Math.Abs(Speed - float.MaxValue) > float.Epsilon)
                {
                    time +=
                        (int)
                        (1000*
                         Math.Max(
                             target.Position.Distance(SourcePosition ?? Player.Instance.ServerPosition) -
                             Player.Instance.BoundingRadius, 0)/Speed);
                }

                return Prediction.Health.GetPrediction(target, time);
            }

            public override bool Cast(Obj_AI_Base targetEntity)
            {
                if (!PrecheckCast(targetEntity.ServerPosition))
                {
                    return false;
                }

                // Get prediction
                var prediction = GetPrediction(targetEntity);

                // Check if the predicted hitchance matches
                if (prediction.HitChance < MinimumHitChance)
                {
                    LastCastFailure = CastFailures.MinimumHitChance;
                    return false;
                }

                // Check if the target is in the predicted range
                if (Player.Instance.Distance(prediction.UnitPosition, true) < RangeSquared)
                {
                    // Cast the spell
                    Player.CastSpell(Slot, prediction.CastPosition);
                    return true;
                }
                return false;
            }

            public override bool Cast(Vector3 targetPosition)
            {
                if (!PrecheckCast(targetPosition))
                {
                    return false;
                }

                Player.CastSpell(Slot, targetPosition);
                return true;
            }

            public virtual bool CastStartToEnd(Vector3 start, Vector3 end)
            {
                if (!PrecheckCast(end))
                {
                    return false;
                }

                Player.CastSpell(Slot, start, end);
                return true;
            }

            #region BestPosition

            public struct BestPosition
            {
                public int HitNumber;
                public Vector3 CastPosition;
            }

            public virtual BestPosition GetBestLinearCastPosition(IEnumerable<Obj_AI_Base> entities, int moreDelay = 0, Vector2? sourcePosition = null)
            {
                //TODO Fix this when prediction supports it
                var targets = entities.ToArray();

                switch (targets.Length)
                {
                    case 0:
                        return new BestPosition();
                    case 1:
                        return new BestPosition
                        {
                            CastPosition = targets[0].ServerPosition,
                            HitNumber = 1
                        };
                }

                var possiblePositions =
                    new List<Vector2>(
                        targets.OrderBy(o => o.Health)
                            .Select(o => Prediction.Position.PredictUnitPosition(o, CastDelay + moreDelay)));
                foreach (var target in targets)
                {
                    var predictedPos = Prediction.Position.PredictUnitPosition(target, CastDelay + moreDelay);
                    possiblePositions.AddRange(from t in targets
                        orderby t.Health
                        where t.NetworkId != target.NetworkId
                        select (predictedPos + predictedPos)/2);
                }

                var startPos = sourcePosition ?? Player.Instance.ServerPosition.To2D();
                var minionCount = 0;
                var result = Vector2.Zero;

                foreach (var pos in possiblePositions.Where(o => o.IsInRange(startPos, Range)))
                {
                    var endPos = startPos + Range*(pos - startPos).Normalized();
                    var count =
                        targets.Where(t => t.IsValidTarget())
                            .OrderBy(o => o.Health)
                            .Count(
                                o =>
                                    Prediction.Position.PredictUnitPosition(o, CastDelay + moreDelay)
                                        .Distance(startPos, endPos, true, true) <= Width*Width);

                    if (count >= minionCount)
                    {
                        result = endPos;
                        minionCount = count;
                    }
                }

                return new BestPosition
                {
                    CastPosition = result.To3DWorld(),
                    HitNumber = minionCount
                };
            }

            public virtual BestPosition GetBestCircularCastPosition(IEnumerable<Obj_AI_Base> entities, int hitChance = 60, int moreDelay = 0)
            {
                var bestCircularCastPos =
                    Prediction.Position.PredictCircularMissileAoe(entities.ToArray(), Range, Width, CastDelay + moreDelay,
                            Speed)
                        .OrderByDescending(r => r.GetCollisionObjects<AIHeroClient>().Length)
                        .ThenByDescending(r => r.GetCollisionObjects<Obj_AI_Base>().Length)
                        .FirstOrDefault();

                if (bestCircularCastPos != null && bestCircularCastPos.HitChancePercent >= hitChance)
                {
                    var predictedTargets =
                        bestCircularCastPos.GetCollisionObjects<AIHeroClient>()
                            .Concat(bestCircularCastPos.GetCollisionObjects<Obj_AI_Base>());
                    return new BestPosition
                    {
                        CastPosition = bestCircularCastPos.CastPosition,
                        HitNumber = predictedTargets.Count()
                    };
                }

                return new BestPosition
                {
                    CastPosition = Vector3.Zero,
                    HitNumber = 0
                };
            }

            /// <summary>
            /// Get the best postion to cast a cone spell
            /// </summary>
            /// <param name="entities"></param>
            /// <param name="hitChance"></param>
            /// <param name="moreDelay"></param>
            /// <returns></returns>
            public virtual BestPosition GetBestConeCastPosition(IEnumerable<Obj_AI_Base> entities, int hitChance = 60, int moreDelay = 0)
            {
                var bestCastConePos =
                    Prediction.Position.PredictConeSpellAoe(entities.ToArray(), Range, Width, CastDelay + moreDelay, Speed)
                        .OrderByDescending(r => r.GetCollisionObjects<AIHeroClient>().Length)
                        .ThenByDescending(r => r.GetCollisionObjects<Obj_AI_Base>().Length)
                        .FirstOrDefault();

                if (bestCastConePos != null && bestCastConePos.HitChancePercent >= hitChance)
                {
                    var predictedTargets =
                        bestCastConePos.GetCollisionObjects<AIHeroClient>()
                            .Concat(bestCastConePos.GetCollisionObjects<Obj_AI_Base>());

                    return new BestPosition
                    {
                        CastPosition = bestCastConePos.CastPosition,
                        HitNumber = predictedTargets.Count()
                    };
                }

                return new BestPosition {CastPosition = Vector3.Zero, HitNumber = 0};
            }

            /// <summary>
            /// Cast os the best position to hit as many heroes as possible
            /// </summary>
            /// <param name="minTargets">The minimun enemy hero count to cast spell</param>
            /// <param name="minHitchancePercent">Hitchance</param>
            /// <returns></returns>
            public virtual bool CastIfItWillHit(int minTargets = 2, int minHitchancePercent = 75)
            {
                switch (Type)
                {
                    case SkillShotType.Linear:
                        var targetsLinear = EntityManager.Heroes.Enemies.Where(CanCast).ToArray();
                        var pred = GetBestLinearCastPosition(targetsLinear);

                        if (pred.CastPosition != Vector3.Zero && pred.HitNumber > 0)
                        {
                            if (pred.HitNumber >= minTargets)
                            {
                                return Cast(pred.CastPosition);
                            }
                        }
                        break;
                    case SkillShotType.Circular:
                        var targetsCircular = EntityManager.Heroes.Enemies.Where(CanCast).ToArray();
                        var predCircular = GetBestLinearCastPosition(targetsCircular);

                        if (predCircular.CastPosition != Vector3.Zero && predCircular.HitNumber > 0)
                        {
                            if (predCircular.HitNumber >= minTargets)
                            {
                                return Cast(predCircular.CastPosition);
                            }
                        }
                        break;
                    case SkillShotType.Cone:
                        var targetsCone = EntityManager.Heroes.Enemies.Where(CanCast).ToArray();
                        var predCone = GetBestLinearCastPosition(targetsCone);

                        if (predCone.CastPosition != Vector3.Zero && predCone.HitNumber > 0)
                        {
                            if (predCone.HitNumber >= minTargets)
                            {
                                return Cast(predCone.CastPosition);
                            }
                        }
                        break;
                }
                return false;
            }

            /// <summary>
            /// Cast spells on the best farm position
            /// </summary>
            /// <param name="minMinion"></param>
            /// <param name="hitChance"></param>
            /// <returns></returns>
            public virtual bool CastOnBestFarmPosition(int minMinion = 3, int hitChance = 50)
            {
                switch (Type)
                {
                    case SkillShotType.Linear:
                        var minionsLinear = EntityManager.MinionsAndMonsters.EnemyMinions.Where(CanCast).OrderBy(m => m.Health);
                        var farmLocationLinear = GetBestLinearCastPosition(minionsLinear);
                        if (farmLocationLinear.HitNumber >= minMinion)
                        {
                            Cast(farmLocationLinear.CastPosition);
                        }
                        break;
                    case SkillShotType.Circular:
                        var minionsCircular = EntityManager.MinionsAndMonsters.EnemyMinions.Where(CanCast).OrderBy(m => m.Health).ToArray();

                        var farmLocationCircular = GetBestCircularCastPosition(minionsCircular, hitChance);
                        if (farmLocationCircular.HitNumber >= minMinion)
                        {
                            Cast(farmLocationCircular.CastPosition);
                        }
                        break;
                    case SkillShotType.Cone:
                        var minionsCone = EntityManager.MinionsAndMonsters.EnemyMinions.Where(CanCast).OrderBy(m => m.Health).ToArray();
                        var farmLocationCone = GetBestConeCastPosition(minionsCone, hitChance);
                        if (farmLocationCone.HitNumber >= minMinion)
                        {
                            Cast(farmLocationCone.CastPosition);
                        }
                        break;
                }
                return false;
            }

            #endregion BestPosition

            public virtual bool CastMinimumHitchance(Obj_AI_Base target, HitChance hitChance = HitChance.Medium)
            {
                if (target == null) return false;
                var pred = GetPrediction(target);
                return pred.HitChance >= hitChance && Cast(pred.CastPosition);
            }

            public virtual bool CastMinimumHitchance(Obj_AI_Base target, int hitChancePercent = 60)
            {
                if (target == null) return false;
                var pred = GetPrediction(target);
                return pred.HitChancePercent >= hitChancePercent && Cast(pred.CastPosition);
            }
        }

        #endregion Skillshot Class

        #region Targeted Class

        public class Targeted : Ranged
        {
            public Targeted(SpellSlot spellSlot, uint spellRange, DamageType dmgType = DamageType.Mixed)
                : base(spellSlot, spellRange, dmgType)
            {
            }

            public override bool Cast()
            {
                throw new SpellCastException("Can't cast targeted spell without target!");
            }

            public override bool Cast(Obj_AI_Base targetEntity)
            {
                if (!PrecheckCast(targetEntity.ServerPosition))
                {
                    return false;
                }

                Player.CastSpell(Slot, targetEntity);
                return true;
            }

            public override bool Cast(Vector3 targetPosition)
            {
                if (!PrecheckCast(targetPosition))
                {
                    return false;
                }

                Player.CastSpell(Slot, targetPosition);
                return true;
            }
        }

        #endregion Targeted Class

        #region Active

        /// <summary>
        /// Active spells are spells that dont use neither a target nor a position to cast
        /// </summary>
        public class Active : SpellBase
        {
            public Active(SpellSlot spellSlot, uint spellRange = uint.MaxValue, DamageType dmgType = DamageType.Mixed)
                : base(spellSlot, spellRange, dmgType)
            {
            }

            public override bool Cast()
            {
                if (!PrecheckCast())
                {
                    return false;
                }

                Player.CastSpell(Slot);
                return true;
            }

            public override bool Cast(Obj_AI_Base targetEntity)
            {
                throw new SpellCastException("Can't cast an active spell on a target!");
            }

            public override bool Cast(Vector3 targetPosition)
            {
                throw new SpellCastException("Can't cast an active spell on a position!");
            }
        }

        #endregion Active

        #region SimpleSkillShot

        /// <summary>
        /// Simple skillshots only requires range to work
        /// </summary>
        public class SimpleSkillshot : SpellBase
        {
            /// <summary>
            /// Simple SkillShot Constructor
            /// </summary>
            /// <param name="spellSlot">Slot of the spell</param>
            /// <param name="spellRange">Range of the spell</param>
            public SimpleSkillshot(SpellSlot spellSlot, uint spellRange = uint.MaxValue,
                DamageType dmgType = DamageType.Mixed) : base(spellSlot, spellRange, dmgType)
            {
            }

            /// <summary>
            /// Cast spell on the position
            /// </summary>
            /// <param name="targetPosition">Position to cast the spell</param>
            /// <returns></returns>
            public override bool Cast(Vector3 targetPosition)
            {
                if (!PrecheckCast(targetPosition))
                {
                    return false;
                }

                Player.CastSpell(Slot, targetPosition);
                return true;
            }

            public override bool Cast()
            {
                throw new SpellCastException("Can't cast a Simple Skillshot spell without a position!");
            }

            public override bool Cast(Obj_AI_Base targetEntity)
            {
                throw new SpellCastException("Can't cast a Simple Skillshot spell on a target!");
            }
        }

        #endregion SimpleSkillShot

        #region Chargeable Class

        public class Chargeable : Skillshot
        {
            public override uint Range
            {
                get
                {
                    return !IsCharging
                        ? base.Range
                        : Convert.ToUInt32(Math.Min(MaximumRange,
                            MinimumRange +
                            ((MaximumRange - MinimumRange)*
                             ((Core.GameTickCount - ChargingStartedTime)/(float) FullyChargedTime))));
                }
                set { base.Range = value; }
            }

            public uint MinimumRange { get; protected set; }
            public uint MaximumRange { get; protected set; }
            public int FullyChargedTime { get; protected set; }

            public bool FullyChargedCastsOnly { get; set; }

            public int ChargingStartedTime { get; protected set; }
            public bool IsCharging { get; protected set; }

            public bool IsFullyCharged
            {
                get { return Core.GameTickCount - ChargingStartedTime > FullyChargedTime; }
            }

            internal int _releaseCastSent;

            public Chargeable(SpellSlot spellSlot, uint minimumRange, uint maximumRange, int fullyChargedTime,
                int castDelay = 250, int? spellSpeed = null, int? spellWidth = null,
                DamageType dmgType = DamageType.Mixed)
                : base(spellSlot, minimumRange, SkillShotType.Linear, castDelay, spellSpeed, spellWidth, dmgType)
            {
                // Initialize properties
                MinimumRange = minimumRange;
                MaximumRange = maximumRange;
                FullyChargedTime = fullyChargedTime;

                // Listen to required events
                Spellbook.OnCastSpell += OnCastSpell;
                Spellbook.OnUpdateChargeableSpell += OnUpdateChargeableSpell;
                Spellbook.OnStopCast += OnStopCast;
                Obj_AI_Base.OnProcessSpellCast += OnProcessSpellCast;
            }

            public override PredictionResult GetPrediction(Obj_AI_Base target)
            {
                return Prediction.Position.PredictLinearMissile(target, IsCharging ? Range : MaximumRange, Width,
                    CastDelay, Speed, AllowedCollisionCount, SourcePosition);
            }

            public bool StartCharging()
            {
                if (!IsCharging && IsReady() && Core.GameTickCount - _releaseCastSent > 500)
                {
                    IsCharging = true;
                    ChargingStartedTime = Core.GameTickCount - Game.Ping;
                    Player.CastSpell(Slot, Game.CursorPos, false);
                    return true;
                }
                return IsCharging;
            }

            private void OnCastSpell(Spellbook sender, SpellbookCastSpellEventArgs args)
            {
                if (sender.Owner.IsMe && args.Slot == Slot)
                {
                    if (IsCharging)
                    {
                        if (Core.GameTickCount - ChargingStartedTime > 500)
                        {
                            IsCharging = false;
                            Player.Instance.Spellbook.UpdateChargeableSpell(Slot, args.EndPosition, true, true);
                        }
                        else
                        {
                            args.Process = false;
                        }
                    }
                    else
                    {
                        StartCharging();
                    }
                }
            }

            private void OnUpdateChargeableSpell(Spellbook sender, SpellbookUpdateChargeableSpellEventArgs args)
            {
                // Validate sender
                if (!sender.Owner.IsMe || args.Slot != Slot)
                {
                    return;
                }

                if (args.ReleaseCast)
                {
                    _releaseCastSent = Core.GameTickCount;
                    IsCharging = false;
                }
            }

            private void OnStopCast(Obj_AI_Base sender, SpellbookStopCastEventArgs args)
            {
                if (sender.IsMe)
                {
                    IsCharging = false;
                }
            }

            private void OnProcessSpellCast(Obj_AI_Base sender, GameObjectProcessSpellCastEventArgs args)
            {
                if (sender.IsMe && args.Slot == Slot && !IsCharging &&
                    Core.GameTickCount - _releaseCastSent > Game.Ping/2 + 100)
                {
                    IsCharging = true;
                    ChargingStartedTime = Core.GameTickCount - Game.Ping;
                }
            }

            public override bool Cast(Obj_AI_Base targetEntity)
            {
                if (IsCharging)
                {
                    var prediction = GetPrediction(targetEntity);
                    return prediction.HitChance >= MinimumHitChance && Cast(prediction.CastPosition);
                }
                return false;
            }

            public override bool Cast(Vector3 targetPosition)
            {
                if (IsCharging && Core.GameTickCount - ChargingStartedTime > 0)
                {
                    if (!FullyChargedCastsOnly || IsFullyCharged)
                    {
                        Player.CastSpell(Slot, targetPosition);
                        return true;
                    }
                }
                return false;
            }
        }
    }

    #endregion Chargeable Class

    public class SpellCastException : Exception
    {
        public SpellCastException(string message) : base(message)
        {
        }
    }
}
