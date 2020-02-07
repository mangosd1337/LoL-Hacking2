using System.Collections.Generic;
using Newtonsoft.Json;

namespace EloBuddy.SDK.Spells
{
    public static class SpellDatabase
    {
        internal static Dictionary<string, List<SpellInfo>> Database { get; set; }

        internal static void Initialize()
        {
            // Deserialize database
            Database = JsonConvert.DeserializeObject<Dictionary<string, List<SpellInfo>>>(DefaultSettings.SpellDatabase);

            // Listen to required events
            //Obj_AI_Base.OnProcessSpellCast += OnProcessSpellCast;
            //Obj_AI_Base.OnSpellCast += OnSpellCast;
        }

        public static List<SpellInfo> GetSpellInfoList(string baseSkinName)
        {
            if (Database.ContainsKey(baseSkinName))
            {
                return Database[baseSkinName];
            }
            return new List<SpellInfo>();
        }

        public static List<SpellInfo> GetSpellInfoList(Obj_AI_Base sender)
        {
            return GetSpellInfoList(sender.BaseSkinName);
        }

        /*
        internal delegate void SpellProcessHandler(Obj_AI_Base sender, SpellDataArgs args);
        internal delegate void SpellCastedHandler(Obj_AI_Base sender, SpellDataArgs args);

        internal static event SpellProcessHandler OnSpellProcess;
        internal static event SpellCastedHandler OnSpellCasted;

        private static void OnProcessSpellCast(Obj_AI_Base sender, GameObjectProcessSpellCastEventArgs args)
        {
            if (OnSpellProcess != null)
            {
                var info = GetSpellInfo(sender, args);
                if (info != null)
                {
                    OnSpellProcess(sender, new SpellDataArgs(args, info));
                }
            }
        }

        private static void OnSpellCast(Obj_AI_Base sender, GameObjectProcessSpellCastEventArgs args)
        {
            if (OnSpellCasted != null)
            {
                var info = GetSpellInfo(sender, args);
                if (info != null)
                {
                    OnSpellCasted(sender, new SpellDataArgs(args, info));
                }
            }
        }

        internal static SpellInfo GetSpellInfo(Obj_AI_Base sender, GameObjectProcessSpellCastEventArgs args)
        {
            // ReSharper disable once ConvertIfStatementToReturnStatement
            if (Database.ContainsKey(sender.BaseSkinName))
            {
                return Database[sender.BaseSkinName].Find(o => o.Slot == args.Slot);
            }

            return null;
        }

        public class SpellDataArgs : EventArgs
        {
            public GameObjectProcessSpellCastEventArgs Handle { get; private set; }
            public SpellInfo SpellInfo { get; private set; }

            // Wrapped properties and fields
            public GameObject Target
            {
                get { return Handle.Target; }
            }
            public SpellData SData
            {
                get { return Handle.SData; }
            }
            public SpellSlot Slot
            {
                get { return Handle.Slot; }
            }
            public Vector3 End
            {
                get { return Handle.End; }
            }
            public Vector3 Start
            {
                get { return Handle.Start; }
            }
            public bool IsToggle
            {
                get { return Handle.IsToggle; }
            }
            public bool Process
            {
                get { return Handle.Process; }
                set { Handle.Process = value; }
            }
            public float Time
            {
                get { return Handle.Time; }
            }

            public int CastedSpellCount
            {
                get { return Handle.CastedSpellCount; }
            }

            public SpellDataArgs(GameObjectProcessSpellCastEventArgs handle, SpellInfo info)
            {
                // Initialize properties
                Handle = handle;
                SpellInfo = info;
            }
        }
        */
    }
}
