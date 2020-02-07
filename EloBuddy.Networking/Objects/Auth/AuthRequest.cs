using System.Runtime.Serialization;

namespace EloBuddy.Networking.Objects
{
    [DataContract]
    public class AuthRequest
    {
        [DataMember]
        public string Username { get; set; }

        [DataMember]
        public string Password { get; set; }

        [DataMember]
        public string Version { get; set; }

        [DataMember]
        public string LeagueVersion { get; set; }

        [DataMember]
        public string Hash { get; set; }

        [DataMember]
        public string Hwid { get; set; }

        [DataMember]
        public object[] Params { get; set; }
    }
}