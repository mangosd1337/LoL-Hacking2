using System;
using System.Collections.Generic;
using System.Linq;
using EloBuddy.SDK.Rendering;
using SharpDX;

namespace EloBuddy.SDK.Menu.Values
{
    public sealed class CheckBox : ValueBase<bool>
    {
        internal Text TextHandle { get; set; }
        internal DynamicControl ControlHandle { get; set; }

        protected internal override int Width
        {
            get { return base.Width / 2; }
        }

        public override bool CurrentValue
        {
            get { return base.CurrentValue; }
            set
            {
                ControlHandle.IsActive = value;
                base.CurrentValue = value;
            }
        }

        public override string DisplayName
        {
            get { return base.DisplayName; }
            set
            {
                base.DisplayName = value;
                TextHandle.TextValue = value;
            }
        }

        public override string VisibleName
        {
            get { return TextHandle.DisplayedText; }
        }

        public CheckBox(string displayName, bool defaultValue = true) : base(displayName, DefaultHeight)
        {
            // Initialize properties
            TextObjects.Add(TextHandle = new Text(displayName, DefaultFont)
            {
                Color = DefaultColorGold,
                TextOrientation = Text.Orientation.Center
            });
            ControlHandle = new CheckBoxHandle();
            CurrentValue = defaultValue;

            ControlHandle.OnActiveStateChanged += delegate
            {
                // Listen to active state changes
                CurrentValue = ControlHandle.IsActive;

                // Hover even when clicked on text
                if (IsMouseInside && !ControlHandle.IsMouseInside)
                {
                    ControlHandle.CurrentState = ControlHandle.IsActive ? DynamicControl.States.ActiveHover : DynamicControl.States.Hover;
                }
            };

            // Add handle to base
            Add(ControlHandle);

            // Initalize theme specific properties
            OnThemeChange();
        }

        protected internal override void OnThemeChange()
        {
            // Apply base theme
            base.OnThemeChange();

            // Update check box handle
            //ControlHandle.AlignOffset = new Vector2(Size.X - ControlHandle.Size.X, Size.Y / 2 - ControlHandle.Size.Y / 2); // Right align
            ControlHandle.AlignOffset = new Vector2(0, (Size.Y - ControlHandle.Size.Y) / 2); // Left align

            // Update text position offset
            TextHandle.Padding = new Vector2(ControlHandle.Size.X, 0);
            TextHandle.ApplyToControlPosition(this);
        }

        internal override bool CallLeftMouseUp()
        {
            return base.CallLeftMouseUp() && ControlHandle.CallLeftMouseUp();
        }

        internal override bool CallMouseLeave()
        {
            return base.CallMouseLeave() && ControlHandle.CallMouseLeave();
        }

        internal override bool CallMouseEnter()
        {
            return base.CallMouseEnter() && ControlHandle.CallMouseEnter();
        }

        internal override bool CallLeftMouseDown()
        {
            return base.CallLeftMouseDown() && ControlHandle.CallLeftMouseDown();
        }

        internal sealed class CheckBoxHandle : DynamicControl
        {
            internal CheckBoxHandle() : base(ThemeManager.SpriteType.ControlCheckBox)
            {
                // Initalize theme specific properties
                OnThemeChange();
            }
        }

        #region Serialization

        public override Dictionary<string, object> Serialize()
        {
            var baseData = base.Serialize();
            //baseData.Add("DisplayName", DisplayName);
            baseData.Add("CurrentValue", CurrentValue);
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
                //DisplayName = (string) data["DisplayName"];
                CurrentValue = (bool) data["CurrentValue"];

                return true;
            }
            return false;
        }

        internal static readonly List<string> DeserializationNeededKeys = new List<string>
        {
            //"DisplayName",
            "CurrentValue"
        };

        #endregion
    }
}
