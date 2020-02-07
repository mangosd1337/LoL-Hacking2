using System;
using System.Collections.Generic;
using System.Linq;
using SharpDX;

namespace EloBuddy.SDK.Events
{
    public static class Dash
    {
        internal static readonly Dictionary<Obj_AI_Base, DashEventArgs> DashDictionary = new Dictionary<Obj_AI_Base, DashEventArgs>();

        public delegate void OnDashDelegate(Obj_AI_Base sender, DashEventArgs e);

        public static event OnDashDelegate OnDash;

        static Dash()
        {
            // Listen to required events
            Game.OnTick += delegate
            {
                DashDictionary.Keys.ToList().ForEach(o =>
                {
                    if (!o.IsValidTarget() || Core.GameTickCount > DashDictionary[o].EndTick)
                    {
                        DashDictionary.Remove(o);
                    }
                    else if (OnDash != null)
                    {
                        OnDash(o, DashDictionary[o]);
                    }
                });
            };
            Obj_AI_Base.OnNewPath += OnNewPath;
        }

        private static void OnNewPath(Obj_AI_Base sender, GameObjectNewPathEventArgs args)
        {
            if (!args.IsDash)
            {
                return;
            }

            var hero = sender as AIHeroClient;
            if (hero != null && hero.IsValid)
            {
                var key = DashDictionary.Keys.FirstOrDefault(o => o.NetworkId == sender.NetworkId) ?? sender;
                var dashArgs = new DashEventArgs
                {
                    StartPos = sender.ServerPosition,
                    EndPos = args.Path.Last(),
                    Speed = args.Speed,
                    StartTick = Core.GameTickCount - Game.Ping
                };
                dashArgs.EndTick = dashArgs.StartTick + (int) (1000 * args.Path.Last().Distance(sender) / 2500);
                dashArgs.Duration = dashArgs.EndTick - dashArgs.StartTick;

                DashDictionary.Remove(key);
                DashDictionary.Add(key, dashArgs);

                if (OnDash != null)
                {
                    OnDash(sender, dashArgs);
                }
            }
        }

        public static bool IsDashing(this Obj_AI_Base unit)
        {
            var key = DashDictionary.Keys.FirstOrDefault(o => o.NetworkId == unit.NetworkId);
            if (key != null)
            {
                return DashDictionary[key].EndTick > Core.GameTickCount;
            }

            return false;
        }

        public static DashEventArgs GetDashInfo(this Obj_AI_Base unit)
        {
            DashEventArgs value;
            DashDictionary.TryGetValue(unit, out value);
            return value;
        }

        public class DashEventArgs : EventArgs
        {
            public Vector3 StartPos { get; set; }
            public Vector3 EndPos { get; set; }
            public float Speed { get; set; }
            public int Duration { get; internal set; }
            public int StartTick { get; internal set; }
            public int EndTick { get; internal set; }
            public List<Vector2> Path { get; internal set; }
        }
    }
}
