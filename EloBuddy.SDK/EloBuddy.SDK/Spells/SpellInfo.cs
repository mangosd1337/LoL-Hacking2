using System.ComponentModel;
using System.Runtime.Serialization;
using Newtonsoft.Json;
using Newtonsoft.Json.Converters;

namespace EloBuddy.SDK.Spells
{
    [DataContract]
    public class SpellInfo
    {
        [DataMember(IsRequired = true)]
        [JsonConverter(typeof (StringEnumConverter))]
        [DefaultValue(-1)]
        public SpellType Type;
        [DataMember(IsRequired = true)]
        [JsonConverter(typeof (StringEnumConverter))]
        [DefaultValue(-1)]
        public SpellSlot Slot;
        [DataMember(IsRequired = true)]
        public float Range;
        [DataMember]
        public float Radius;
        [DataMember]
        public int CastRangeGrowthMin;
        [DataMember]
        public float CastRangeGrowthDuration;
        [DataMember]
        public int CastRangeGrowthMax;
        [DataMember]
        public float CastRangeGrowthStartTime;
        [DataMember]
        [JsonProperty(ItemConverterType = typeof (StringEnumConverter))]
        public CollisionType[] Collisions = { };
        [DataMember]
        public float Delay;
        [DataMember]
        public bool Acceleration;
        [DataMember]
        public bool Chargeable;
        [DataMember]
        public float MissileAccel;
        [DataMember]
        public float MissileFixedTravelTime;
        [DataMember]
        public float MissileMaxSpeed;
        [DataMember]
        public float MissileMinSpeed;
        [DataMember]
        public float MissileSpeed = float.MaxValue;
        [DataMember]
        public string SpellName;
    }
}
