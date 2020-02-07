using System.Collections.Generic;
using System.Text.RegularExpressions;
using Newtonsoft.Json;

// ReSharper disable InconsistentNaming
namespace DDragonToDLibrary
{
    public static class RiotChampionResonse
    {
        // This object contains champion list data.
        public class ChampionListDto
        {
            public Dictionary<string, ChampionDto> data { get; set; }
            public string format { get; set; }
            public Dictionary<string, string> keys { get; set; }
            public string type { get; set; }
            public string version { get; set; }
        }

        // This object contains champion data.
        public class ChampionDto
        {
            public List<string> allytips { get; set; }
            public string blurb { get; set; }
            public List<string> enemytips { get; set; }
            public string id { get; set; }
            public ImageDto image { get; set; }
            public InfoDto info { get; set; }
            public int key { get; set; }
            public string lore { get; set; }
            public string name { get; set; }
            public string partype { get; set; }
            public PassiveDto passive { get; set; }
            public List<RecommendedDto> recommended { get; set; }
            public List<SkinDto> skins { get; set; }
            public List<ChampionSpellDto> spells { get; set; }
            public StatsDto stats { get; set; }
            public List<string> tags { get; set; }
            public string title { get; set; }
        }

        // This object contains champion spell data.
        public class ChampionSpellDto
        {
            public List<ImageDto> altimages { get; set; }
            public List<float> cooldown { get; set; }
            public string cooldownBurn { get; set; }
            public List<int> cost { get; set; }
            public string costBurn { get; set; }
            public string costType { get; set; }
            public string description { get; set; }
            public List<List<float>> effect { get; set; }
            public List<string> effectBurn { get; set; }
            public string id { get; set; }
            public ImageDto image { get; set; }
            public string key { get; set; }
            public LevelTipDto leveltip { get; set; }
            public int maxrank { get; set; }
            public string name { get; set; }
            public object range { get; set; } // This field is either a List of Integer or the String 'self' for spells that target one's own champion.
            public string rangeBurn { get; set; }
            public string resource { get; set; }
            public string sanitizedDescription { get; set; }
            public string sanitizedTooltip
            {
                get { return Regex.Replace(tooltip, @"<.*?>", ""); }
            }
            public string tooltip { get; set; }
            public List<SpellVarsDto> vars { get; set; }
        }

        // This object contains image data.
        public class ImageDto
        {
            public string full { get; set; }
            public string group { get; set; }
            public int h { get; set; }
            public string sprite { get; set; }
            public int w { get; set; }
            public int x { get; set; }
            public int y { get; set; }
        }

        // This object contains champion information.
        public class InfoDto
        {
            public int attack { get; set; }
            public int defense { get; set; }
            public int difficulty { get; set; }
            public int magic { get; set; }
        }

        // This object contains champion passive data.
        public class PassiveDto
        {
            public string description { get; set; }
            public ImageDto image { get; set; }
            public string name { get; set; }
            public string sanitizedDescription { get; set; }
        }

        // This object contains champion recommended data.
        public class RecommendedDto
        {
            public List<BlockDto> blocks { get; set; }
            public string champion { get; set; }
            public string map { get; set; }
            public string mode { get; set; }
            public bool priority { get; set; }
            public string title { get; set; }
            public string type { get; set; }
        }

        // This object contains champion skin data.
        public class SkinDto
        {
            public int id { get; set; }
            public string name { get; set; }
            public int num { get; set; }
        }

        // This object contains champion stats data.
        public class StatsDto
        {
            public float armor { get; set; }
            public float armorperlevel { get; set; }
            public float attackdamage { get; set; }
            public float attackdamageperlevel { get; set; }
            public float attackrange { get; set; }
            public float attackspeedoffset { get; set; }
            public float attackspeedperlevel { get; set; }
            public float crit { get; set; }
            public float critperlevel { get; set; }
            public float hp { get; set; }
            public float hpperlevel { get; set; }
            public float hpregen { get; set; }
            public float hpregenperlevel { get; set; }
            public float movespeed { get; set; }
            public float mp { get; set; }
            public float mpperlevel { get; set; }
            public float mpregen { get; set; }
            public float mpregenperlevel { get; set; }
            public float spellblock { get; set; }
            public float spellblockperlevel { get; set; }
        }

        // This object contains champion level tip data.
        public class LevelTipDto
        {
            public List<string> effect { get; set; }
            public List<string> label { get; set; }
        }

        // This object contains spell vars data.
        public class SpellVarsDto
        {
            [JsonConverter(typeof(SingleOrArrayConverter<float>))]
            public List<float> coeff { get; set; }
            public string dyn { get; set; }
            public string key { get; set; }
            public string link { get; set; }
            public string ranksWith { get; set; }
        }

        // This object contains champion recommended block data.
        public class BlockDto
        {
            public List<BlockItemDto> items { get; set; }
            public bool recMath { get; set; }
            public string type { get; set; }
        }

        // This object contains champion recommended block item data.
        public class BlockItemDto
        {
            public int count { get; set; }
            public int id { get; set; }
        }
    }
}
