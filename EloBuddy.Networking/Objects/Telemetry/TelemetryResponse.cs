using System.Runtime.Serialization;

namespace EloBuddy.Networking.Objects
{
    [DataContract]
    public class TelemetryResponse
    {
        [DataMember]
        public object[] Data { get; set; }
    }
}