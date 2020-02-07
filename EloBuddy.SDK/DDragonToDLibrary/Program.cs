using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.IO;
using System.Linq;
using System.Net;
using System.Text.RegularExpressions;
using EloBuddy.SDK;
using Newtonsoft.Json;

namespace DDragonToDLibrary
{
    public enum SpellSlot
    {
        Q,
        W,
        E,
        R,
        Unknown1,
        Unknown2,
        Unknown3,
        Unknown4,
        Unknown5,
    }

    public enum DamageType
    {
        Magical,
        Physical,
        True,
        Mixed
    }

    public static class Program
    {
        public const string VersionRequestUrl = "https://ddragon.leagueoflegends.com/api/versions.json";
        public static string CurrentVersion = "6.17.1";
        public static string ChampionRequestUrl
        {
            get { return string.Format("http://ddragon.leagueoflegends.com/cdn/{0}/data/en_US/championFull.json", CurrentVersion); }
        }

        public static readonly Dictionary<string, Damage.ScalingType> BonusDamageScalingTypeTranslation = new Dictionary<string, Damage.ScalingType>
        {
            { "bonusattackdamage", Damage.ScalingType.BonusAttackPoints },
            { "spelldamage", Damage.ScalingType.AbilityPoints },
            { "attackdamage", Damage.ScalingType.AttackPoints },
            { "@cooldownchampion", Damage.ScalingType.AttackPoints },
            { "@stacks", Damage.ScalingType.AttackPoints },
            { "@dynamic.attackdamage", Damage.ScalingType.AttackPoints },
            { "@dynamic.abilitypower", Damage.ScalingType.AttackPoints },
            { "bonusspellblock", Damage.ScalingType.MagicResist },
            { "armor", Damage.ScalingType.Armor },
            { "bonushealth", Damage.ScalingType.MaxHealth },
            { "mana", Damage.ScalingType.MaxMana },
            { "health", Damage.ScalingType.MaxHealth },
        };
        public static readonly Dictionary<string, Damage.ScalingTarget> BonusDamageScalingTargetTranslation = new Dictionary<string, Damage.ScalingTarget>
        {
            { "bonusattackdamage", Damage.ScalingTarget.Source },
            { "spelldamage", Damage.ScalingTarget.Source },
            { "attackdamage", Damage.ScalingTarget.Source },
            { "@cooldownchampion", Damage.ScalingTarget.Source },
            { "@stacks", Damage.ScalingTarget.Source },
            { "@special.BraumWArmor", Damage.ScalingTarget.Source },
            { "@special.BraumWMR", Damage.ScalingTarget.Source },
            { "@dynamic.attackdamage", Damage.ScalingTarget.Source },
            { "@special.dariusr3", Damage.ScalingTarget.Source },
            { "@text", Damage.ScalingTarget.Source },
            { "@dynamic.abilitypower", Damage.ScalingTarget.Source },
            { "@special.jaxrarmor", Damage.ScalingTarget.Source },
            { "@special.jaxrmr", Damage.ScalingTarget.Source },
            { "@special.jaycew", Damage.ScalingTarget.Source },
            { "bonusarmor", Damage.ScalingTarget.Source },
            { "bonusspellblock", Damage.ScalingTarget.Source },
            { "armor", Damage.ScalingTarget.Source },
            { "@special.nautilusq", Damage.ScalingTarget.Source },
            { "bonushealth", Damage.ScalingTarget.Source },
            { "mana", Damage.ScalingTarget.Source },
            { "health", Damage.ScalingTarget.Source },
            { "@special.viw", Damage.ScalingTarget.Source }
        };

        [STAThread]
        // ReSharper disable once FunctionComplexityOverflow
        public static void Main(string[] args)
        {
            var stopwatch = new Stopwatch();
            stopwatch.Start();

            // Prepare the response object
            RiotChampionResonse.ChampionListDto champList;

            using (var webClient = new WebClient())
            {
                // Update the version to the latest version
                try
                {
                    CurrentVersion = JsonConvert.DeserializeObject<string[]>(webClient.DownloadString(VersionRequestUrl))[0];
                    Console.WriteLine("Version updated to {0}", CurrentVersion);
                }
                catch (Exception e)
                {
                    Console.WriteLine("An error occured while trying to update the version:\n{0}", e);
                }

                // Download and convert the full champion list
                champList = JsonConvert.DeserializeObject<RiotChampionResonse.ChampionListDto>(webClient.DownloadString(ChampionRequestUrl));
            }

            stopwatch.Stop();
            Console.WriteLine("Downloaded champion data json file (took {0}ms)", stopwatch.ElapsedMilliseconds);
            Console.WriteLine();
            Console.WriteLine("Beginning parsing of the champion data...");
            Console.WriteLine("----------------------------------------------------------------------------");

            // Analyzing helpers
            var multiBonusAbilities = new Dictionary<string, List<string>>();
            var noDamageAbilities = new Dictionary<string, List<string>>();
            var noAbilitiesChamps = new List<string>();
            var differencingBonusDamageTypes = new Dictionary<string, List<string>>();

            // Prepare the outpud dictionary
            var champDataOutput = new Dictionary<string, Dictionary<SpellSlot, List<DamageLibrary.StageSpell>>>();
            foreach (var champData in champList.data.Values)
            {
                // Prepare the output
                var spells = new Dictionary<SpellSlot, List<DamageLibrary.StageSpell>>();

                // Create the spells, champs with more than 4 spells will have weird spell slots added, those will
                // have to be changed by hand and put into the according spell stage
                for (var i = 0; i < champData.spells.Count; i++)
                {
                    var spell = champData.spells[i];
                    var slot = (SpellSlot) i;
                    var lowerTooltip = spell.sanitizedTooltip.ToLower();
                    var stages = new List<DamageLibrary.StageSpell>();

                    // Check for faulty spells
                    if (spell.effect.Count == 0)
                    {
                        Console.WriteLine("Skipping {0} {1} ({2}), no effect data found!", champData.name, slot, spell.name);
                        continue;
                    }

                    // Check if the spell is a damage spell
                    var damageMatches = Regex.Matches(lowerTooltip, @"{{\s*e(\d+)\s*}}\s*(\(\s*\+{{\s*(\w\d+)\s*}}\)\s*)*(bonus\s*)?(true|physic\w*|magic\w*)\s*damage");
                    if (damageMatches.Count > 0)
                    {
                        // Loop through all damages found
                        foreach (Match damageMatch in damageMatches)
                        {
                            // Get the damage type
                            var dmgType = DamageType.True;
                            if (damageMatch.Groups[5].Value.Contains("physic"))
                            {
                                dmgType = DamageType.Physical;
                            }
                            else if (damageMatch.Groups[5].Value.Contains("magic"))
                            {
                                dmgType = DamageType.Magical;
                            }

                            // Create the champion spell damage object
                            var champSpell = new DamageLibrary.ChampionSpell
                            {
                                Damages = spell.effect[int.Parse(damageMatch.Groups[1].Value)].ToArray(),
                                DamageType = dmgType
                            };

                            // Get the bonus damage(s)
                            var bonuses = new List<DamageLibrary.SpellBonus>();
                            if (damageMatch.Groups[2].Success)
                            {
                                var bonusMatches = Regex.Matches(damageMatch.Groups[0].Value, @"(\(\s*\+{{\s*(\w\d+)\s*}}\s*\))");
                                if (bonusMatches.Count > 0)
                                {
                                    foreach (var bonus in bonusMatches.Cast<Match>().Select(bonusMatch => spell.vars.Find(o => o.key == bonusMatch.Groups[2].Value)).Where(bonus => bonus != null))
                                    {
                                        // Correct bonus coefficient
                                        if (bonus.coeff.Count == 1)
                                        {
                                            if (spell.effect[1] != null)
                                            {
                                                for (var k = 1; k < spell.effect[1].Count; k++)
                                                {
                                                    bonus.coeff.Add(bonus.coeff[0]);
                                                }
                                            }
                                            else
                                            {
                                                Console.WriteLine("spell.effect[1] is null");
                                            }
                                        }

                                        // Get the ScalingType
                                        var type = Damage.ScalingType.AttackPoints;
                                        if (BonusDamageScalingTypeTranslation.ContainsKey(bonus.link))
                                        {
                                            type = BonusDamageScalingTypeTranslation[bonus.link];
                                        }

                                        // Get the ScalingTarget
                                        var target = Damage.ScalingTarget.Source;
                                        if (BonusDamageScalingTargetTranslation.ContainsKey(bonus.link))
                                        {
                                            target = BonusDamageScalingTargetTranslation[bonus.link];
                                        }

                                        // Add the bonus to the list
                                        bonuses.Add(new DamageLibrary.SpellBonus
                                        {
                                            DamagePercentages = bonus.coeff.ToArray(),
                                            DamageType = dmgType,
                                            ScalingType = type,
                                            ScalingTarget = target
                                        });
                                    }

                                    if (bonusMatches.Count > 1)
                                    {
                                        if (!multiBonusAbilities.ContainsKey(champData.name))
                                        {
                                            multiBonusAbilities.Add(champData.name, new List<string>());
                                        }
                                        multiBonusAbilities[champData.name].Add(string.Format("{0}: {1}", slot, spell.name));
                                    }
                                }
                            }

                            // Add the bonus(es) to the damage object
                            champSpell.BonusDamages = bonuses.ToArray();

                            // Create the stage spell with the champ spell data
                            stages.Add(new DamageLibrary.StageSpell
                            {
                                SpellData = champSpell,
                                Stage = EloBuddy.SDK.DamageLibrary.SpellStages.Default
                            });
                        }

                        // Add the spell to the dictionary
                        spells.Add(slot, stages);
                    }
                    else
                    {
                        // No damage abilities found
                        if (!noDamageAbilities.ContainsKey(champData.name))
                        {
                            noDamageAbilities.Add(champData.name, new List<string>());
                        }
                        noDamageAbilities[champData.name].Add(string.Format("{0}: {1}", slot, spell.name));
                    }
                }

                if (spells.Count == 0)
                {
                    noAbilitiesChamps.Add(champData.name);
                }

                // Add the spells to the output
                champDataOutput.Add(champData.name, spells);
            }

            File.WriteAllText(Path.Combine(Environment.CurrentDirectory, "DamageLibrary.json"), JsonHelper.FormatJson(JsonConvert.SerializeObject(champDataOutput)));
            Console.WriteLine("----------------------------------------------------------------------------");
            Console.WriteLine("Pasing complete! Output saved to DamageLibrary.json!");
            Console.WriteLine();
            Console.WriteLine("Statistics:");
            Console.WriteLine("----------------------------------------------------------------------------");
            Console.WriteLine("Total number of champions: {0}", champDataOutput.Count);
            Console.WriteLine("Champs with no abilities at all (faulty code): {0}", noAbilitiesChamps.Count);
            if (noAbilitiesChamps.Count > 0)
            {
                Console.WriteLine("    - {0}", string.Join("\n    - ", noAbilitiesChamps));
            }
            Console.WriteLine("Champs with multiple bonus abilities: {0}", multiBonusAbilities.Count);
            if (multiBonusAbilities.Count > 0)
            {
                Console.WriteLine("    - {0}", string.Join("\n    - ", multiBonusAbilities.Select(o => string.Format("{0}\n        - {1}", o.Key, string.Join("\n        - ", o.Value)))));
            }
            Console.WriteLine("Champs with different damage types in their bonus abilities: {0}", differencingBonusDamageTypes.Count);
            if (differencingBonusDamageTypes.Count > 0)
            {
                Console.WriteLine("    - {0}", string.Join("\n    - ", differencingBonusDamageTypes.Select(o => string.Format("{0}\n        - {1}", o.Key, string.Join("\n        - ", o.Value)))));
            }
            Console.WriteLine("----------------------------------------------------------------------------");
            Console.WriteLine("End of statistics, press any key to exit...");
            Console.WriteLine();
            Console.ReadKey();

            /*
            var links = new Dictionary<string, Dictionary<string, List<string>>>();
            foreach (var champ in champList.data.Values)
            {
                foreach (var spell in champ.spells)
                {
                    foreach (var mod in spell.vars)
                    {
                        if (!links.ContainsKey(mod.link))
                        {
                            links.Add(mod.link, new Dictionary<string, List<string>>());
                        }
                        if (!links[mod.link].ContainsKey(champ.name))
                        {
                            links[mod.link].Add(champ.name, new List<string>());
                        }
                        links[mod.link][champ.name].Add(spell.name);
                    }
                }
            }

            //Clipboard.SetText(string.Join(",\n", links.Keys.Select(o => string.Format("{{ \"{0}\", Damage.ScalingTarget.{1} }}", o, Damage.ScalingTarget.Source))));

            Console.WriteLine("Bonus links:");
            Console.WriteLine("-------------------");
            Console.WriteLine(string.Join("\n",
                links.Select(o => string.Format("{0}\n    {1}", o.Key, string.Join("\n    ", o.Value.Select(e => string.Format("{0}: {1}", e.Key, string.Join(", ", e.Value))))))));

            Console.ReadKey();
            
            */
        }
    }
}
