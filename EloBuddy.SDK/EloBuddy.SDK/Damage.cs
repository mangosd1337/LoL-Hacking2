using System;
using System.Collections.Generic;
using System.Data;
using System.Linq;
using System.Reflection;
using EloBuddy.SDK.Menu.Values;

namespace EloBuddy.SDK
{
    internal class PrecalculatedAutoAttackDamage
    {
        internal float _rawTotal;
        internal float _rawPhysical;
        internal float _rawMagical;
        internal float _calculatedPhysical;
        internal float _calculatedMagical;
        internal float _calculatedTrue;
        internal DamageType _autoAttackDamageType = DamageType.Physical;
    }

    public static class Damage
    {
        private const bool Broken = true;

        internal static bool IsSiegeMinion(this Obj_AI_Base minion)
        {
            return minion.BaseSkinName.Contains("Siege");
        }

        internal static bool IsLaneTurret(this Obj_AI_Base turret)
        {
            return Constants.ObjectNames.LaneTurrets.Contains(turret.BaseSkinName);
        }

        #region Masteries

        internal static bool HasDoubleEdgedSword(this AIHeroClient hero)
        {
            return hero.IsMe && Orbwalker.FarmingMenu["doubleEdgedSword"].Cast<CheckBox>().CurrentValue;
        }

        internal static bool HasAssassin(this AIHeroClient hero)
        {
            return hero.IsMe && Orbwalker.FarmingMenu["assassin"].Cast<CheckBox>().CurrentValue;
        }

        internal static int GetSavageryCount(this AIHeroClient hero)
        {
            return hero.IsMe ? Orbwalker.FarmingMenu["savagery"].Cast<Slider>().CurrentValue : 0;
        }

        internal static int GetMercilessCount(this AIHeroClient hero)
        {
            return hero.IsMe ? Orbwalker.FarmingMenu["merciless"].Cast<Slider>().CurrentValue : 0;
        }

        #endregion

        #region AutoAttackDamage

        internal static PrecalculatedAutoAttackDamage GetStaticAutoAttackDamage(this AIHeroClient fromHero, bool targetIsMinion)
        {
            var precalculated = new PrecalculatedAutoAttackDamage { _rawTotal = fromHero.TotalAttackDamage };
            var fromHeroLevel = Math.Min(fromHero.Level, 18);

            #region Offensive Item Passives

            // Nashor's Tooth: +15 (+ 15% AP) on-hit magic damage
            if (fromHero.HasItem(ItemId.Nashors_Tooth))
            {
                precalculated._rawMagical += 15f + 0.15f * fromHero.FlatMagicDamageMod;
            }

            // Recurve Bow Unique Passive: Deal 15 bonus on-hit physical damage.
            // Runaan's Hurricane Unique Passive: Deal 15 bonus on-hit physical damage.
            if (fromHero.HasItem(ItemId.Runaans_Hurricane_Ranged_Only, ItemId.Recurve_Bow))
            {
                precalculated._rawPhysical += 15f;
            }

            //Wit's End Unique Passive: Basic attacks deal 40 bonus magic damage on-hit.
            if (fromHero.HasItem(ItemId.Wits_End))
            {
                precalculated._rawMagical += 40f;
            }

            //Devourer Unique Passive - Devouring: Basic attacks deal 30 + (Devourer stacks) on-hit magic damage. 
            if (fromHero.HasItem(
                ItemId.Skirmishers_Sabre_Enchantment_Devourer,
                ItemId.Skirmishers_Sabre_Enchantment_Sated_Devourer,
                ItemId.Stalkers_Blade_Enchantment_Devourer,
                ItemId.Stalkers_Blade_Enchantment_Sated_Devourer,
                ItemId.Trackers_Knife_Enchantment_Devourer,
                ItemId.Trackers_Knife_Enchantment_Sated_Devourer))
            {
                precalculated._rawMagical += 30f + Math.Max(fromHero.GetBuffCount("enchantment_slayer_stacks"), 0);
            }

            //Sheen: Unique Passive - Spellblade: After using an ability, your next basic attack deals 100% base AD bonus physical damage
            if (fromHero.HasItem(ItemId.Sheen) && fromHero.HasBuff("sheen"))
            {
                precalculated._rawPhysical += 1f * fromHero.TotalAttackDamage;
            }

            //Lich Bane: Unique Passive – Spellblade: After using an ability, your next basic attack deals 75% base AD (+ 50% AP) bonus magic damage
            if (fromHero.HasItem(ItemId.Lich_Bane) && fromHero.HasBuff("lichbane"))
            {
                precalculated._rawMagical += 0.75f * fromHero.TotalAttackDamage + 0.5f * fromHero.FlatMagicDamageMod;
            }

            //Trinity Force: Unique Passive - Spellblade: After using an ability, your next basic attack deals 200% base AD bonus physical damage
            if (fromHero.HasItem(ItemId.Trinity_Force) && fromHero.HasBuff("sheen"))
            {
                precalculated._rawPhysical += 2f * fromHero.TotalAttackDamage;
            }

            if (fromHero.GetBuffCount("itemstatikshankcharge") == 100)
            {
                var statikk = 0f;
                var kircheis = 0f;
                var rapid = 0f;
                //Statikk Shiv: UNIQUE – SHIV LIGHTNING: Your next basic attack (on-hit) sparks lightning to up to 4 nearby units, dealing 50 - 120 (based on level) bonus magic damage, increased to 110 -  (based on level) against minions.
                if (fromHero.HasItem(ItemId.Statikk_Shiv))
                {
                    statikk += (targetIsMinion ? 2.2f : 1f) * new[] { 50, 50, 50, 50, 50, 56, 61, 67, 72, 77, 83, 88, 94, 99, 104, 110, 115, 120 }[fromHeroLevel - 1];
                }

                //Kircheis Shard: ENERGIZED STRIKE: Your next basic attack deals +30 bonus on-hit magic damage.
                if (fromHero.HasItem(ItemId.Kircheis_Shard))
                {
                    kircheis += 40f;
                }

                //Rapid Firecannon: UNIQUE – ENERGIZED STRIKE: Moving and attacking generates Energize stacks, up to 100. When fully Energized, your next basic attack gains FIRECANNON and deals 50 - 160 (based on level) bonus on-hit magic damage.
                if (fromHero.HasItem(ItemId.Rapid_Firecannon))
                {
                    rapid += new[] { 50, 50, 50, 50, 50, 58, 66, 75, 83, 92, 100, 109, 117, 126, 134, 143, 151, 160 }[fromHeroLevel - 1];
                }
                precalculated._rawMagical = new[] { statikk, kircheis, rapid }.Max();
            }

            //Guinsoo's Rageblade:  Basic attacks Deal an additional 15 magic damage on-hit.
            if (fromHero.HasItem(ItemId.Guinsoos_Rageblade))
            {
                precalculated._calculatedMagical += 15f;
            }

            if (fromHero.IsMelee)
            {
                if (fromHero.HasItem(3742))
                {
                    var count = fromHero.GetBuffCount("DreadnoughtMomentumBuff");
                    if (count > 0)
                    {
                        precalculated._rawPhysical += (count == 100 ? 2 : 1) * count / 2f;
                    }
                }
                if (fromHero.HasItem(3748))
                {
                    precalculated._rawPhysical += fromHero.HasBuff("itemtitanichydracleavebuff") ? (40f + 0.1f * fromHero.MaxHealth) : (5f + 0.01f * fromHero.MaxHealth);
                }
            }

            #endregion

            #region Offensive Spell Passives

            switch (fromHero.Hero)
            {
                case Champion.Aatrox:
                    if (fromHero.HasBuff("AatroxWONHPowerBuff"))
                    {
                        precalculated._rawPhysical += 35f * fromHero.Spellbook.GetSpell(SpellSlot.Q).Level + 25f +
                                                      fromHero.FlatPhysicalDamageMod;
                    }
                    break;
                case Champion.Alistar:
                    if (fromHero.HasBuff("alistartrample"))
                    {
                        precalculated._rawMagical += (targetIsMinion ? 2f : 1f) *
                                                     (6f + fromHero.Level + 0.1f * fromHero.FlatMagicDamageMod);
                    }
                    break;
                case Champion.Ashe:
                    if (fromHero.HasBuff("asheqbuff"))
                    {
                        precalculated._rawPhysical += (110f + 5f * fromHero.Spellbook.GetSpell(SpellSlot.Q).Level) / 100f *
                                                      fromHero.TotalAttackDamage;
                    }
                    break;
                case Champion.Blitzcrank:
                    if (fromHero.HasBuff("PowerFist"))
                    {
                        precalculated._rawPhysical += fromHero.TotalAttackDamage;
                    }
                    break;
                case Champion.Caitlyn:
                    if (fromHero.HasBuff("caitlynheadshot"))
                    {
                        if (targetIsMinion)
                        {
                            precalculated._rawTotal *= 2.5f;
                        }
                        else
                        {
                            precalculated._rawPhysical += fromHero.TotalAttackDamage *
                                                          (0.5f +
                                                           (fromHero.FlatCritChanceMod * (1f + fromHero.FlatCritDamageMod)));
                        }
                    }
                    break;
                case Champion.Chogath:
                    if (fromHero.HasBuff("VorpalSpikes"))
                    {
                        precalculated._rawMagical += 5f + 15f * fromHero.Spellbook.GetSpell(SpellSlot.E).Level +
                                                     0.3f * fromHero.FlatMagicDamageMod;
                    }
                    break;
                case Champion.Corki:
                    precalculated._rawTotal *= 0.5f;
                    precalculated._rawMagical = precalculated._rawTotal;
                    break;
                case Champion.Darius:
                    //TODO
                    break;
                case Champion.Diana:
                    if (fromHero.GetBuffCount("dianapassivemarker") == 2)
                    {
                        precalculated._rawMagical +=
                            new[] { 20, 25, 30, 35, 40, 50, 60, 70, 80, 90, 105, 120, 135, 155, 175, 200, 225, 250 }[
                                fromHeroLevel - 1] + 0.8f * fromHero.FlatMagicDamageMod;
                    }
                    break;
                case Champion.Draven:
                    if (fromHero.HasBuff("dravenspinningattack"))
                    {
                        precalculated._rawPhysical += (35 + 10 * fromHero.Spellbook.GetSpell(SpellSlot.Q).Level) *
                                                      fromHero.TotalAttackDamage / 100f;
                    }
                    break;
                case Champion.Fiora:
                    //TODO
                    break;
                case Champion.Fizz:
                    if (fromHero.HasBuff("FizzSeastonePassive"))
                    {
                        precalculated._rawMagical += 5f + 5f * fromHero.Spellbook.GetSpell(SpellSlot.W).Level +
                                                     0.3f * fromHero.FlatMagicDamageMod;
                    }
                    break;
                case Champion.Garen:
                    if (fromHero.HasBuff("GarenQ"))
                    {
                        precalculated._rawPhysical += 5f + 25f * fromHero.Spellbook.GetSpell(SpellSlot.Q).Level +
                                                      0.4f * fromHero.TotalAttackDamage;
                    }
                    break;
                case Champion.Graves:
                    //Enemies hit take 70% - 100% AD physical damage plus 「 23 - 33% AD 」 damage for every pellet that hit them beyond the first.
                    precalculated._rawTotal *=
                        new[] { 70, 71, 72, 74, 75, 76, 78, 80, 81, 83, 85, 87, 89, 91, 95, 96, 97, 100 }[
                            fromHeroLevel - 1] / 100f;
                    break;
                case Champion.Hecarim:
                    //TODO
                    break;
                case Champion.Irelia:
                    if (fromHero.HasBuff("ireliahitenstylecharged"))
                    {
                        precalculated._calculatedTrue += 15f * fromHero.Spellbook.GetSpell(SpellSlot.W).Level;
                    }
                    break;
                case Champion.Jax:
                    if (fromHero.HasBuff("JaxEmpowerTwo"))
                    {
                        precalculated._rawMagical += 5f + 35f * fromHero.Spellbook.GetSpell(SpellSlot.W).Level +
                                                     0.6f * fromHero.FlatMagicDamageMod;
                    }

                    break;
                case Champion.Jayce:
                    if (fromHero.HasBuff("jaycepassivemeleeattack"))
                    {
                        precalculated._rawMagical += -20f + 40f * fromHero.Spellbook.GetSpell(SpellSlot.R).Level +
                                                     0.4f * fromHero.FlatMagicDamageMod;
                    }
                    break;
                case Champion.Jinx:
                    if (fromHero.Spellbook.GetSpell(SpellSlot.Q).ToggleState == 2)
                    {
                        precalculated._rawTotal *= 1.1f;
                    }
                    break;
                case Champion.Kalista:
                    precalculated._rawTotal *= 0.9f;
                    break;
                case Champion.Kassadin:
                    if (fromHero.Spellbook.GetSpell(SpellSlot.W).Level > 0)
                    {
                        precalculated._rawMagical += 20f + 0.1f * fromHero.FlatMagicDamageMod;
                    }
                    if (fromHero.HasBuff("NetherBladeArmorPen"))
                    {
                        precalculated._rawMagical += -5f + 25f * fromHero.Spellbook.GetSpell(SpellSlot.W).Level +
                                                     0.6f * fromHero.FlatMagicDamageMod;
                    }
                    break;
                case Champion.Kayle:
                    if (fromHero.Spellbook.GetSpell(SpellSlot.E).Level > 0)
                    {
                        precalculated._rawMagical += 5f + 5f * fromHero.Spellbook.GetSpell(SpellSlot.E).Level +
                                                     0.15f * fromHero.FlatMagicDamageMod;
                        if (fromHero.HasBuff("JudicatorRighteousFury"))
                        {
                            precalculated._rawMagical += 5f + 5f * fromHero.Spellbook.GetSpell(SpellSlot.E).Level +
                                                         0.15f * fromHero.FlatMagicDamageMod;
                        }
                    }

                    break;
                case Champion.Kennen:
                    if (fromHero.HasBuff("kennendoublestrikelive"))
                    {
                        precalculated._rawMagical += (0.3f + 0.1f * fromHero.Spellbook.GetSpell(SpellSlot.W).Level) *
                                                     fromHero.TotalAttackDamage;
                    }
                    break;
                case Champion.Khazix:
                    //TODO
                    break;
                case Champion.Leona:
                    if (fromHero.HasBuff("LeonaShieldOfDaybreak"))
                    {
                        precalculated._rawMagical += 10f + 30f * fromHero.Spellbook.GetSpell(SpellSlot.Q).Level +
                                                     0.3f * fromHero.FlatMagicDamageMod;
                    }
                    break;
                case Champion.Lucian:
                    if (fromHero.HasBuff("lucianpassivebuff"))
                    {
                        if (targetIsMinion)
                        {
                            precalculated._rawTotal *= 2f;
                        }
                        else
                        {
                            precalculated._rawTotal += (0.3f + 0.1f * (int) Math.Truncate((fromHero.Level - 1f) / 6f)) *
                                                       fromHero.TotalAttackDamage;
                        }
                    }
                    break;
                case Champion.Lulu:
                    precalculated._rawMagical += -1f + 4f * ((fromHero.Level + 1f) / 2f) + 0.05f * fromHero.FlatMagicDamageMod;
                    break;
                case Champion.Malphite:
                    //TODO
                    break;
                case Champion.MasterYi:
                    if (fromHero.HasBuff("doublestrike"))
                    {
                        precalculated._rawTotal *= 1.5f;
                    }
                    if (fromHero.HasBuff("wujustylesuperchargedvisual"))
                    {
                        precalculated._calculatedTrue += 9f * fromHero.Spellbook.GetSpell(SpellSlot.E).Level + 5f +
                                                         0.25f * fromHero.FlatPhysicalDamageMod;
                    }
                    break;
                case Champion.MissFortune:
                    //TODO
                    // Miss Fortune's basic attacks deal 25 - 50% AD bonus physical damage, doubled to 50 - 100% AD against champions and monsters, and mark them. Marked targets are immune to Love Tap, but Miss Fortune can only mark a target once and transfers the mark to whichever target she hits with a basic attack.
                    break;
                case Champion.Mordekaiser:
                    //TODO
                    break;
                case Champion.Nami:
                    //TODO
                    break;
                case Champion.Nasus:
                    if (fromHero.HasBuff("NasusQ"))
                    {
                        precalculated._rawPhysical += Math.Max(fromHero.GetBuffCount("nasusqstacks"), 0f) + 10f +
                                                      20f * fromHero.Spellbook.GetSpell(SpellSlot.Q).Level;
                    }
                    break;
                case Champion.Nautilus:
                    //TODO
                    break;
                case Champion.Nidalee:
                    //TODO
                    break;
                case Champion.Nocturne:
                    //TODO
                    break;
                case Champion.Nunu:
                    //TODO
                    break;
                case Champion.Pantheon:
                    //TODO
                    break;
                case Champion.Poppy:
                    break;
                case Champion.RekSai:
                    //TODO
                    break;
                case Champion.Rengar:
                    if (fromHero.HasBuff("rengarqbase"))
                    {
                        precalculated._rawPhysical += 30f * fromHero.Spellbook.GetSpell(SpellSlot.Q).Level +
                                                      0.05f * (fromHero.Spellbook.GetSpell(SpellSlot.Q).Level - 1) *
                                                      fromHero.TotalAttackDamage;
                    }
                    if (fromHero.HasBuff("rengarqemp"))
                    {
                        precalculated._rawPhysical +=
                            new[]
                            {
                                30, 45, 60, 75, 90, 105, 120, 135, 150, 160, 170, 180, 190, 200, 210, 220, 230,
                                240
                            }[fromHeroLevel - 1] + 0.5f * fromHero.TotalAttackDamage;
                    }
                    break;
                case Champion.Riven:
                    if (fromHero.GetBuffCount("rivenpassiveaaboost") > 0)
                    {
                        precalculated._rawPhysical += (20f + 5f * (int) Math.Truncate((fromHero.Level + 2f) / 3f)) *
                                                      fromHero.TotalAttackDamage / 100f;
                    }
                    break;
                case Champion.Rumble:
                    //TODO
                    break;
                case Champion.Sejuani:
                    //TODO
                    break;
                case Champion.Shaco:
                    //TODO
                    break;
                case Champion.Shen:
                    //TODO
                    break;
                case Champion.Shyvana:
                    if (fromHero.HasBuff("ShyvanaDoubleAttack"))
                    {
                        precalculated._rawPhysical += (0.75f + 0.05f * fromHero.Spellbook.GetSpell(SpellSlot.Q).Level) *
                                                      fromHero.TotalAttackDamage;
                    }
                    break;
                case Champion.Sion:
                    //TODO
                    break;
                case Champion.Sona:
                    if (fromHero.HasBuff("sonapassiveattack"))
                    {
                        precalculated._rawMagical +=
                            new[] { 13, 20, 27, 35, 43, 52, 62, 72, 82, 92, 102, 112, 122, 132, 147, 162, 177, 192 }[
                                fromHeroLevel - 1] + 0.2f * fromHero.FlatMagicDamageMod;
                    }
                    break;
                case Champion.TahmKench:
                    //TODO
                    break;
                case Champion.Talon:
                    //TODO
                    break;
                case Champion.Taric:
                    //TODO
                    break;
                case Champion.Teemo:
                    if (fromHero.HasBuff("ToxicShot"))
                    {
                        precalculated._rawMagical += 0.3f * fromHero.FlatMagicDamageMod +
                                                     10f * fromHero.Spellbook.GetSpell(SpellSlot.E).Level;
                    }
                    break;
                case Champion.Thresh:
                    if (fromHero.Spellbook.GetSpell(SpellSlot.E).Level > 0)
                    {
                        var v = Math.Max(fromHero.GetBuffCount("threshpassivesouls"), 0f);
                        v += (0.5f + 0.3f * fromHero.Spellbook.GetSpell(SpellSlot.E).Level) *
                             fromHero.TotalAttackDamage;
                        if (fromHero.HasBuff("threshqpassive4"))
                        {
                            v /= 1f;
                        }
                        else if (fromHero.HasBuff("threshqpassive3"))
                        {
                            v /= 2f;
                        }
                        else if (fromHero.HasBuff("threshqpassive2"))
                        {
                            v /= 3f;
                        }
                        else
                        {
                            v /= 4f;
                        }
                        precalculated._rawMagical += v;
                    }
                    break;
                case Champion.Trundle:
                    //TODO
                    break;
                case Champion.TwistedFate:
                    if (fromHero.HasBuff("CardMasterStackParticle"))
                    {
                        precalculated._rawMagical += 25f * fromHero.Spellbook.GetSpell(SpellSlot.E).Level + 30 +
                                                     0.5f * fromHero.FlatMagicDamageMod;
                    }
                    if (fromHero.HasBuff("BlueCardPreAttack"))
                    {
                        precalculated._autoAttackDamageType = DamageType.Magical;
                        precalculated._rawTotal += 20f * fromHero.Spellbook.GetSpell(SpellSlot.W).Level + 20f +
                                                   0.5f * fromHero.FlatMagicDamageMod;
                    }
                    else if (fromHero.HasBuff("RedCardPreAttack"))
                    {
                        precalculated._autoAttackDamageType = DamageType.Magical;
                        precalculated._rawTotal += 15f * fromHero.Spellbook.GetSpell(SpellSlot.W).Level + 15f +
                                                   0.5f * fromHero.FlatMagicDamageMod;
                    }
                    else if (fromHero.HasBuff("GoldCardPreAttack"))
                    {
                        precalculated._autoAttackDamageType = DamageType.Magical;
                        precalculated._rawTotal += 7.5f * fromHero.Spellbook.GetSpell(SpellSlot.W).Level + 7.5f +
                                                   0.5f * fromHero.FlatMagicDamageMod;
                    }
                    break;
                case Champion.Udyr:
                    //TODO
                    break;
                case Champion.Varus:
                    if (fromHero.Spellbook.GetSpell(SpellSlot.W).Level > 0)
                    {
                        precalculated._rawMagical += 6f + 4f * fromHero.Spellbook.GetSpell(SpellSlot.W).Level +
                                                     0.25f * fromHero.FlatMagicDamageMod;
                    }
                    break;
                case Champion.Vayne:
                    if (fromHero.HasBuff("vaynetumblebonus"))
                    {
                        precalculated._rawPhysical += (0.2f + 0.1f * fromHero.Spellbook.GetSpell(SpellSlot.Q).Level) *
                                                      fromHero.TotalAttackDamage;
                    }
                    break;
                case Champion.Vi:
                    //TODO
                    break;
                case Champion.Viktor:
                    if (fromHero.HasBuff("viktorpowertransferreturn"))
                    {
                        precalculated._autoAttackDamageType = DamageType.Magical;
                        precalculated._rawTotal +=
                            (new[] { 20, 25, 30, 35, 40, 45, 50, 55, 60, 70, 80, 90, 110, 130, 150, 170, 190, 210 })[
                                fromHeroLevel - 1] + 0.5f * fromHero.FlatMagicDamageMod;
                    }
                    break;
                case Champion.Volibear:
                    //TODO
                    break;
                case Champion.Warwick:
                    //TODO
                    break;
                case Champion.MonkeyKing:
                    //TODO
                    break;
                case Champion.XinZhao:
                    if (fromHero.HasBuff("XenZhaoComboTarget"))
                    {
                        precalculated._rawPhysical += 15f * fromHero.Spellbook.GetSpell(SpellSlot.Q).Level +
                                                      0.2f * fromHero.TotalAttackDamage;
                    }
                    break;
                case Champion.Yorick:
                    //TODO
                    break;
                case Champion.Ziggs:
                    if (fromHero.HasBuff("ziggsshortfuse"))
                    {
                        precalculated._rawMagical += 1f *
                                                     new[]
                                                     {
                                                         20, 24, 28, 32, 36, 40, 48, 56, 64, 72, 80, 88, 100, 112, 124,
                                                         136,
                                                         148, 160
                                                     }[fromHeroLevel - 1] +
                                                     fromHero.FlatMagicDamageMod *
                                                     new[]
                                                     {
                                                         25, 25, 25, 25, 25, 25, 30, 30, 30, 30, 30, 30, 35, 35, 35, 35,
                                                         35,
                                                         35
                                                     }[fromHeroLevel - 1] / 100f;
                    }
                    break;
            }

            #endregion

            return precalculated;
        }

        internal static float GetAutoAttackDamage(this AIHeroClient fromHero, Obj_AI_Base target, PrecalculatedAutoAttackDamage precalculated)
        {
            var targetIsMinion = target.Type == GameObjectType.obj_AI_Minion;
            var calculatedPhysicalDamage = precalculated._calculatedPhysical;
            var calculatedMagicalDamage = precalculated._calculatedMagical;
            var calculatedTrueDamage = precalculated._calculatedTrue;
            var rawPhysicalDamage = precalculated._rawPhysical;
            var rawMagicalDamage = precalculated._rawMagical;
            var rawTotal = precalculated._rawTotal;
            var guaranteedCriticalStrike = false;
            //Special minions like wards, kalista's w, etc..
            if (targetIsMinion && target.MaxHealth >= 0 && target.MaxHealth <= 6)
            {
                return 1f;
            }

            #region Offensive Item Passives

            //  Spoils of War
            //  Basic melee attacks deal 200/240/400 bonus true damage to minions with less than 200/240/400 health.
            //  Whenever a minion is killed by Spoils of War, the wielder and the nearest allied champion are healed,
            //  and the ally gains gold equal to the kill. Recharges every 30-60 seconds, up to a maximum of 2-4 charges.
            //  Requires a nearby allied champion.
            if (fromHero.IsMelee && target.IsEnemy && !target.Team.IsNeutral() &&
                targetIsMinion &&
                fromHero.GetBuffCount("talentreaperdisplay") > 0 &&
                EntityManager.Heroes.AllHeroes.Any(h => h.IsValidTarget() && h.Team == fromHero.Team && !h.IdEquals(fromHero) && h.IsInRange(fromHero, 1050)))
            {
                //var damage = fromHero.GetAutoAttackDamage(target);
                var relicShieldDictionary = new Dictionary<ItemId, float>
                {
                    { ItemId.Relic_Shield, 195f + 5f * fromHero.Level },
                    { ItemId.Targons_Brace, 200f + 10f * fromHero.Level },
                    { ItemId.Face_of_the_Mountain, 320f + 20f * fromHero.Level },
                    { ItemId.Eye_of_the_Equinox, 320f + 20f * fromHero.Level }
                };
                foreach (var relicDamage in relicShieldDictionary.Where(pair => fromHero.HasItem(pair.Key)).Select(pair => pair.Value).Where(relicDamage => target.Health <= relicDamage))
                {
                    return relicDamage;
                }
            }

            // Blade of the Ruined King
            // Basic attacks deal 6% of the target's current Health in bonus physical damage (max 60 vs. monsters and minions) on hit.
            if (fromHero.HasItem(ItemId.Blade_of_the_Ruined_King))
            {
                var itemDamage = 0.06f * target.Health;
                if (targetIsMinion)
                {
                    itemDamage = Math.Min(itemDamage, 60f);
                }
                rawPhysicalDamage += Math.Max(itemDamage, 10f);
            }

            //Hunter's Machete: UNIQUE PASSIVE Nail: Basic attacks deal 20 bonus damage on-hit vs monsters. Killing large monsters grants 15 bonus experience.
            if (fromHero.HasItem(ItemId.Hunters_Machete) && target.IsMonster)
            {
                calculatedTrueDamage += 20f;
            }

            #endregion

            #region Offensive Spell Passives

            int count;
            switch (fromHero.Hero)
            {
                case Champion.Akali:
                    if (target.HasBuff("AkaliMota"))
                    {
                        rawMagicalDamage += 25f * fromHero.Spellbook.GetSpell(SpellSlot.Q).Level + 20f +
                                            0.5f * fromHero.FlatMagicDamageMod;
                    }
                    rawMagicalDamage += (0.06f + (Math.Abs(fromHero.FlatMagicDamageMod / 6) * 0.01f)) *
                                        fromHero.TotalAttackDamage;
                    break;
                case Champion.Ashe:
                    /*
                    if (target.HasBuff("ashepassiveslow"))
                    {
                        rawPhysicalDamage += fromHero.TotalAttackDamage *
                                             (0.1f +
                                              (fromHero.FlatCritChanceMod * (1f + fromHero.FlatCritDamageMod)));
                        //TODO
                    }
                    */
                    break;
                case Champion.Braum:
                    if (target.GetBuffCount("braummarkcounter") == 3)
                    {
                        rawMagicalDamage += 16f + 10f * fromHero.Level;
                    }
                    if (target.HasBuff("braummarkstunreduction"))
                    {
                        rawMagicalDamage += 6.4f + 1.6f * fromHero.Level;
                    }
                    break;
                case Champion.Ekko:
                    if (target.GetBuffCount("EkkoStacks") == 3)
                    {
                        rawMagicalDamage += 10f + 10f * fromHero.Level + 0.8f * fromHero.FlatMagicDamageMod;
                    }
                    break;
                case Champion.Gnar:
                    if (target.GetBuffCount("gnarwproc") == 2)
                    {
                        precalculated._autoAttackDamageType = DamageType.Magical;
                        rawTotal += 10f * fromHero.Spellbook.GetSpell(SpellSlot.W).Level +
                                    Math.Min(
                                        target.MaxHealth *
                                        (4f + 2f * fromHero.Spellbook.GetSpell(SpellSlot.W).Level) / 100f,
                                        50f * fromHero.Spellbook.GetSpell(SpellSlot.W).Level + 50f);
                    }
                    break;
                case Champion.Gragas:
                    if (fromHero.HasBuff("gragaswattackbuff"))
                    {
                        rawMagicalDamage += -10f + 30f * fromHero.Spellbook.GetSpell(SpellSlot.W).Level +
                                            0.3f * fromHero.FlatMagicDamageMod + 0.08f * target.MaxHealth;
                    }
                    break;
                case Champion.JarvanIV:
                    if (!target.HasBuff("jarvanivmartialcadencecheck"))
                    {
                        rawPhysicalDamage += Math.Min(400f, 0.1f * target.Health);
                    }
                    break;
                case Champion.Jhin:
                    if (fromHero.HasBuff("jhinpassiveattackbuff"))
                    {
                        rawPhysicalDamage += (target.MaxHealth - target.Health) * ((int) Math.Truncate(fromHero.Level / 6f) * 5f + 10f) / 100f;
                        guaranteedCriticalStrike = true;
                    }
                    break;
                case Champion.Katarina:
                    if (target.HasBuff("katarinaqmark"))
                    {
                        rawMagicalDamage += 0.15f * fromHero.FlatMagicDamageMod +
                                            15f * fromHero.Spellbook.GetSpell(SpellSlot.Q).Level;
                    }
                    break;
                case Champion.Kindred:
                    break;
                case Champion.KogMaw:
                    if (fromHero.HasBuff("KogMawBioArcaneBarrage"))
                    {
                        var dmg = fromHero.CalculateDamageOnUnit(target, DamageType.Magical,
                            target.MaxHealth * ((1 + fromHero.Spellbook.GetSpell(SpellSlot.W).Level + (int) (fromHero.FlatMagicDamageMod / 100)) / 100f), false);
                        if (targetIsMinion)
                        {
                            dmg = Math.Min(dmg, 100);
                        }
                        calculatedMagicalDamage += dmg;
                    }
                    break;
                case Champion.Lux:
                    if (target.HasBuff("LuxIlluminatingFraulein"))
                    {
                        rawMagicalDamage += 10f + 10f * fromHero.Level + 0.2f * fromHero.FlatMagicDamageMod;
                    }
                    break;
                case Champion.Orianna:
                    count = (fromHero.GetBuffCount("orianapowerdaggerdisplay") > 0 && fromHero.IsMe &&
                             Orbwalker.LastTarget != null && Orbwalker.LastTarget.NetworkId == target.NetworkId)
                        ? fromHero.GetBuffCount("orianapowerdaggerdisplay")
                        : 0;
                    rawMagicalDamage += (8f * (int) Math.Truncate(((fromHero.Level + 2) / 3f)) + 2f +
                                         count * (1.6f * (int) Math.Truncate(((fromHero.Level + 2) / 3f)) + 0.4f) +
                                         ((15f + 3f * count) / 100f) * fromHero.FlatMagicDamageMod);
                    break;
                case Champion.Quinn:
                    if (target.HasBuff("QuinnW"))
                    {
                        rawPhysicalDamage += 0.5f * fromHero.TotalAttackDamage;
                    }
                    break;
                case Champion.Shyvana:
                    if (target.HasBuff("ShyvanaFireballMissile"))
                    {
                        rawMagicalDamage += Math.Min(target.MaxHealth * .025f,
                            target.IsMonster ? 100 : target.MaxHealth);
                    }
                    break;
                case Champion.Vayne:
                    if (target.GetBuffCount("vaynesilvereddebuff") == 2)
                    {
                        calculatedTrueDamage +=
                            Math.Max(
                                (0.045f + 0.015f * fromHero.Spellbook.GetSpell(SpellSlot.W).Level) *
                                target.MaxHealth, 20f * fromHero.Spellbook.GetSpell(SpellSlot.W).Level + 20f);
                    }
                    break;
                case Champion.Zed:
                    if (target.HealthPercent <= 50 && !target.HasBuff("zedpassivecd"))
                    {
                        rawMagicalDamage += target.MaxHealth *
                                            ((int) Math.Truncate((fromHero.Level - 1f) / 6f) * 2f + 6f) / 100f;
                    }
                    break;
            }
            // Special case Kalista
            if (fromHero.Hero == Champion.Kalista)
            {
                if (
                    target.Buffs.Any(
                        o => o.IsValid() && o.Caster == fromHero && o.Name == "kalistacoopstrikemarkally"))
                {
                    if (targetIsMinion && target.Health <= 125)
                    {
                        return target.Health;
                    }
                    rawMagicalDamage +=
                        Math.Min(
                            targetIsMinion
                                ? (new[] { 75f, 125f, 150f, 175f, 200f })[
                                    Math.Min(fromHero.Spellbook.GetSpell(SpellSlot.W).Level, 5) - 1]
                                : target.MaxHealth,
                            (new[] { 0.1f, 0.125f, 0.15f, 0.175f, 0.2f })[
                                Math.Min(fromHero.Spellbook.GetSpell(SpellSlot.W).Level, 5) - 1] * target.MaxHealth);
                }
            }
            else if (EntityManager.Heroes.ContainsKalista && fromHero.HasBuff("KalistaPassiveCoopStrike"))
            {
                var kalista = EntityManager.Heroes.AllHeroes.FirstOrDefault(o => o.Team == fromHero.Team && o.Hero == Champion.Kalista);
                if (kalista != null &&
                    target.Buffs.Any(o => o.IsValid() && o.Name == "kalistacoopstrikemarkself"))
                {
                    if (targetIsMinion && target.Health <= 125)
                    {
                        return target.Health;
                    }
                    rawMagicalDamage +=
                        Math.Min(
                            targetIsMinion
                                ? (new[] { 75f, 125f, 150f, 175f, 200f })[
                                    Math.Min(kalista.Spellbook.GetSpell(SpellSlot.W).Level, 5) - 1]
                                : target.MaxHealth,
                            (new[] { 0.1f, 0.125f, 0.15f, 0.175f, 0.2f })[
                                Math.Min(kalista.Spellbook.GetSpell(SpellSlot.W).Level, 5) - 1] * target.MaxHealth);
                }
            }

            // Azir solider special calculations
            if (fromHero.IsMe && fromHero.Hero == Champion.Azir)
            {
                var soldierCount = Orbwalker.AzirSoldiers.Count(i => i.IsInAutoAttackRange(target));
                if (soldierCount > 0)
                {
                    rawTotal = (new[] { 50, 52, 54, 56, 58, 60, 63, 66, 69, 72, 75, 85, 95, 110, 125, 140, 155, 170, 180 }[Math.Min(fromHero.Level, 18) - 1] + fromHero.FlatMagicDamageMod * 0.6f) *
                               (soldierCount * 0.25f + 0.75f);
                    precalculated._autoAttackDamageType = DamageType.Magical;
                }
            }

            #endregion

            switch (precalculated._autoAttackDamageType)
            {
                case DamageType.Physical:
                    rawPhysicalDamage += rawTotal;
                    break;
                case DamageType.Magical:
                    rawMagicalDamage += rawTotal;
                    break;
                case DamageType.True:
                    calculatedTrueDamage += rawTotal;
                    break;
            }
            if (rawPhysicalDamage > 0f)
            {
                calculatedPhysicalDamage += fromHero.CalculateDamageOnUnit(target, DamageType.Physical, rawPhysicalDamage, false, precalculated._autoAttackDamageType == DamageType.Physical);
            }
            if (rawMagicalDamage > 0f)
            {
                calculatedMagicalDamage += fromHero.CalculateDamageOnUnit(target, DamageType.Magical, rawMagicalDamage, false, precalculated._autoAttackDamageType == DamageType.Magical);
            }
            var percentMod = 1f;
            if (Math.Abs(fromHero.FlatCritChanceMod - 1f) < float.Epsilon || guaranteedCriticalStrike)
            {
                percentMod *= fromHero.GetCriticalStrikePercentMod();
            }
            return percentMod * calculatedPhysicalDamage + calculatedMagicalDamage + calculatedTrueDamage;
        }

        public static float GetCriticalStrikePercentMod(this AIHeroClient fromHero)
        {
            var baseCriticalDamage = (fromHero.HasItem(ItemId.Infinity_Edge) ? 0.5f : 0f) + 2f;
            switch (fromHero.Hero)
            {
                case Champion.Jhin:
                    baseCriticalDamage *= 0.75f;
                    break;
                case Champion.Shaco:
                    if (fromHero.HasBuff("Deceive"))
                    {
                        baseCriticalDamage -= 0.8f - 0.2f * fromHero.Spellbook.GetSpell(SpellSlot.Q).Level;
                    }
                    break;
                case Champion.XinZhao:
                    baseCriticalDamage -= 0.875f - 0.125f * fromHero.Spellbook.GetSpell(SpellSlot.W).Level;
                    break;
                case Champion.Yasuo:
                    baseCriticalDamage *= 0.9f;
                    break;
            }
            return baseCriticalDamage;
        }

        public static float GetAutoAttackDamage(this Obj_AI_Base from, Obj_AI_Base target, bool respectPassives = false)
        {
            if (from == null)
            {
                return 0f;
            }
            if (target == null)
            {
                return 0f;
            }
            var fromHero = from as AIHeroClient;
            if (respectPassives)
            {
                if (fromHero != null)
                {
                    return fromHero.GetAutoAttackDamage(target, fromHero.GetStaticAutoAttackDamage(target.Type == GameObjectType.obj_AI_Minion));
                }
            }
            return from.CalculateDamageOnUnit(target, DamageType.Physical, from.TotalAttackDamage, false, true);
        }

        #endregion

        public static float CalculateDamageOnUnit(
            this Obj_AI_Base from,
            Obj_AI_Base target,
            DamageType damageType,
            float rawDamage,
            bool isAbility = true,
            bool isAutoAttackOrTargetted = false)
        {
            if (target == null)
            {
                return 0f;
            }
            // The logic for calculating damage is:
            // RawDamage -> + FlatPassiveDamage -> * PercentPassiveDamage -> * PercentReceivedDamage -> + FlatReceivedDamage -> - (Doran's Shield Reduction * PercentPassiveDamage)
            var percentMod = 1f;
            var baseResistance = 0f;
            var bonusResistance = 0f;
            var reductionFlat = 0f;
            var reductionPercent = 0f;
            var penetrationFlat = 0f;
            var penetrationPercent = 0f;
            var bonusPenetrationPercent = 0f;
            switch (damageType)
            {
                case DamageType.Physical:
                    baseResistance = target.CharData.Armor;
                    bonusResistance = target.Armor - target.CharData.Armor;
                    if (!Broken)
                    {
                        reductionFlat = from.FlatPhysicalReduction;
                        reductionPercent = from.PercentPhysicalReduction;
                        penetrationFlat = from.FlatArmorPenetrationMod;
                        penetrationPercent = from.PercentArmorPenetrationMod;
                        bonusPenetrationPercent = from.PercentBonusArmorPenetrationMod;
                    }
                    // Minions return wrong percent values.
                    if (from.Type == GameObjectType.obj_AI_Minion)
                    {
                        penetrationFlat = 0f;
                        penetrationPercent = 0f;
                        bonusPenetrationPercent = 0f;
                    }
                    // Turrets passive.
                    else if (from.Type == GameObjectType.obj_AI_Turret)
                    {
                        penetrationFlat = 0f;
                        penetrationPercent = from.IsLaneTurret() ? 0.75f : 0.25f;
                        bonusPenetrationPercent = 0f;
                    }
                    break;
                case DamageType.Magical:
                    baseResistance = target.CharData.SpellBlock;
                    bonusResistance = target.SpellBlock - target.CharData.SpellBlock;
                    if (!Broken)
                    {
                        reductionFlat = from.FlatMagicReduction;
                        reductionPercent = from.PercentMagicReduction;
                        penetrationFlat = from.FlatMagicPenetrationMod;
                        penetrationPercent = from.PercentMagicPenetrationMod;
                        bonusPenetrationPercent = from.PercentBonusMagicPenetrationMod;
                    }
                    break;

                case DamageType.True:
                    return rawDamage;
            }
            var resistance = baseResistance + bonusResistance;
            if (resistance > 0)
            {
                var basePercent = baseResistance / resistance;
                var bonusPercent = 1f - basePercent;
                baseResistance -= reductionFlat * basePercent;
                bonusResistance -= reductionFlat * bonusPercent;
                resistance = baseResistance + bonusResistance;
                if (resistance > 0)
                {
                    baseResistance *= 1f - reductionPercent;
                    bonusResistance *= 1f - reductionPercent;
                    baseResistance *= 1f - penetrationPercent;
                    bonusResistance *= 1f - penetrationPercent;
                    bonusResistance *= 1f - bonusPenetrationPercent;
                    resistance = baseResistance + bonusResistance;
                    resistance -= penetrationFlat;
                }
            }
            // Penetration cant reduce resistance below 0.
            if (resistance >= 0)
            {
                percentMod *= 100f / (100f + resistance);
            }
            else
            {
                percentMod *= 2f - 100f / (100f - resistance);
            }
            var fromHero = from as AIHeroClient;
            var fromMinion = from as Obj_AI_Minion;
            var targetHero = target as AIHeroClient;
            var targetMinion = target as Obj_AI_Minion;

            #region Percent Passive

            var percentPassive = 1f;

            #region Turret Passive

            if (from.Type == GameObjectType.obj_AI_Turret)
            {
                //TODO
                //Inhibitor Towers && Nexus Towers: Gains +1.05% damage per point of heat, up to a maximum of +125% extra damage.
                //Inner Towers: Gain 37.5% damage each time they strike a champion (Max 75% bonus damage).
                //Inner Towers: After the turret is fully heated, consecutive attacks against the same champion deal 25% additional damage (Max 50% bonus damage).

                if (target.Type == GameObjectType.obj_AI_Minion)
                {
                    percentPassive *= 1.25f;
                    // Siege minions receive 70% damage from turrets
                    if (target.IsSiegeMinion())
                    {
                        percentPassive *= 0.7f;
                    }
                }
                // Warming Up: Turrets gain 37.5% damage each time they strike a champion (Max 75% bonus damage).
                else if (target.Type == GameObjectType.AIHeroClient)
                {
                    percentPassive *= 1.375f;
                }
            }

            #endregion

            #region Minion Pushing 5.24

            if (fromMinion != null && targetMinion != null && Game.MapId == GameMapId.SummonersRift)
            {
                //While your team has a level advantage, your minions deal bonus damage to enemy minions equal to 5% + 5% per turret advantage in their lane, all multiplied by your team's level advantage.
                percentPassive *= 1f + fromMinion.PercentDamageToBarracksMinionMod;
            }

            #endregion

            if (fromHero != null)
            {
                #region Masteries

                if (fromHero.HasDoubleEdgedSword())
                {
                    percentPassive *= 1.03f;
                }
                if (fromHero.HasAssassin() && !EntityManager.Heroes.AllHeroes.Any(h => h.IsValidTarget() && fromHero.Team == h.Team && !h.Equals(fromHero) && h.IsInRange(fromHero, 800)))
                {
                    percentPassive *= 1.02f;
                }
                // Bounty Hunter
                // You gain a permanent 1 % damage increase for each unique enemy champion you kill
                //percentMod *= (1.0f + 0.01f * Buff.Count);

                // Oppressor
                // You deal 2.5 % increased damage to targets with impaired movement (slows, stuns, taunts, etc)
                //if (!target.CanMove){}

                // Sorcery
                // INCREASED DAMAGE FROM ABILITIES 0.4 / 0.8 / 1.2 / 1.6 / 2 %
                //if (isAbility) {percentMod *= (1.0f + 0.004f * Mastery.Count); }

                // Merciless
                // DAMAGE AMPLIFICATION 1 / 2 / 3 / 4 / 5 % increased damage to champions below 40 % health
                if (target.HealthPercent <= 40 && target.Type == GameObjectType.AIHeroClient)
                {
                    percentPassive *= (1.0f + 0.01f * fromHero.GetMercilessCount());
                }

                #endregion

                #region Champion Abilities

                if (targetHero != null) //Only because of FPS drops
                {
                    //Exhaust reduces damage dealt by 40% for 2.5 seconds.
                    if (fromHero.HasBuff("summonerexhaust"))
                    {
                        percentPassive *= 0.6f;
                    }

                    // Sona's Aria of Perseverance.png Aria of Perseverance enhances her Power Chord.png Power Chord, to debuff her target to deal 20% (+ 2% per 100 AP) less damage for 3 seconds.
                    if (target.HasBuff("sonapassivedebuff"))
                    {
                        var caster = target.GetBuff("sonapassivedebuff").Caster as AIHeroClient;
                        percentPassive *= (1f - (0.2f + (caster != null ? 0.02f * (int) (caster.FlatMagicDamageMod / 100f) : 0f)));
                    }

                    // Urgot's Zaun-Touched Bolt Augmenter.png Zaun-Touched Bolt Augmenter makes his autoattacks and Acid Hunter.png Acid Hunter reduce all damage that his target deals by 15% for 2.5 seconds.
                    if (target.HasBuff("urgotcorrosivedebuff"))
                    {
                        percentPassive *= 0.85f;
                    }
                }
                switch (fromHero.Hero)
                {
                    case Champion.Fizz:
                        if (targetHero != null && damageType == DamageType.Magical)
                        {
                            if (target.HasBuff("fizzrbonusbuff"))
                            {
                                percentPassive *= 1.2f;
                            }
                        }
                        break;
                    case Champion.Jayce:
                        if (isAutoAttackOrTargetted && fromHero.HasBuff("jaycehypercharge"))
                        {
                            percentPassive *= (0.62f + 0.08f * fromHero.Spellbook.GetSpell(SpellSlot.W).Level);
                        }
                        break;
                }

                #endregion

                #region Items

                if (targetHero != null)
                {
                    if (damageType == DamageType.Physical && fromHero.MaxHealth < targetHero.MaxHealth)
                    {
                        if (fromHero.HasItem(3034))
                        {
                            percentPassive *= 1f + Math.Min(targetHero.MaxHealth - fromHero.MaxHealth, 500f) / 50f * 0.010f;
                        }
                        if (fromHero.HasItem(3036))
                        {
                            percentPassive *= 1f + Math.Min(targetHero.MaxHealth - fromHero.MaxHealth, 500f) / 50f * 0.015f;
                        }
                    }
                }

                #endregion

                //Doom's Eve: +10% increased damage TODO
            }

            if (targetHero != null)
            {
                if (targetHero.HasDoubleEdgedSword())
                {
                    percentPassive *= 1.015f;
                }
            }

            #endregion

            #region Flat Passive

            var flatPassive = 0f;
            if (fromHero != null)
            {
                if (isAutoAttackOrTargetted)
                {
                    if (targetHero != null && fromHero.HasBuff("MasteryOnHitDamageStacker"))
                    {
                        // Fervor of Battle
                        // Your basic attacks and spells give you stacks of Fervor for 5 seconds, stacking 10 times.Each stack of Fervor adds 1 - 8 bonus physical damage to your basic attacks against champions, based on your level.
                        flatPassive += (0.9f + 0.73f * fromHero.Level) * fromHero.GetBuffCount("MasteryOnHitDamageStacker");
                    }
                    if (fromHero.HasItem(ItemId.Muramana) && fromHero.HasBuff("Muramana") && fromHero.ManaPercent >= 3)
                    {
                        flatPassive += 0.06f * fromHero.Mana;
                    }
                }
                if (isAutoAttackOrTargetted || isAbility)
                {
                    // Thunderlord's Decree TODO
                    // Your 3rd ability or basic attack on an enemy champion shocks them, dealing 10 - 180(+0.2 bonus attack damage)(+0.1 ability power) magic damage in an area around them
                }
            }

            #endregion

            #region PercentReceived

            var percentReceived = 1f;
            if (targetHero != null)
            {
                #region Increasing damage received

                if (targetHero.HasBuff("vladimirhemoplaguedebuff"))
                {
                    percentReceived *= 1.15f;
                }

                #endregion

                #region Decreasing damage received

                #region Buffs

                switch (targetHero.Hero)
                {
                    case Champion.Alistar:
                        if (targetHero.HasBuff("FerociousHowl"))
                        {
                            percentReceived *= 0.3f;
                        }
                        break;
                    case Champion.Braum:
                        if (targetHero.HasBuff("braumeshieldbuff"))
                        {
                            percentReceived *= 1f - (0.275f + 0.025f * targetHero.Spellbook.GetSpell(SpellSlot.E).Level);
                        }
                        break;
                    case Champion.Galio:
                        if (targetHero.HasBuff("GalioIdolOfDurand"))
                        {
                            percentReceived *= 0.5f;
                        }
                        break;
                    case Champion.Garen:
                        if (targetHero.HasBuff("GarenW"))
                        {
                            percentReceived *= 0.7f;
                        }
                        break;
                    case Champion.Gragas:
                        if (targetHero.HasBuff("gragaswself"))
                        {
                            percentReceived *= 1f - (0.08f + 0.02f * targetHero.Spellbook.GetSpell(SpellSlot.W).Level);
                        }
                        break;
                    case Champion.Kassadin:
                        if (targetHero.HasBuff("voidstone") && damageType == DamageType.Magical)
                        {
                            percentReceived *= 0.15f;
                        }
                        break;
                    case Champion.Katarina:
                        if (targetHero.HasBuff("katarinaereduction"))
                        {
                            percentReceived *= 0.85f;
                        }
                        break;
                    case Champion.Maokai:
                        if (targetHero.HasBuff("maokaidrain3defense") && from.Type != GameObjectType.obj_AI_Turret)
                        {
                            percentReceived *= 0.8f;
                        }
                        break;
                    case Champion.MasterYi:
                        if (targetHero.HasBuff("Meditate"))
                        {
                            percentReceived *= 1f -
                                               (0.45f + 0.05f * targetHero.Spellbook.GetSpell(SpellSlot.W).Level) /
                                               ((from.Type == GameObjectType.obj_AI_Turret) ? 2f : 1f);
                        }
                        break;
                    case Champion.Shen:
                        break;
                    case Champion.Urgot:
                        if (targetHero.HasBuff("urgotswapdef"))
                        {
                            percentReceived *= (1f - (0.2f + 0.1f * targetHero.Spellbook.GetSpell(SpellSlot.R).Level));
                        }
                        break;
                    case Champion.Yorick: //TODO
                        break;
                }

                #endregion

                #region Items

                // Ninja Tabi
                // Blocks 10% of the damage from basic attacks.
                if (isAutoAttackOrTargetted && targetHero.HasItem(
                    ItemId.Ninja_Tabi,
                    ItemId.Ninja_Tabi_Enchantment_Alacrity,
                    ItemId.Ninja_Tabi_Enchantment_Captain,
                    ItemId.Ninja_Tabi_Enchantment_Distortion,
                    ItemId.Ninja_Tabi_Enchantment_Furor))
                {
                    percentReceived *= 0.88f;
                }
                // Phantom Dancer
                // UNIQUE – LAMENT: The last champion hit deals 12% less damage to you (ends after 10 seconds of not hitting).
                if (fromHero != null && fromHero.HasBuff("itemphantomdancerdebuff") && targetHero.HasItem(3046))
                {
                    percentReceived *= 0.88f;
                }

                #endregion

                #region Masteries

                // Bond of Stone
                // DAMAGE REDUCTION 4 % when near at least one allied champion TODO
                if (target.HasBuff("Mastery6263"))
                {
                    percentReceived *= 0.96f;
                }
                // Bond of Stone
                // IN THIS TOGETHER 6 % of the damage that the nearest allied champion would take is dealt to you instead.This can't bring you below 5% health.
                if (target.HasBuff("MasteryWardenOfTheDawn"))
                {
                    percentReceived *= 0.94f;
                }

                #endregion

                #endregion
            }
            //Baron's buff
            if (targetMinion != null && targetMinion.IsMelee && targetMinion.HasBuff("exaltedwithbaronnashorminion"))
            {
                if (fromHero != null)
                {
                    percentReceived *= 0.25f;
                }
                else if (from.Type == GameObjectType.obj_AI_Turret)
                {
                    percentReceived *= 0.7f;
                }
            }

            #endregion

            #region FlatReceived

            var flatReceived = 0f;
            if (targetHero != null)
            {
                #region Buffs

                switch (targetHero.Hero)
                {
                    case Champion.Amumu:
                        if (damageType == DamageType.Physical)
                        {
                            flatReceived -= 2f * targetHero.Spellbook.GetSpell(SpellSlot.E).Level;
                        }
                        break;
                    case Champion.Fizz:
                        if (isAutoAttackOrTargetted)
                        {
                            flatReceived -= 2f * (int) ((targetHero.Level + 2f) / 3f) + 2f;
                        }
                        break;
                }

                #endregion

                #region Masteries

                // Tough Skin
                // You take 2 less damage from champion and monster basic attacks

                #endregion
            }
            else if (targetMinion != null)
            {
                if (fromHero != null)
                {
                    // Savagery
                    // BONUS DAMAGE TO MINIONS AND MONSTERS 1 / 2 / 3 / 4 / 5 on single target spells and basic attacks
                    if (isAutoAttackOrTargetted)
                    {
                        flatReceived += fromHero.GetSavageryCount();
                    }
                }
                else if (fromMinion != null && Game.MapId == GameMapId.SummonersRift && Game.Time >= 240)
                {
                    //MINION DEFENSE BONUS
                    //While your team has a level advantage, your minions take reduced damage from enemy minions equal to 1 + 1 per turret advantage in that lane, with the turret advantage bonus multiplied by the team's level advantage.
                    //Damage reduction = 1 + (Level advantage * Turret advantage)
                    flatReceived -= targetMinion.FlatDamageReductionFromBarracksMinionMod;
                }
            }

            #endregion

            #region Item modifiers

            var otherDamageModifier = 0f;
            //Doran's Shield blocks 8 damage from champion basic attacks and single target spells. The damage reduction is calculated before armor/magic resist and percentage damage reduction benefits are taken into account.
            if (isAutoAttackOrTargetted && damageType == DamageType.Physical && target.Type == GameObjectType.AIHeroClient &&
                ((AIHeroClient) target).HasItem(ItemId.Dorans_Shield))
            {
                otherDamageModifier -= 8 * percentPassive * percentMod;
            }
            //Runic Echoes
            //UNIQUE – ECHO: Gains charges upon moving or casting. At 100 charges, the next instance of ability damage you deal will expend all charges to deal 100 (+ 10% AP) bonus magic damage to the first enemy hit
            //Echo is amplified to 250% damage on Large Monsters. Hitting a Large Monster with this effect will restore 18% of your missing mana.
            if (isAbility && fromHero != null && fromHero.HasItem(1402, 1410, 1414, 3673) && fromHero.GetBuffCount("itemmagicshankcharge") == 100)
            {
                otherDamageModifier += fromHero.CalculateDamageOnUnit(target, DamageType.Magical,
                    ((targetMinion != null && targetMinion.IsMonster) ? 2.5f : 1f) * (100f + 0.1f * fromHero.FlatMagicDamageMod), false);
            }

            #endregion

            return Math.Max(percentReceived * percentPassive * percentMod * (rawDamage + flatPassive) + flatReceived + otherDamageModifier, 0f);
        }

        #region Calculators

        public static Calculator CreateCalculator(Obj_AI_Base sourceUnit)
        {
            return new Calculator(sourceUnit);
        }

        public class Calculator
        {
            public Obj_AI_Base SourceUnit { get; protected set; }

            protected readonly Dictionary<Guid, DamageSourceBase> DamageSources = new Dictionary<Guid, DamageSourceBase>();

            internal Calculator(Obj_AI_Base sourceUnit)
            {
                // Initialize properties
                SourceUnit = sourceUnit;
            }

            public float GetDamage(Obj_AI_Base target)
            {
                return DamageSources.Sum(o => o.Value.GetDamage(SourceUnit, target));
            }

            public Calculator AddDamageSource(DamageSourceBase damageSource)
            {
                DamageSources[Guid.NewGuid()] = damageSource;
                return this;
            }

            public Calculator AddDamageSource(SpellSlot slot, DamageType damageType, float[] damages, Func<Obj_AI_Base, bool> condition = null)
            {
                Guid damageId;
                return AddDamageSource(out damageId, slot, damageType, damages);
            }

            public Calculator AddDamageSource(out Guid damageId, SpellSlot slot, DamageType damageType, float[] damages, Func<Obj_AI_Base, bool> condition = null)
            {
                damageId = Guid.NewGuid();
                DamageSources[damageId] = new DamageSource(slot, damageType)
                {
                    Damages = damages,
                    Condition = condition
                };
                return this;
            }

            public Calculator AddBonusDamageSource(
                SpellSlot slot,
                DamageType damageType,
                float[] damagePercentages,
                ScalingType scalingType,
                ScalingTarget scalingTarget = ScalingTarget.Source,
                Func<Obj_AI_Base, bool> condition = null)
            {
                Guid damageId;
                return AddBonusDamageSource(out damageId, slot, damageType, damagePercentages, scalingType, scalingTarget);
            }

            public Calculator AddBonusDamageSource(
                out Guid damageId,
                SpellSlot slot,
                DamageType damageType,
                float[] damagePercentages,
                ScalingType scalingType,
                ScalingTarget scalingTarget = ScalingTarget.Source,
                Func<Obj_AI_Base, bool> condition = null)
            {
                damageId = Guid.NewGuid();
                DamageSources[damageId] = new BonusDamageSource(slot, damageType)
                {
                    ScalingType = scalingType,
                    ScalingTarget = scalingTarget,
                    DamagePercentages = damagePercentages,
                    Condition = condition
                };
                return this;
            }

            public void RemoveDamageSource(Guid damageId)
            {
                DamageSources.Remove(damageId);
            }

            public void RemoveDamageSource(DamageSourceBase damageSource)
            {
                foreach (var entry in DamageSources.ToArray().Where(entry => entry.Value == damageSource))
                {
                    DamageSources.Remove(entry.Key);
                }
            }
        }

        public class ExpressionCalculator
        {
            private DataTable DataTable { get; set; }
            public List<IVariable> Variables { get; set; }

            public ExpressionCalculator()
            {
                DataTable = new DataTable();
                Variables = new List<IVariable>();
            }

            public float Calculate(string expresion, Obj_AI_Base source, Obj_AI_Base target, IVariable[] customVariables = null)
            {
                expresion = SetVaribles(expresion, source, target, customVariables);
                try
                {
                    return float.Parse(DataTable.Compute(expresion, null).ToString());
                }
                catch (Exception ex)
                {
                    Console.WriteLine(ex);
                    return 0f;
                }
            }

            public string SetVaribles(string expresion, Obj_AI_Base source, Obj_AI_Base target, IVariable[] customVariables)
            {
                expresion = Variables.Aggregate(expresion, (current, variable) => current.Replace("{" + variable.Key + "}", variable.GetValue(source, target).ToString("F")));
                if (customVariables != null)
                {
                    expresion = customVariables.Aggregate(expresion, (current, variable) => current.Replace("{" + variable.Key + "}", variable.GetValue(source, target).ToString("F")));
                }
                return expresion;
            }
        }

        #endregion

        #region Enums

        public enum ScalingType
        {
            AbilityPoints,
            AttackPoints,
            BonusAbilityPoints,
            BonusAttackPoints,
            Armor,
            MagicResist,
            CurrentHealth,
            CurrentMana,
            MaxHealth,
            MaxMana,
            MissingHealth
        }

        public enum ScalingTarget
        {
            Source,
            Target
        }

        #endregion

        #region Damage Sources

        public abstract class DamageSourceBase
        {
            public abstract float GetDamage(Obj_AI_Base source, Obj_AI_Base target);
        }

        public class DamageSource : DamageSourceBase
        {
            public SpellSlot Slot { get; set; }
            public DamageType DamageType { get; set; }

            public float[] Damages { get; set; }

            public Func<Obj_AI_Base, bool> Condition { get; set; }

            public DamageSource(SpellSlot slot, DamageType damageType)
            {
                // Initialize properties
                Slot = slot;
                DamageType = damageType;
            }

            public override float GetDamage(Obj_AI_Base source, Obj_AI_Base target)
            {
                var spell = source.Spellbook.GetSpell(Slot);
                if (spell == null || spell.Level == 0 || (Condition != null && !Condition(target)) || spell.Level > Damages.Length)
                {
                    return 0;
                }

                return source.CalculateDamageOnUnit(target, DamageType, Damages[spell.Level - 1]);
            }
        }

        public class BonusDamageSource : DamageSourceBase
        {
            public SpellSlot Slot { get; set; }
            public DamageType DamageType { get; set; }

            public ScalingType ScalingType { get; set; }
            public ScalingTarget ScalingTarget { get; set; }
            public float[] DamagePercentages { get; set; }

            public Func<Obj_AI_Base, bool> Condition { get; set; }

            public BonusDamageSource(SpellSlot slot, DamageType damageType)
            {
                // Initialize properties
                Slot = slot;
                DamageType = damageType;
            }

            public override float GetDamage(Obj_AI_Base source, Obj_AI_Base target)
            {
                var spell = source.Spellbook.GetSpell(Slot);
                if (spell == null || spell.Level == 0 || (Condition != null && !Condition(target)) || spell.Level > DamagePercentages.Length)
                {
                    return 0;
                }

                var bonusDamage = 0f;
                var scalingTarget = ScalingTarget == ScalingTarget.Source ? source : target;

                switch (ScalingType)
                {
                    case ScalingType.AbilityPoints:
                        bonusDamage = scalingTarget.FlatMagicDamageMod;
                        break;
                    case ScalingType.Armor:
                        bonusDamage = scalingTarget.Armor + scalingTarget.FlatArmorMod;
                        break;
                    case ScalingType.AttackPoints:
                        bonusDamage = scalingTarget.TotalAttackDamage;
                        break;
                    case ScalingType.BonusAbilityPoints:
                        bonusDamage = scalingTarget.FlatMagicDamageMod;
                        break;
                    case ScalingType.BonusAttackPoints:
                        bonusDamage = scalingTarget.FlatPhysicalDamageMod;
                        break;
                    case ScalingType.CurrentHealth:
                        bonusDamage = scalingTarget.Health;
                        break;
                    case ScalingType.CurrentMana:
                        bonusDamage = scalingTarget.Mana;
                        break;
                    case ScalingType.MagicResist:
                        bonusDamage = scalingTarget.SpellBlock;
                        break;
                    case ScalingType.MaxHealth:
                        bonusDamage = scalingTarget.MaxHealth;
                        break;
                    case ScalingType.MaxMana:
                        bonusDamage = scalingTarget.MaxMana;
                        break;
                    case ScalingType.MissingHealth:
                        bonusDamage = scalingTarget.MaxHealth - scalingTarget.Health;
                        break;
                }

                return source.CalculateDamageOnUnit(target, DamageType, bonusDamage * DamagePercentages[spell.Level - 1]);
            }
        }

        public class DamageSourceBoundle : DamageSourceBase
        {
            public List<DamageSourceBase> DamageSources { get; protected set; }
            public List<DamageSourceExpression> DamageSourceExpresions { get; protected set; }

            public Func<Obj_AI_Base, bool> Condition { get; set; }

            public DamageSourceBoundle()
            {
                // Initialize properties
                DamageSources = new List<DamageSourceBase>();
                DamageSourceExpresions = new List<DamageSourceExpression>();
            }

            public void Add(DamageSourceBase damageSource)
            {
                DamageSources.Add(damageSource);
            }

            public void AddExpresion(DamageSourceExpression damageSource)
            {
                DamageSourceExpresions.Add(damageSource);
            }

            public void Remove(DamageSourceBase damageSource)
            {
                DamageSources.RemoveAll(o => o == damageSource);
            }

            public void RemoveExpresion(DamageSourceExpression damageSource)
            {
                DamageSourceExpresions.RemoveAll(o => o == damageSource);
            }

            public float GetDamage(Obj_AI_Base target)
            {
                return GetDamage(Player.Instance, target);
            }

            public override float GetDamage(Obj_AI_Base source, Obj_AI_Base target)
            {
                if (Condition != null && !Condition(target))
                {
                    return 0;
                }
                var baseDamage = DamageSources.Sum(o => o.GetDamage(source, target));
                return DamageSourceExpresions.Count == 0 ? baseDamage : DamageSourceExpresions.Sum(x => x.GetDamage(source, target, baseDamage));
            }
        }

        public abstract class DamageSourceExpression
        {
            public abstract float GetDamage(Obj_AI_Base source, Obj_AI_Base target, float baseDamage);
        }

        internal class ExpresionDamageSource : DamageSourceExpression
        {
            public ExpressionCalculator ExpressionCalculator { get; set; }
            public string Expression { get; set; }
            public string Condition { get; set; }
            public float RequriedValue { get; set; }
            public SpellSlot Slot { get; set; }
            public DamageType DamageType { get; set; }
            public float[] DamagePercentages { get; set; }
            public IEnumerable<IVariable> Variables
            {
                set { ExpressionCalculator.Variables = value.ToList(); }
            }

            public ExpresionDamageSource(string expression, SpellSlot slot, DamageType damageType)
            {
                ExpressionCalculator = new ExpressionCalculator();
                Expression = expression;
                Slot = slot;
                DamageType = damageType;
            }

            public bool CheckCondition(string condition, Obj_AI_Base source, Obj_AI_Base target)
            {
                if (string.IsNullOrEmpty(condition))
                {
                    return true;
                }
                var value = ExpressionCalculator.SetVaribles(condition, source, target, null);
                var statements = value.Split(' ');

                if (statements.Length != 3)
                {
                    return true;
                }
                switch (statements[1])
                {
                    case ">":
                        return float.Parse(statements[0]) > float.Parse(statements[2]);
                    case "<":
                        return float.Parse(statements[0]) < float.Parse(statements[2]);
                    case "==":
                        return Math.Abs(float.Parse(statements[0]) - float.Parse(statements[2])) < float.Epsilon;
                    case ">=":
                        return float.Parse(statements[0]) >= float.Parse(statements[2]);
                    case "<=":
                        return float.Parse(statements[0]) <= float.Parse(statements[2]);
                }
                return true;
            }

            public override float GetDamage(Obj_AI_Base source, Obj_AI_Base target, float baseDamage)
            {
                var spell = source.Spellbook.GetSpell(Slot);
                if (spell == null || spell.Level == 0)
                {
                    return 0;
                }
                var cond = CheckCondition(Condition, source, target);

                return cond
                    ? source.CalculateDamageOnUnit(target, DamageType, ExpressionCalculator.Calculate(Expression, source, target, GetCustomVaribales(baseDamage)))
                    : baseDamage;
            }

            private static IVariable[] GetCustomVaribales(float baseDamage)
            {
                return new IVariable[]
                {
                    new ExpresionBasicVarible("BaseDamage", (source, target) => baseDamage),
                };
            }
        }

        #endregion

        #region Expression Variables

        internal class ExpresionBasicVarible : IVariable
        {
            public string Key { get; set; }
            public Func<Obj_AI_Base, Obj_AI_Base, float> Value { get; set; }

            public ExpresionBasicVarible(string key, Func<Obj_AI_Base, Obj_AI_Base, float> value)
            {
                Key = key;
                Value = value;
            }

            public float GetValue(Obj_AI_Base source, Obj_AI_Base target)
            {
                return Value.Invoke(source, target);
            }
        }

        internal class ExpresionLevelVarible : IVariable
        {
            public string Key { get; set; }
            public SpellSlot Slot { get; set; }
            public float[] Damages { get; set; }

            public ExpresionLevelVarible(string key, SpellSlot slot, float[] damages)
            {
                Key = key;
                Slot = slot;
                Damages = damages;
            }

            public float GetValue(Obj_AI_Base source, Obj_AI_Base target)
            {
                var spell = source.Spellbook.GetSpell(Slot);
                if (spell == null)
                {
                    return 0f;
                }
                var spellLevel = spell.Level - 1;
                if (spellLevel > 0 && spellLevel <= Damages.Length)
                {
                    return Damages[spellLevel];
                }
                return 0f;
            }
        }

        internal class ExpresionStaticVarible : IVariable
        {
            public string Key { get; set; }
            public ScalingType Type { get; set; }
            public ScalingTarget Target { get; set; }

            public ExpresionStaticVarible(string key, ScalingTarget target, ScalingType type)
            {
                Key = key;
                Target = target;
                Type = type;
            }

            public float GetValue(Obj_AI_Base source, Obj_AI_Base target)
            {
                var objBase = Target == ScalingTarget.Source ? source : target;
                var bonusDamage = 0f;
                switch (Type)
                {
                    case ScalingType.AbilityPoints:
                        bonusDamage = objBase.FlatMagicDamageMod;
                        break;
                    case ScalingType.Armor:
                        bonusDamage = objBase.Armor + objBase.FlatArmorMod;
                        break;
                    case ScalingType.AttackPoints:
                        bonusDamage = objBase.TotalAttackDamage;
                        break;
                    case ScalingType.BonusAbilityPoints:
                        bonusDamage = objBase.FlatMagicDamageMod;
                        break;
                    case ScalingType.BonusAttackPoints:
                        bonusDamage = objBase.FlatPhysicalDamageMod;
                        break;
                    case ScalingType.CurrentHealth:
                        bonusDamage = objBase.Health;
                        break;
                    case ScalingType.CurrentMana:
                        bonusDamage = objBase.Mana;
                        break;
                    case ScalingType.MagicResist:
                        bonusDamage = objBase.SpellBlock;
                        break;
                    case ScalingType.MaxHealth:
                        bonusDamage = objBase.MaxHealth;
                        break;
                    case ScalingType.MaxMana:
                        bonusDamage = objBase.MaxMana;
                        break;
                }
                return bonusDamage;
            }
        }

        internal class ExpresionTypeVarible : IVariable
        {
            public string Key { get; set; }
            public ScalingTarget Target { get; set; }
            public DamageType Type { get; set; }
            public string Name { get; set; }
            public string[] Parameters { get; set; }
            public bool IsMethod
            {
                get { return Parameters.Length != 0; }
            }
            private Type ObjectType { get; set; }
            private MethodInfo Method { get; set; }
            private ParameterInfo[] MethodParameters { get; set; }
            private PropertyInfo Property { get; set; }

            public ExpresionTypeVarible(string key, DamageType type, ScalingTarget target, string name, params string[] param)
            {
                Key = key;
                Type = type;
                Target = target;
                Name = name;
                Parameters = param;

                ObjectType = typeof (Obj_AI_Base);
                if (IsMethod)
                {
                    Method = ObjectType.GetMethod(name);
                    if (Method != null)
                    {
                        MethodParameters = Method.GetParameters();
                        return;
                    }
                }
                else
                {
                    Property = ObjectType.GetProperty(name);
                    if (Property != null)
                    {
                        return;
                    }
                }
                throw new ArgumentException((IsMethod ? "Method" : "Property") + " (" + name + ") not found!");
            }

            public float GetValue(Obj_AI_Base source, Obj_AI_Base target)
            {
                var objBase = Target == ScalingTarget.Source ? source : target;
                if (IsMethod && Method != null)
                {
                    var convertedParams = new object[MethodParameters.Length];
                    for (int i = 0; i < MethodParameters.Length; i++)
                    {
                        if (i > Parameters.Length - 1 && MethodParameters[i].HasDefaultValue)
                        {
                            convertedParams[i] = MethodParameters[i].DefaultValue;
                        }
                        else if (!MethodParameters[i].HasDefaultValue && Parameters.Length < i)
                        {
                            throw new ArgumentException("Not Enough Arguments For Method Expected: " + MethodParameters.Length + ",  Collected: " + Parameters.Length);
                        }
                        else
                        {
                            if (MethodParameters[i].ParameterType == typeof (float))
                            {
                                convertedParams[i] = float.Parse(Parameters[i]);
                            }
                            else if (MethodParameters[i].ParameterType == typeof (int))
                            {
                                convertedParams[i] = int.Parse(Parameters[i]);
                            }
                            else if (MethodParameters[i].ParameterType == typeof (string))
                            {
                                convertedParams[i] = Parameters[i];
                            }
                        }
                    }
                    var value = Method.Invoke(objBase, convertedParams);
                    if (Method.ReturnType == typeof (float))
                    {
                        return (float) value;
                    }
                    if (Method.ReturnType == typeof (int))
                    {
                        return (int) value;
                    }
                }
                else
                {
                    return (float) Property.GetValue(objBase);
                }

                return 0f;
            }
        }

        public interface IVariable
        {
            string Key { get; }
            float GetValue(Obj_AI_Base source, Obj_AI_Base target);
        }

        #endregion
    }
}
