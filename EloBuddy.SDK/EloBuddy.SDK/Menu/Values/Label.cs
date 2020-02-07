using System;
using System.Collections.Generic;
using System.Linq;
using EloBuddy.SDK.Rendering;

namespace EloBuddy.SDK.Menu.Values
{
    public class Label : ValueBase<string>
    {
        public override bool ShouldSerialize
        {
            get { return false; }
        }

        internal const float HeightMultiplier = 1;

        internal Text TextHandle { get; set; }

        public override string CurrentValue
        {
            get { return TextHandle.TextValue; }
            set
            {
                TextHandle.TextValue = value;
                base.CurrentValue = value;
            }
        }

        public override string DisplayName
        {
            get { return CurrentValue; }
            set { CurrentValue = value; }
        }

        public override string VisibleName
        {
            get { return TextHandle.DisplayedText; }
        }

        internal float _textWidthMultiplier = 1;
        internal float TextWidthMultiplier
        {
            get { return _textWidthMultiplier; }
            set
            {
                if (Math.Abs(_textWidthMultiplier - value) > float.Epsilon)
                {
                    _textWidthMultiplier = value;
                    TextHandle.Width = (int) (DefaultWidth * value);
                }
            }
        }

        internal Label(string displayName, int height) : base(displayName, height)
        {
            // Initialize properties
            TextObjects.Add(TextHandle = new Text(displayName, DefaultFont)
            {
                Color = DefaultColorGreen
            });
            TextWidthMultiplier = 1;

            // Initalize theme specific properties
            OnThemeChange();
        }

        public Label(string displayName) : this(displayName, DefaultHeight)
        {
        }

        protected internal override sealed void OnThemeChange()
        {
            // Apply base theme
            base.OnThemeChange();

            // Update text position
            TextHandle.Width = (int) (DefaultWidth * TextWidthMultiplier);
            TextHandle.ApplyToControlPosition(this);
        }

        #region Serialization

        public override Dictionary<string, object> Serialize()
        {
            var baseData = base.Serialize();
            //baseData.Add("DisplayName", DisplayName);
            return baseData;
        }

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

        internal static readonly List<string> DeserializationNeededKeys = new List<string>
        {
            //"DisplayName"
        };

        #endregion
    }
}
