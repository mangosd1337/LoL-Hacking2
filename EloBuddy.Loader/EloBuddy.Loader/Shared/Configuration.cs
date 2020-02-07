using System;
using System.Collections.Generic;
using System.Linq;
using System.Reflection;
using System.Runtime.Serialization;
using System.Security;

namespace EloBuddy.Sandbox.Shared
{
    [DataContract]
    public class Configuration
    {
        [DataMember]
        public bool StreamingMode { get; set; }

        [DataMember]
        public bool DrawWatermark { get; set; }

        [DataMember]
        public bool MovementHack { get; set; }

        [DataMember]
        public bool AntiAfk { get; set; }

        [DataMember]
        public bool Console { get; set; }

        [DataMember]
        public bool DisableRangeIndicator { get; set; }

        [DataMember]
        public string DataDirectory { get; set; }

        [DataMember]
        public bool ExtendedZoom { get; set; }

        [DataMember]
        public bool DisableChatFunction { get; set; }

        [DataMember]
        public string EloBuddyDllPath { get; set; }

        [DataMember]
        public string LibrariesDirectory { get; set; }

        [DataMember]
        public int MenuKey { get; set; }

        [DataMember]
        public int MenuToggleKey { get; set; }

        [DataMember]
        public PermissionSet Permissions { get; set; }

        [DataMember]
        public int ReloadAndRecompileKey { get; set; }

        [DataMember]
        public int ReloadKey { get; set; }

        [DataMember]
        public bool TowerRange { get; set; }

        [DataMember]
        public int UnloadKey { get; set; }

        [DataMember]
        public bool IsBuddy { get; set; }

        public override string ToString()
        {
            var excludedProperties = new HashSet<string>
            {
                "Permissions"
            };
            var properties = GetType().GetProperties(BindingFlags.Public | BindingFlags.Instance);

            return string.Join("\n", from propertyInfo in properties
                                     where !excludedProperties.Contains(propertyInfo.Name) && Attribute.IsDefined(propertyInfo, typeof(DataMemberAttribute))
                                     select propertyInfo.Name + ":" + propertyInfo.GetValue(this, null));
        }

        [DataMember]
        public string Username { get; set; }

        [DataMember]
        public string PasswordHash { get; set; }

        [DataMember]
        public string Hwid { get; set; }
    }
}
