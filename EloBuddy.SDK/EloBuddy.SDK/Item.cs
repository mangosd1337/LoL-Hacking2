using System.Collections.Generic;
using System.Linq;
using EloBuddy.SDK.Properties;
using Newtonsoft.Json;
using SharpDX;

// ReSharper disable UnusedAutoPropertyAccessor.Local
// ReSharper disable ClassNeverInstantiated.Global
// ReSharper disable ClassCannotBeInstantiated
// ReSharper disable InconsistentNaming

namespace EloBuddy.SDK
{
    public sealed class Item
    {
        public static Dictionary<ItemId, ItemInfo> ItemData { get; private set; }

        static Item()
        {
            var defaultContractResolver = new Newtonsoft.Json.Serialization.DefaultContractResolver();
            defaultContractResolver.DefaultMembersSearchFlags |= System.Reflection.BindingFlags.NonPublic;
            var jss = new JsonSerializerSettings { ContractResolver = defaultContractResolver };

            ItemData = JsonConvert.DeserializeObject<Dictionary<ItemId, ItemInfo>>(Resources.ItemData, jss);
        }

        public static void Initialize()
        {
        }

        public ItemId Id { get; private set; }
        private float _range;
        public float Range
        {
            get { return _range; }
            set
            {
                _range = value;
                RangeSqr = value * value;
            }
        }
        public float RangeSqr { get; private set; }
        public List<SpellSlot> Slots
        {
            get { return Player.Instance.InventoryItems.Where(slot => slot.Id == Id).Select(slot => slot.SpellSlot).ToList(); }
        }

        public ItemInfo ItemInfo { get; private set; }

        public Item(int id, float range = 0)
        {
            Id = (ItemId) id;
            if (ItemData.ContainsKey(Id))
            {
                ItemInfo = ItemData[Id];
            }
            Range = range;
        }

        public Item(ItemId id, float range = 0)
        {
            Id = id;
            if (ItemData.ContainsKey(Id))
            {
                ItemInfo = ItemData[Id];
            }
            Range = range;
        }

        public bool IsInRange(Obj_AI_Base target)
        {
            return IsInRange(target.ServerPosition);
        }

        public bool IsInRange(Vector2 target)
        {
            return IsInRange(target.To3D());
        }

        public bool IsInRange(Vector3 target)
        {
            return Player.Instance.ServerPosition.Distance(target, true) < RangeSqr;
        }

        public bool IsOwned(AIHeroClient target = null)
        {
            return HasItem(Id, target);
        }

        public bool IsReady()
        {
            return CanUseItem(Id);
        }

        public bool Cast()
        {
            return UseItem(Id);
        }

        public bool Cast(Obj_AI_Base target)
        {
            return UseItem(Id, target);
        }

        public bool Cast(Vector2 position)
        {
            return UseItem(Id, position);
        }

        public bool Cast(Vector3 position)
        {
            return UseItem(Id, position);
        }

        public void Buy()
        {
            Shop.BuyItem(Id);
        }

        public Item[] GetUpgrades()
        {
            if (ItemInfo == null)
            {
                return new Item[] { };
            }

            return ItemInfo.IntoId.Select(id => new Item(id)).ToArray();
        }

        public Item[] GetComponents()
        {
            if (ItemInfo == null)
            {
                return new Item[] { };
            }

            return ItemInfo.FromId.Select(id => new Item(id)).ToArray();
        }

        public int GoldRequired()
        {
            if (ItemInfo == null)
            {
                return 0;
            }

            return ItemInfo.Gold.Base + GetComponents().Where(it => !it.IsOwned()).Sum(it => it.GoldRequired());
        }

        /// <summary>
        /// Returns true if the hero has the item.
        /// </summary>
        public static bool HasItem(string name, AIHeroClient hero = null)
        {
            return (hero ?? Player.Instance).InventoryItems.Any(slot => slot.Name == name);
        }

        /// <summary>
        /// Returns true if the hero has the item.
        /// </summary>
        public static bool HasItem(int id, AIHeroClient hero = null)
        {
            return HasItem((ItemId) id, hero);
        }

        /// <summary>
        /// Returns true if the hero has the item.
        /// </summary>
        public static bool HasItem(ItemId id, AIHeroClient hero = null)
        {
            return (hero ?? Player.Instance).InventoryItems.Any(slot => slot.Id == id);
        }

        /// <summary>
        /// Retruns true if the player has the item and its not on cooldown.
        /// </summary>
        public static bool CanUseItem(string name)
        {
            return
                Player.Instance.InventoryItems.Where(slot => slot.Name == name)
                    .Select(slot => Player.Instance.Spellbook.Spells.FirstOrDefault(spell => (int) spell.Slot == slot.Slot + (int) SpellSlot.Item1))
                    .Select(inst => inst != null && inst.State == SpellState.Ready)
                    .FirstOrDefault();
        }

        /// <summary>
        /// Retruns true if the player has the item and its not on cooldown.
        /// </summary>
        public static bool CanUseItem(int id)
        {
            return CanUseItem((ItemId) id);
        }

        /// <summary>
        /// Retruns true if the player has the item and its not on cooldown.
        /// </summary>
        public static bool CanUseItem(ItemId id)
        {
            return
                Player.Instance.InventoryItems.Where(slot => slot.Id == id)
                    .Select(slot => Player.Instance.Spellbook.Spells.FirstOrDefault(spell => (int) spell.Slot == slot.Slot + (int) SpellSlot.Item1))
                    .Select(inst => inst != null && inst.State == SpellState.Ready)
                    .FirstOrDefault();
        }

        /// <summary>
        /// Casts the item on the target.
        /// </summary>
        public static bool UseItem(string name, Obj_AI_Base target = null)
        {
            return
                Player.Instance.InventoryItems.Where(slot => slot.Name == name)
                    .Select(slot => target != null ? Player.Instance.Spellbook.CastSpell(slot.SpellSlot, target) : Player.CastSpell(slot.SpellSlot))
                    .FirstOrDefault();
        }

        /// <summary>
        /// Casts the item on the target.
        /// </summary>
        public static bool UseItem(int id, Obj_AI_Base target = null)
        {
            return UseItem((ItemId) id, target);
        }

        /// <summary>
        /// Casts the item on a Vector2 position.
        /// </summary>
        public static bool UseItem(int id, Vector2 position)
        {
            return UseItem(id, position.To3D());
        }

        /// <summary>
        /// Casts the item on a Vector3 position.
        /// </summary>
        public static bool UseItem(int id, Vector3 position)
        {
            return UseItem((ItemId) id, position);
        }

        /// <summary>
        /// Casts the item on the target.
        /// </summary>
        public static bool UseItem(ItemId id, Obj_AI_Base target = null)
        {
            return
                Player.Instance.InventoryItems.Where(slot => slot.Id == id)
                    .Select(slot => target != null ? Player.CastSpell(slot.SpellSlot, target) : Player.CastSpell(slot.SpellSlot))
                    .FirstOrDefault();
        }

        /// <summary>
        /// Casts the item on a Vector2 position.
        /// </summary>
        public static bool UseItem(ItemId id, Vector2 position)
        {
            return UseItem(id, position.To3D());
        }

        /// <summary>
        /// Casts the item on a Vector3 position.
        /// </summary>
        public static bool UseItem(ItemId id, Vector3 position)
        {
            return !position.IsZero && Player.Instance.InventoryItems.Where(slot => slot.Id == id).Select(slot => Player.CastSpell(slot.SpellSlot, position)).FirstOrDefault();
        }
    }

    public sealed class ItemInfo
    {
        private ItemInfo()
        {
        }

        public string Name { get; private set; }
        public ItemImage Image { get; private set; }
        public ItemGold Gold { get; private set; }
        public string Group { get; private set; }
        public string Description { get; private set; }
        public string Plaintext { get; private set; }
        public bool Consumed { get; private set; }
        private float? stacks { get; set; }

        public float Stacks
        {
            get { return stacks ?? 1; }
        }

        private int? depth { get; set; }

        public int Depth
        {
            get { return depth ?? 1; }
        }

        public bool ConsumeOnFull { get; private set; }
        public int[] From { get; private set; }
        public int[] Into { get; private set; }
        public float SpecialRecipe { get; private set; }
        private bool? inStore { get; set; }

        public bool InStore
        {
            get { return inStore ?? true; }
        }

        public bool HideFromAll { get; private set; }
        public string RequiredChampion { get; private set; }
        public ItemStats Stats { get; private set; }
        public string[] Tags { get; private set; }
        public Dictionary<int, bool> Maps { get; private set; }

        // Custom properties

        public ItemId[] FromId
        {
            get { return From != null ? From.Select(i => (ItemId) i).ToArray() : new ItemId[] { }; }
        }

        public ItemId[] IntoId
        {
            get { return Into != null ? Into.Select(i => (ItemId) i).ToArray() : new ItemId[] { }; }
        }

        public bool ValidForPlayer
        {
            get { return string.IsNullOrEmpty(RequiredChampion) || Player.Instance.ChampionName == RequiredChampion; }
        }

        public bool AvailableForMap
        {
            get { return Maps.ContainsKey((int) Game.MapId) && Maps[(int) Game.MapId]; }
        }
    }

    public sealed class ItemGold
    {
        private ItemGold()
        {
        }

        public int Base { get; private set; }
        public int Total { get; private set; }
        public int Sell { get; private set; }
        public bool Purchasable { get; private set; }
    }

    public sealed class ItemStats
    {
        private ItemStats()
        {
        }

        public float FlatHPPoolMod { get; private set; }
        public float rFlatHPModPerLevel { get; private set; }
        public float FlatMPPoolMod { get; private set; }
        public float rFlatMPModPerLevel { get; private set; }
        public float PercentHPPoolMod { get; private set; }
        public float PercentMPPoolMod { get; private set; }
        public float FlatHPRegenMod { get; private set; }
        public float rFlatHPRegenModPerLevel { get; private set; }
        public float PercentHPRegenMod { get; private set; }
        public float FlatMPRegenMod { get; private set; }
        public float rFlatMPRegenModPerLevel { get; private set; }
        public float PercentMPRegenMod { get; private set; }
        public float FlatArmorMod { get; private set; }
        public float rFlatArmorModPerLevel { get; private set; }
        public float PercentArmorMod { get; private set; }
        public float rFlatArmorPenetrationMod { get; private set; }
        public float rFlatArmorPenetrationModPerLevel { get; private set; }
        public float rPercentArmorPenetrationMod { get; private set; }
        public float rPercentArmorPenetrationModPerLevel { get; private set; }
        public float FlatPhysicalDamageMod { get; private set; }
        public float rFlatPhysicalDamageModPerLevel { get; private set; }
        public float PercentPhysicalDamageMod { get; private set; }
        public float FlatMagicDamageMod { get; private set; }
        public float rFlatMagicDamageModPerLevel { get; private set; }
        public float PercentMagicDamageMod { get; private set; }
        public float FlatMovementSpeedMod { get; private set; }
        public float rFlatMovementSpeedModPerLevel { get; private set; }
        public float PercentMovementSpeedMod { get; private set; }
        public float rPercentMovementSpeedModPerLevel { get; private set; }
        public float FlatAttackSpeedMod { get; private set; }
        public float PercentAttackSpeedMod { get; private set; }
        public float rPercentAttackSpeedModPerLevel { get; private set; }
        public float rFlatDodgeMod { get; private set; }
        public float rFlatDodgeModPerLevel { get; private set; }
        public float PercentDodgeMod { get; private set; }
        public float FlatCritChanceMod { get; private set; }
        public float rFlatCritChanceModPerLevel { get; private set; }
        public float PercentCritChanceMod { get; private set; }
        public float FlatCritDamageMod { get; private set; }
        public float rFlatCritDamageModPerLevel { get; private set; }
        public float PercentCritDamageMod { get; private set; }
        public float FlatBlockMod { get; private set; }
        public float PercentBlockMod { get; private set; }
        public float FlatSpellBlockMod { get; private set; }
        public float rFlatSpellBlockModPerLevel { get; private set; }
        public float PercentSpellBlockMod { get; private set; }
        public float FlatEXPBonus { get; private set; }
        public float PercentEXPBonus { get; private set; }
        public float rPercentCooldownMod { get; private set; }
        public float rPercentCooldownModPerLevel { get; private set; }
        public float rFlatTimeDeadMod { get; private set; }
        public float rFlatTimeDeadModPerLevel { get; private set; }
        public float rPercentTimeDeadMod { get; private set; }
        public float rPercentTimeDeadModPerLevel { get; private set; }
        public float rFlatGoldPer10Mod { get; private set; }
        public float rFlatMagicPenetrationMod { get; private set; }
        public float rFlatMagicPenetrationModPerLevel { get; private set; }
        public float rPercentMagicPenetrationMod { get; private set; }
        public float rPercentMagicPenetrationModPerLevel { get; private set; }
        public float FlatEnergyRegenMod { get; private set; }
        public float rFlatEnergyRegenModPerLevel { get; private set; }
        public float FlatEnergyPoolMod { get; private set; }
        public float rFlatEnergyModPerLevel { get; private set; }
        public float PercentLifeStealMod { get; private set; }
        public float PercentSpellVampMod { get; private set; }
    }

    public sealed class ItemImage
    {
        private ItemImage()
        {
        }

        public string Full { get; private set; }
        public string Sprite { get; private set; }
        public string Group { get; private set; }
        public int X { get; private set; }
        public int Y { get; private set; }
        public int W { get; private set; }
        public int H { get; private set; }
    }
}
