using System;
using System.Runtime.Serialization;

namespace EloBuddy.Networking.Objects
{
    [Serializable]
    [DataContract]
    public class TelemetryRequest
    {
        [DataMember]
        public byte[] Token { get; set; }
        
        [DataMember]
        public int GameId { get; set; }

        [DataMember]
        public AddonData[] Assemblies { get; set; }

        [DataMember]
        public object[] Data { get; set; }
    }

    [Serializable]
    [DataContract]
    public class AddonData
    {
        [DataMember]
        public string Name { get; set; }

        [DataMember]
        public string Author { get; set; }

        [DataMember]
        public string Repository { get; set; }

        [DataMember]
        public bool IsLocal { get; set; }

        [DataMember]
        public bool IsBuddyAddon { get; set; }

        [DataMember]
        public int AddonState { get; set; }

        [DataMember]
        public int AddonType { get; set; }
    }
}