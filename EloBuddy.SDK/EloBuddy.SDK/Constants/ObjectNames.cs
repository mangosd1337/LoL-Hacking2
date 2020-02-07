using System;
using System.Collections.Generic;
using System.Linq;

namespace EloBuddy.SDK.Constants
{
    public static class ObjectNames
    {
        internal static readonly string[] MinionList;
        public static string[] Minions
        {
            get { return MinionList; }
        }

        internal static readonly HashSet<string> InvalidTargets = new HashSet<string>
        {
            "JarvanIVStandard",
            "ZyraSeed"
            //"GangplankBarrel"
        };
        internal static readonly HashSet<string> LaneTurrets = new HashSet<string>
        {
            "SRUAP_Turret_Order1",
            "SRUAP_Turret_Order2",
            "SRUAP_Turret_Order3",
            "SRUAP_Turret_Chaos1",
            "SRUAP_Turret_Chaos2",
            "SRUAP_Turret_Chaos3"
        };
        internal static readonly HashSet<string> BaseTurrets = new HashSet<string>
        {
            "SRUAP_Turret_Order3",
            "SRUAP_Turret_Order4",
            "SRUAP_Turret_Chaos3",
            "SRUAP_Turret_Chaos4"
        };

        static ObjectNames()
        {
            var maps = new[] { "SRU", "HA" };
            var teams = new[] { "Chaos", "Order" };
            var minions = new[] { "Melee", "Ranged", "Siege", "Super" };
            var oldTeams = new[] { "Blue", "Red" };
            var oldMinions = new[] { "Basic", "MechCannon", "MechMelee", "Wizard" };

            // SummonersRift & HowlingAbyss
            var minionsList =
                (from map in maps
                 from team in teams
                 from minion in minions
                 select string.Format("{0}_{1}Minion{2}", map, team, minion)).ToList();

            // TheCrystalScar
            minionsList.AddRange(new[]
            {
                "OdinBlueSuperminion",
                "OdinRedSuperminion",
                "Odin_Blue_Minion_Caster",
                "Odin_Red_Minion_Caster"
            });

            // Old minion names
            minionsList.AddRange(from team in oldTeams
                                 from minion in oldMinions
                                 select string.Format("{0}_Minion_{1}", team, minions));

            // Black Market Brawlers Mode
            var nameSuffix = new[] { "Ocklepod", "Pludercrab", "Ironback", "Razorfin" };
            minionsList.AddRange(nameSuffix.Select(name => string.Format("BW_{0}", name)));

            //  Upgraded minions
            minionsList.AddRange(
                (from team in teams
                 from minion in minions
                 select string.Format("BilgeLane{0}_{1}", minion, team)));

            // Cannons
            minionsList.AddRange(
                (from team in teams
                 select string.Format("BilgeLaneCannon_{0}", team)));

            // Apply the list
            MinionList = minionsList.ToArray();
        }
    }
}
