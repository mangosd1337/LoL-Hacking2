using System.Runtime.Serialization;

namespace EloBuddy.Sandbox.Shared
{
    [DataContract]
    public class SharedAddon
    {
        [DataMember]
        public string PathToBinary { get; set; }
    }
}
