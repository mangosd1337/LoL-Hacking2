using System.Runtime.Serialization;

namespace EloBuddy.Networking.Objects
{
    [DataContract]
    public class AuthResponse
    {
        [DataMember]
        public string DisplayName { get; set; }
       
        [DataMember]
        public byte[] Avatar { get; set; }
       
        [DataMember]
        public string GroupName { get; set; }
       
        [DataMember]
        public int GroupId { get; set; }
       
        [DataMember]
        public byte[] Data { get; set; }
       
        [DataMember]
        public object[] Params { get; set; }

        [DataMember]
        public byte[] Token { get; set; }
    }
}