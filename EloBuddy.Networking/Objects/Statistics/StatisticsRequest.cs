using System.Runtime.Serialization;

namespace EloBuddy.Networking.Objects
{
    [DataContract]
    public class StatisticsRequest
    {
        [DataMember]
        public string Username { get; set; }

        [DataMember]
        public string Password { get; set; }

        [DataMember]
        public object[] Data { get; set; }
    }
}
