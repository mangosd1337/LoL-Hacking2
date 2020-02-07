using System;
using System.Collections.Generic;
using System.Linq;
using SharpDX.Direct3D9;

namespace EloBuddy.SDK.Menu.Values
{
    public sealed class GroupLabel : Label
    {
        internal new const float HeightMultiplier = 1.5f;

        public GroupLabel(string displayName)
            : base(displayName, (int) (DefaultHeight * HeightMultiplier))
        {
            // Initialize properties
            TextHandle.ReplaceFont(new FontDescription
            {
                FaceName = ThemeManager.DefaultFontFaceName,
                Height = 20
            });
            TextHandle.Color = DefaultColorGreen;
        }

        #region Serialization

        protected internal override bool ApplySerializedData(Dictionary<string, object> data)
        {
            if (data == null)
            {
                throw new ArgumentNullException("data");
            }

            if (base.ApplySerializedData(data))
            {
                // Check if all keys are present
                if (DeserializationNeededKeys.Any(key => !data.ContainsKey(key)))
                {
                    return false;
                }

                // Apply all keys to the object instance
                //DisplayName = (string)data["DisplayName"];

                return true;
            }
            return false;
        }

        #endregion
    }
}
