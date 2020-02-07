using System;
using System.Collections;
using System.Collections.Generic;
using System.Linq;
using System.Reflection;
using EloBuddy.SDK.Enumerations;
using EloBuddy.SDK.Utils;
using Newtonsoft.Json;
using Newtonsoft.Json.Converters;

namespace EloBuddy.SDK
{
    public static class DamageLibraryManager
    {
        #region Json Objects

        internal class StageSpell
        {
            [JsonConverter(typeof (StringEnumConverter))]
            public DamageLibrary.SpellStages Stage { get; set; }
            public ChampionSpell SpellData { get; set; }
        }

        internal class ChampionSpell
        {
            [JsonConverter(typeof (StringEnumConverter))]
            public DamageType DamageType { get; set; }
            public float[] Damages { get; set; }
            public SpellBonus[] BonusDamages { get; set; }
            public ExpresionDamage[] ExpresionDamages { get; set; }

            public Damage.DamageSourceBase ToDamageSourceBase(SpellSlot slot)
            {
                var boundle = new Damage.DamageSourceBoundle();

                // Base damage
                boundle.Add(new Damage.DamageSource(slot, DamageType)
                {
                    Damages = Damages
                });

                // All bonus damages
                foreach (var bonus in BonusDamages)
                {
                    boundle.Add(new Damage.BonusDamageSource(slot, bonus.DamageType)
                    {
                        DamagePercentages = bonus.DamagePercentages,
                        ScalingTarget = bonus.ScalingTarget,
                        ScalingType = bonus.ScalingType
                    });
                }
                if (ExpresionDamages != null)
                {
                    foreach (var expresionBonusDamage in ExpresionDamages)
                    {
                        boundle.AddExpresion(new Damage.ExpresionDamageSource(expresionBonusDamage.Expression, slot, expresionBonusDamage.DamageType)
                        {
                            DamageType = expresionBonusDamage.DamageType,
                            DamagePercentages = expresionBonusDamage.DamagePercentages,
                            Variables =
                                expresionBonusDamage.StaticVariables.Select(x => new Damage.ExpresionStaticVarible(x.Key, x.ScalingTarget, x.ScalingType))
                                    .Cast<Damage.IVariable>()
                                    .Concat(expresionBonusDamage.TypeVariables.Select(x => new Damage.ExpresionTypeVarible(x.Key, x.DamageType, x.Target, x.Name, x.Parameters)))
                                    .Concat(expresionBonusDamage.LevelVariables.Select(x => new Damage.ExpresionLevelVarible(x.Key, x.Slot, x.Damages))),
                            Expression = expresionBonusDamage.Expression,
                            Condition = expresionBonusDamage.Condition
                        });
                    }
                }

                return boundle;
            }
        }

        internal class SpellBonus
        {
            [JsonConverter(typeof (StringEnumConverter))]
            public DamageType DamageType { get; set; }
            public float[] DamagePercentages { get; set; }
            [JsonConverter(typeof (StringEnumConverter))]
            public Damage.ScalingTarget ScalingTarget { get; set; }
            [JsonConverter(typeof (StringEnumConverter))]
            public Damage.ScalingType ScalingType { get; set; }
        }

        internal class ExpresionDamage
        {
            [JsonConverter(typeof (StringEnumConverter))]
            public DamageType DamageType { get; set; }
            public float[] DamagePercentages { get; set; }
            public string Expression { get; set; }
            public string Condition { get; set; }
            public StaticDamageVarible[] StaticVariables { get; set; }
            public LevelDamageVarible[] LevelVariables { get; set; }
            public TypeDamageVarible[] TypeVariables { get; set; }
        }

        internal class TypeDamageVarible
        {
            public string Name { get; set; }
            public string[] Parameters { get; set; }
            [JsonConverter(typeof (StringEnumConverter))]
            public DamageType DamageType { get; set; }
            [JsonConverter(typeof (StringEnumConverter))]
            public Damage.ScalingTarget Target { get; set; }
            public string Key { get; set; }
        }

        internal class StaticDamageVarible
        {
            [JsonConverter(typeof (StringEnumConverter))]
            public Damage.ScalingTarget ScalingTarget { get; set; }
            [JsonConverter(typeof (StringEnumConverter))]
            public Damage.ScalingType ScalingType { get; set; }
            public string Key { get; set; }
        }

        internal class LevelDamageVarible
        {
            public string Key { get; set; }
            [JsonConverter(typeof (StringEnumConverter))]
            public SpellSlot Slot { get; set; }
            public float[] Damages { get; set; }
        }

        #endregion

        #region Database Objects

        internal class DamageDatabase : Dictionary<Champion, ChampionDamageDatabase>
        {
            public ChampionDamageDatabase NewChampion(Champion key)
            {
                ChampionDamageDatabase db;
                if (TryMakeNewChampion(key, out db))
                {
                    return db;
                }
                throw new ArgumentException("Key is already present in the Champion Database. Use TryMakeNewChampion() to prevent this in the future.", "key");
            }

            public bool TryMakeNewChampion(Champion key, out ChampionDamageDatabase database)
            {
                database = null;
                if (ContainsKey(key))
                {
                    return false;
                }

                //Does not contain key, lets make a database for that key and add it to the db
                database = new ChampionDamageDatabase();
                Add(key, database);
                return true;
            }
        }

        public class LockedDictionary<TKey, TValue> : IEnumerable<KeyValuePair<TKey, TValue>>
        {
            private readonly Dictionary<TKey, TValue> _dictionary = new Dictionary<TKey, TValue>();

            public TValue this[TKey key]
            {
                get { return _dictionary[key]; }
                internal set { _dictionary[key] = value; }
            }

            public bool ContainsKey(TKey key)
            {
                return _dictionary.ContainsKey(key);
            }

            internal TValue Add(TKey key, TValue value)
            {
                _dictionary.Add(key, value);
                return value;
            }

            public IEnumerator<KeyValuePair<TKey, TValue>> GetEnumerator()
            {
                return _dictionary.GetEnumerator();
            }

            IEnumerator IEnumerable.GetEnumerator()
            {
                return GetEnumerator();
            }
        }

        public class ChampionDamageDatabase : LockedDictionary<SpellSlot, SpellDamageDatabase>
        {
            /// <summary>
            /// Returns a new <see cref="SpellDamageDatabase"/> if the specified <see cref="SpellSlot"/> is not already in the database.
            /// If the <see cref="SpellSlot"/> is in the database, it will return the value associated to it.
            /// </summary>
            public SpellDamageDatabase NewOrExistingSpell(SpellSlot key)
            {
                return ContainsKey(key) ? this[key] : NewSpell(key);
            }

            public SpellDamageDatabase NewSpell(SpellSlot key)
            {
                SpellDamageDatabase db;
                Add(key, db = new SpellDamageDatabase());
                return db;
            }
        }

        public class SpellDamageDatabase : LockedDictionary<DamageLibrary.SpellStages, Damage.DamageSourceBase>
        {
            internal void AddStageSpell(StageSpell spell, SpellSlot slot)
            {
                Add(spell.Stage, spell.SpellData.ToDamageSourceBase(slot));
            }
        }

        #endregion

        #region Initialization

        internal static DamageDatabase Database { get; set; }

        static DamageLibraryManager()
        {
            Database = new DamageDatabase();
        }

        internal static void Initialize()
        {
            // Load damages from DamageLibary.json
            var champData = JsonConvert.DeserializeObject<Dictionary<string, Dictionary<SpellSlot, List<StageSpell>>>>(DefaultSettings.DamageLibrary);
            //Collecting Data from every champion inside the current game...
            var heroes = EntityManager.Heroes.AllHeroes.Select(x => x.Hero);
            foreach (var champ in heroes)
            {
                //Selected champ, get his spell data name...
                var csdName = GetSpellDataChampName(champ);
                if (csdName != null && champData.ContainsKey(csdName))
                {
                    //champ exists in DamageDatabase, see if we have already added him to the generated database
                    ChampionDamageDatabase champDamageDatabase;

                    if (Database.TryMakeNewChampion(champ, out champDamageDatabase))
                    {
                        foreach (var entry in champData[csdName])
                        {
                            var spelldd = champDamageDatabase.NewOrExistingSpell(entry.Key);
                            foreach (var stage in entry.Value)
                            {
                                if (spelldd.ContainsKey(stage.Stage))
                                {
                                    Logger.Log(LogLevel.Warn, "The stage '{0}' is already present in '{1}' {2}!", stage.Stage, csdName, entry.Key);
                                    continue;
                                }
                                spelldd.AddStageSpell(stage, entry.Key);
                            }
                        }
                    }
                }
                else if (!Database.ContainsKey(champ))
                {
                    Logger.Log(LogLevel.Warn, "'{0}' was not found in the DamageLibrary!", csdName);
                    Database.Add(champ, new ChampionDamageDatabase());
                }
            }
        }

        #endregion

        #region Contains / Try Logic

        internal static bool ContainsChampion(Champion champion)
        {
            return Database.ContainsKey(champion);
        }

        internal static bool ContainsSlot(Champion champion, SpellSlot slot)
        {
            return ContainsChampion(champion) && Database[champion].ContainsKey(slot);
        }

        internal static bool ContainsStage(Champion champion, SpellSlot slot, DamageLibrary.SpellStages stage)
        {
            return ContainsSlot(champion, slot) && Database[champion][slot].ContainsKey(stage);
        }

        internal static bool TryGetChampion(Champion champion, out ChampionDamageDatabase database)
        {
            database = null;
            if (!ContainsChampion(champion))
            {
                return false;
            }

            database = Database[champion];
            return true;
        }

        internal static bool TryGetSlot(Champion champion, SpellSlot slot, out SpellDamageDatabase database)
        {
            database = null;
            if (!ContainsSlot(champion, slot))
            {
                return false;
            }
            database = Database[champion][slot];
            return true;
        }

        internal static bool TryGetStage(Champion champion, SpellSlot slot, DamageLibrary.SpellStages stage, out Damage.DamageSourceBase damageSourceBase)
        {
            damageSourceBase = null;
            if (!ContainsStage(champion, slot, stage))
            {
                return false;
            }

            damageSourceBase = Database[champion][slot][stage];
            return true;
        }

        #endregion

        internal static string GetSpellDataChampName(Champion champion)
        {
            switch (champion)
            {
                // Spacing
                case Champion.AurelionSol:
                    return "Aurelion Sol";
                case Champion.DrMundo:
                    return "Dr Mundo";
                case Champion.JarvanIV:
                    return "Jarvan IV";
                case Champion.LeeSin:
                    return "Lee Sin";
                case Champion.MasterYi:
                    return "Master Yi";
                case Champion.MissFortune:
                    return "Miss Fortune";
                case Champion.TahmKench:
                    return "Tahm Kench";
                case Champion.TwistedFate:
                    return "Twisted Fate";
                case Champion.XinZhao:
                    return "Xin Zhao";

                // Special characters
                case Champion.Chogath:
                    return "Cho'Gath";
                case Champion.Khazix:
                    return "Kha'Zix";
                case Champion.KogMaw:
                    return "Kog'Maw";
                case Champion.RekSai:
                    return "Rek'Sai";
                case Champion.Velkoz:
                    return "Vel'Koz";

                // Capitalization
                case Champion.FiddleSticks:
                    return "Fiddlesticks";
                case Champion.Leblanc:
                    return "LeBlanc";

                // Totally different naming, rito pls
                case Champion.MonkeyKing:
                    return "Wukong";

                // Same name
                default:
                    return champion.ToString();
            }
        }

        internal static void ReplaceSpell<T>(AIHeroClient hero, SpellSlot slot, DamageLibrary.SpellStages stage) where T : DamageSourceReplacement
        {
            Database[hero.Hero][slot][stage] =
                (T) Activator.CreateInstance(typeof (T), BindingFlags.NonPublic | BindingFlags.Instance, null, new object[] { slot, Database[hero.Hero][slot][stage] }, null);
        }

        #region Custom spell definitions

        internal abstract class DamageSourceReplacement : Damage.DamageSourceBoundle
        {
            internal SpellSlot Slot { get; set; }
            internal Damage.DamageSourceBase OriginalDamageSource { get; set; }
            internal Damage.DamageSourceBoundle MonsterDamage { get; set; }
            internal int MonsterMaxDamage { get; set; }
            internal int[] MonsterMaxDamages { get; set; }

            internal DamageSourceReplacement(SpellSlot slot, Damage.DamageSourceBase originalDamageSource)
            {
                Slot = slot;
                OriginalDamageSource = originalDamageSource;
                MonsterMaxDamage = -1;
                Add(originalDamageSource);
            }

            public void SetMonsterDamage(DamageType damageType, float[] damages = null, params Damage.DamageSourceBase[] sources)
            {
                MonsterDamage = new Damage.DamageSourceBoundle();
                MonsterDamage.Add(new Damage.DamageSource(Slot, damageType)
                {
                    Damages = damages ?? new float[] { 0, 0, 0, 0, 0 }
                });
                foreach (var source in sources)
                {
                    MonsterDamage.Add(source);
                }
            }

            public override float GetDamage(Obj_AI_Base source, Obj_AI_Base target)
            {
                if (target is Obj_AI_Minion)
                {
                    if (Condition != null && !Condition(target))
                    {
                        return 0;
                    }
                    var maxMonsterDamage = MonsterMaxDamage > -1 ? MonsterMaxDamage : MonsterMaxDamages != null ? MonsterMaxDamages[source.Spellbook.GetSpell(Slot).Level - 1] : -1;
                    if (MonsterDamage != null)
                    {
                        return maxMonsterDamage > -1 ? Math.Min(maxMonsterDamage, MonsterDamage.GetDamage(source, target)) : MonsterDamage.GetDamage(source, target);
                    }
                    if (maxMonsterDamage > -1)
                    {
                        return Math.Min(maxMonsterDamage, base.GetDamage(source, target));
                    }
                }
                return base.GetDamage(source, target);
            }
        }

        #region Gnar

        internal class MiniGnarW : DamageSourceReplacement
        {
            internal MiniGnarW(SpellSlot slot, Damage.DamageSourceBase originalDamageSource) : base(slot, originalDamageSource)
            {
                SetMonsterDamage(DamageType.Magical, new float[] { 110, 170, 230, 290, 350 }, new Damage.BonusDamageSource(SpellSlot.W, DamageType.Magical)
                {
                    DamagePercentages = new float[] { 1, 1, 1, 1, 1 },
                    ScalingType = Damage.ScalingType.AbilityPoints,
                    ScalingTarget = Damage.ScalingTarget.Source
                });

                Condition = target => target.GetBuffCount("GnarWProc") == 2;
            }
        }

        #endregion

        #region Kalista

        internal class KalistaW : DamageSourceReplacement
        {
            public KalistaW(SpellSlot slot, Damage.DamageSourceBase originalDamageSource) : base(slot, originalDamageSource)
            {
                MonsterMaxDamages = new[] { 75, 125, 150, 175, 200 };
                Condition = target => target.Buffs.Any(b => b.IsValid() && b.Name.Contains("kalistacoopstrikemark"));
            }
        }

        #endregion

        #region Vayne

        internal class VayneW : DamageSourceReplacement
        {
            internal VayneW(SpellSlot slot, Damage.DamageSourceBase originalDamageSource) : base(slot, originalDamageSource)
            {
                MonsterMaxDamage = 200;
                Condition = target => target.GetBuffCount("VayneSilverDebuff") == 2;
            }
        }

        #endregion

        #endregion
    }

    public static class DamageLibrary
    {
        internal static void Initialize()
        {
            DamageLibraryManager.Initialize();
        }

        public static DamageLibraryManager.ChampionDamageDatabase GetChampionDamageDatabase(this AIHeroClient source)
        {
            return GetChampionDamageDatabase(source.Hero);
        }

        public static DamageLibraryManager.ChampionDamageDatabase GetChampionDamageDatabase(Champion source)
        {
            DamageLibraryManager.ChampionDamageDatabase db;
            return DamageLibraryManager.TryGetChampion(source, out db) ? db : new DamageLibraryManager.ChampionDamageDatabase();
        }

        public static DamageLibraryManager.SpellDamageDatabase GetSpellDamageDatabase(this AIHeroClient source, SpellSlot slot)
        {
            return GetSpellDamageDatabase(source.Hero, slot);
        }

        public static DamageLibraryManager.SpellDamageDatabase GetSpellDamageDatabase(Champion source, SpellSlot slot)
        {
            DamageLibraryManager.SpellDamageDatabase db;
            return DamageLibraryManager.TryGetSlot(source, slot, out db) ? db : new DamageLibraryManager.SpellDamageDatabase();
        }

        public static float GetSpellDamage(this AIHeroClient source, Obj_AI_Base target, SpellSlot slot, SpellStages stage = SpellStages.Default)
        {
            if (source == null || target == null)
            {
                return 0f;
            }
            Damage.DamageSourceBase damageSpell;
            return DamageLibraryManager.TryGetStage(source.Hero, slot, stage, out damageSpell) ? damageSpell.GetDamage(source, target) : 0;
        }

        public enum SummonerSpells
        {
            Ignite,
            Smite
        }

        public static float GetSummonerSpellDamage(this AIHeroClient source, Obj_AI_Base target, SummonerSpells summonerSpell)
        {
            switch (summonerSpell)
            {
                case SummonerSpells.Ignite:
                    return 50 + 20 * source.Level - (target.HPRegenRate / 5 * 3);
                case SummonerSpells.Smite:

                    if (target is AIHeroClient)
                    {
                        if (source.Spellbook.Spells.Any(o => o.Name == "s5_summonersmiteplayerganker"))
                        {
                            return 20 + 8 * source.Level;
                        }
                        if (source.Spellbook.Spells.Any(o => o.Name == "s5_summonersmiteduel"))
                        {
                            return 54 + 6 * source.Level;
                        }
                    }
                    else
                    {
                        return new float[] { 390, 410, 430, 450, 480, 510, 540, 570, 600, 640, 680, 720, 760, 800, 850, 900, 950, 1000 }[source.Level - 1];
                    }
                    break;
            }

            return 0;
        }

        public static float GetItemDamage(this AIHeroClient source, Obj_AI_Base target, ItemId item)
        {
            switch (item)
            {
                case ItemId.Bilgewater_Cutlass:
                    return source.CalculateDamageOnUnit(target, DamageType.Magical, 100);
                case ItemId.Blade_of_the_Ruined_King:
                    return Math.Max(100, source.CalculateDamageOnUnit(target, DamageType.Physical, target.MaxHealth * 0.1f));
                case ItemId.Frost_Queens_Claim:
                    return source.CalculateDamageOnUnit(target, DamageType.Magical, 50 + 5 * source.Level);
                case ItemId.Hextech_Gunblade:
                    return source.CalculateDamageOnUnit(target, DamageType.Magical, 150 + 0.4f * source.TotalMagicalDamage);
                case ItemId.Liandrys_Torment:
                    return source.CalculateDamageOnUnit(target, DamageType.Magical, target.Health * 0.2f * 3 * (target.CanMove ? 1 : 2));
                case ItemId.Ravenous_Hydra_Melee_Only:
                    return source.CalculateDamageOnUnit(target, DamageType.Physical, 0.6f * source.TotalAttackDamage);
                case ItemId.Tiamat_Melee_Only:
                    return source.CalculateDamageOnUnit(target, DamageType.Physical, 0.6f * source.TotalAttackDamage);
                default:
                    Logger.Log(LogLevel.Info, "Item id '{0}' not yet added to DamageLibrary.GetItemDamage!", item);
                    break;
            }
            return 0;
        }

        internal static readonly List<string> SpecialCases = new List<string>
        {
            "All champs with 0 spell base attack damage",
            "Amumu W",
            "Ashe Passive",
            "Ashe Q",
            "Zed Passive",
            "Darius R",
            "Dr Mundo Q",
            "Dr Mundo E",
            "Ekko E",
            "Elise Q bonus",
            "Elise R",
            "Evelynn R",
            "Fiddlesticks E minions",
            "Fizz W bonus",
            "Garen Q",
            "Hecarim Q minions",
            "Heimerdinger empowered",
            "Jayce W second form",
            "Jayce E monster",
            "Jinx R",
            "Kalista E",
            "Karma ULT",
            "Kassadin R stacks",
            "KhaZix Q empowered",
            "KogMaw W",
            "LeBlanc R",
            "LeeSin Q second cast bonus",
            "Malzahar W",
            "Malzahar E damage per half second from default / 8",
            "Maokai W",
            "Malzahar R"
        };

        public enum SpellStages
        {
            Default,
            SecondCast,
            SecondForm,
            WayBack,
            Detonation,
            DamagePerSecond,
            Empowered,
            EmpoweredSecondCast,
            ToggledState,
            DamagePerStack,
            Passive
        }
    }
}
