using System.Collections.Generic;
using EloBuddy.SDK;
using Newtonsoft.Json;
using Newtonsoft.Json.Converters;

namespace DDragonToDLibrary
{
    public static class DamageLibrary
    {
        public class Champion
        {
            public Dictionary<SpellSlot, List<StageSpell>> Spells { get; set; }
        }

        public class StageSpell
        {
            [JsonConverter(typeof(StringEnumConverter))]
            public EloBuddy.SDK.DamageLibrary.SpellStages Stage { get; set; }
            public ChampionSpell SpellData { get; set; }
        }

        public class ChampionSpell
        {
            [JsonConverter(typeof(StringEnumConverter))]
            public DamageType DamageType { get; set; }
            public float[] Damages { get; set; }
            public SpellBonus[] BonusDamages { get; set; }
        }

        public class SpellBonus
        {
            [JsonConverter(typeof(StringEnumConverter))]
            public DamageType DamageType { get; set; }
            public float[] DamagePercentages { get; set; }
            [JsonConverter(typeof(StringEnumConverter))]
            public Damage.ScalingTarget ScalingTarget { get; set; }
            [JsonConverter(typeof(StringEnumConverter))]
            public Damage.ScalingType ScalingType { get; set; }
        }
    }
}
