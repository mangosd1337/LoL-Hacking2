using System;
using System.Collections.Generic;
using EloBuddy.SDK.Enumerations;

namespace EloBuddy.SDK.Events
{
    public static class Teleport
    {
        public delegate void TeleportHandler(Obj_AI_Base sender, TeleportEventArgs args);

        public static event TeleportHandler OnTeleport;

        internal static readonly Dictionary<int, TeleportEventArgs> TeleportDataNetId = new Dictionary<int, TeleportEventArgs>();

        static Teleport()
        {
            // Listen to required events
            Obj_AI_Base.OnTeleport += OnUnitTeleport;
        }

        private static void OnUnitTeleport(Obj_AI_Base sender, GameObjectTeleportEventArgs args)
        {
            var eventArgs = new TeleportEventArgs
            {
                Status = TeleportStatus.Unknown,
                Type = TeleportType.Unknown
            };

            if (sender == null)
            {
                return;
            }

            if (!TeleportDataNetId.ContainsKey(sender.NetworkId))
            {
                TeleportDataNetId[sender.NetworkId] = eventArgs;
            }

            if (!string.IsNullOrEmpty(args.RecallType))
            {
                eventArgs.Status = TeleportStatus.Start;
                eventArgs.Duration = GetDuration(args);
                eventArgs.Type = GetType(args);
                eventArgs.Start = Core.GameTickCount;

                TeleportDataNetId[sender.NetworkId] = eventArgs;
            }
            else
            {
                eventArgs = TeleportDataNetId[sender.NetworkId];
                eventArgs.Status = Core.GameTickCount - eventArgs.Start < eventArgs.Duration - 250
                    ? TeleportStatus.Abort
                    : TeleportStatus.Finish;
            }

            if (OnTeleport != null)
            {
                OnTeleport(sender, eventArgs);
            }
        }

        internal static TeleportType GetType(GameObjectTeleportEventArgs args)
        {
            switch (args.RecallName)
            {
                case "Recall":
                    return TeleportType.Recall;
                case "Teleport":
                    return TeleportType.Teleport;
                case "Gate":
                    return TeleportType.TwistedFate;
                case "Shen":
                    return TeleportType.Shen;
                default:
                    return TeleportType.Recall; //fallback
            }
        }

        internal static int GetDuration(GameObjectTeleportEventArgs args)
        {
            switch (GetType(args))
            {
                case TeleportType.Shen:
                    return 3000;
                case TeleportType.Teleport:
                    return 3500;
                case TeleportType.TwistedFate:
                    return 1500;
                case TeleportType.Recall:
                    return GetRecallDuration(args);
                default:
                    return 3500; //fallback
            }
        }

        internal static int GetRecallDuration(GameObjectTeleportEventArgs args)
        {
            switch (args.RecallType.ToLower())
            {
                case "recall":
                    return 8000;
                case "recallimproved":
                    return 7000;
                case "odinrecall":
                    return 4500;
                case "odinrecallimproved":
                    return 4000;
                case "superrecall":
                    return 4000;
                case "superrecallimproved":
                    return 4000;
                default:
                    return 8000; //fallback
            }
        }

        public class TeleportEventArgs : EventArgs
        {
            public int Start { get; internal set; }
            public int Duration { get; internal set; }
            public TeleportType Type { get; internal set; }
            public TeleportStatus Status { get; internal set; }
        }
    }
}
