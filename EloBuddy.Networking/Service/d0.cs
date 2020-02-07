using System;
using System.Runtime.Serialization;

namespace EloBuddy.Networking.Service
{
    [DataContract(Name = "d0", Namespace = "http://schemas.datacontract.org/2004/07/")]
    [Serializable]
    public class d0 : r0
    {
        [OptionalField]
        private byte[] DataField;

        [DataMember]
        public byte[] Data
        {
            get
            {
                return this.DataField;
            }
            set
            {
                this.DataField = value;
            }
        }
    }
}
