using System;
using System.Collections.Generic;
using System.Linq;
using EloBuddy.SDK.Rendering;
using SharpDX;
using SharpDX.Direct3D9;
using Color = System.Drawing.Color;

namespace EloBuddy.SDK.Menu
{
    public sealed class Button : DynamicControl
    {
        internal static ThemeManager.SpriteType GetSpriteType(ButtonType buttonType)
        {
            switch (buttonType)
            {
                case ButtonType.Addon:
                case ButtonType.AddonSub:
                    return ThemeManager.SpriteType.ButtonAddon;
                case ButtonType.Confirm:
                    return ThemeManager.SpriteType.ButtonConfirm;
                case ButtonType.Exit:
                    return ThemeManager.SpriteType.ButtonExit;
                case ButtonType.Mini:
                    return ThemeManager.SpriteType.ButtonMini;
                case ButtonType.Normal:
                    return ThemeManager.SpriteType.ButtonNormal;
                case ButtonType.ComboBoxSub:
                    return ThemeManager.SpriteType.ControlComboBox;
            }

            throw new ArgumentException(string.Format("ButtonType '{0}' was not found!", buttonType), "buttonType");
        }

        internal enum ButtonType
        {
            Exit,
            Addon,
            AddonSub,
            Normal,
            Confirm,
            Mini,
            ComboBoxSub
        }

        internal ButtonType CurrentButtonType { get; set; }

        internal static readonly Dictionary<ButtonType, Color> DefaultColorValues = new Dictionary<ButtonType, Color>()
        {
            { ButtonType.Addon, Color.FromArgb(255, 67, 137, 123) },
            { ButtonType.AddonSub, Color.FromArgb(255, 67, 137, 123) },
            { ButtonType.Confirm, Color.FromArgb(255, 164, 243, 215) },
            { ButtonType.Mini, Color.FromArgb(255, 162, 140, 99) },
            { ButtonType.Normal, Color.FromArgb(255, 162, 140, 99) },
            { ButtonType.ComboBoxSub, Color.FromArgb(255, 162, 140, 99) }
        };

        internal static readonly Dictionary<ButtonType, Func<string, Text>> TextDictionary = new Dictionary<ButtonType, Func<string, Text>>
        {
            {
                ButtonType.Addon, text =>
                    new Text(text, new FontDescription
                    {
                        FaceName = ThemeManager.DefaultFontFaceName,
                        Height = 15,
                        Quality = FontQuality.Antialiased
                    })
                    { Color = DefaultColorValues[ButtonType.Addon] }
            },
            {
                ButtonType.AddonSub, text =>
                    new Text(text, new FontDescription
                    {
                        FaceName = ThemeManager.DefaultFontFaceName,
                        Height = 14,
                        Quality = FontQuality.Antialiased
                    })
                    { Color = DefaultColorValues[ButtonType.AddonSub] }
            },
            {
                ButtonType.Confirm, text =>
                    new Text(text, new FontDescription
                    {
                        FaceName = ThemeManager.DefaultFontFaceName,
                        Height = 18,
                        Quality = FontQuality.Antialiased
                    })
                    { Color = DefaultColorValues[ButtonType.Confirm] }
            },
            {
                ButtonType.Mini, text =>
                    new Text(text, new FontDescription
                    {
                        FaceName = ThemeManager.DefaultFontFaceName,
                        Height = 28,
                        Weight = FontWeight.Bold,
                        Width = 12,
                        Quality = FontQuality.Antialiased
                    })
                    { Color = DefaultColorValues[ButtonType.Mini] }
            },
            {
                ButtonType.Normal, text =>
                    new Text(text, new FontDescription
                    {
                        FaceName = ThemeManager.DefaultFontFaceName,
                        Height = 28,
                        Weight = FontWeight.Bold,
                        Width = 12,
                        Quality = FontQuality.Antialiased
                    })
                    { Color = DefaultColorValues[ButtonType.Normal] }
            },
            {
                ButtonType.ComboBoxSub, text =>
                    new Text(text, new FontDescription
                    {
                        FaceName = ThemeManager.DefaultFontFaceName,
                        Height = 14,
                        Quality = FontQuality.Antialiased
                    })
                    { Color = DefaultColorValues[ButtonType.ComboBoxSub] }
            },
        };

        internal Text TextHandle { get; set; }
        public string TextValue { get; set; }
        public string DisplayedText
        {
            get { return TextHandle != null ? TextHandle.DisplayedText : string.Empty; }
        }

        public override States CurrentState
        {
            get { return base.CurrentState; }
            internal set
            {
                base.CurrentState = value;
                if (TextHandle != null && DefaultColorValues.ContainsKey(CurrentButtonType))
                {
                    TextHandle.Color = CurrentColorModificationValue.Combine(DefaultColorValues[CurrentButtonType]);
                }
            }
        }

        internal Button(ButtonType buttonType, string displayText = null) : base(GetSpriteType(buttonType))
        {
            // Initialize properties
            CurrentButtonType = buttonType;

            // Initalize theme specific properties
            OnThemeChange();

            switch (buttonType)
            {
                case ButtonType.AddonSub:
                case ButtonType.ComboBoxSub:

                    // Initialize special case
                    DrawBase = false;
                    if (buttonType == ButtonType.AddonSub)
                    {
                        ExcludeFromParent = true;
                    }
                    break;
            }

            if (displayText != null)
            {
                SetText(displayText);
            }
        }

        internal void SetText(string text)
        {
            TextValue = text;
            switch (CurrentButtonType)
            {
                case ButtonType.Addon:
                case ButtonType.AddonSub:
                case ButtonType.ComboBoxSub:
                    SetText(text, Text.Align.Left, (int) (15 * (CurrentButtonType == ButtonType.AddonSub ? 1.5f : 1f)));
                    break;
                case ButtonType.Exit:
                    break;
                default:
                    SetText(text, Text.Align.Center);
                    break;
            }
        }

        internal void SetText(string text, Text.Align align, int xOffset = 0, int yOffset = 0)
        {
            TextValue = text;
            if (TextHandle == null)
            {
                if (TextDictionary.ContainsKey(CurrentButtonType))
                {
                    TextHandle = TextDictionary[CurrentButtonType](CurrentButtonType == ButtonType.Addon ? text.ToUpper() : text);
                    TextHandle.Color = CurrentColorModificationValue.Combine(DefaultColorValues[CurrentButtonType]);
                    TextObjects.Add(TextHandle);
                }
                else
                {
                    return;
                }
            }
            else
            {
                TextHandle.TextValue = CurrentButtonType == ButtonType.Addon ? text.ToUpper() : text;
            }
            TextHandle.TextAlign = align;
            TextHandle.Padding = new Vector2(xOffset, yOffset);
            TextHandle.ApplyToControlPosition(this);
        }

        internal void RemoveText()
        {
            TextValue = null;
            if (TextHandle != null)
            {
                TextHandle.Dispose();
                TextHandle = null;
            }
        }

        protected internal override void OnThemeChange()
        {
            switch (CurrentButtonType)
            {
                case ButtonType.AddonSub:
                case ButtonType.ComboBoxSub:

                    // Update fake sizes
                    DynamicRectangle = ThemeManager.GetDynamicRectangle(this);
                    var rectangle =
                        Enum.GetValues(typeof (States))
                            .Cast<States>()
                            .Select(state => DynamicRectangle.GetRectangle(state))
                            .FirstOrDefault(rect => !rect.IsEmpty);
                    Size = new Vector2(rectangle.Width, (int) (rectangle.Height * (CurrentButtonType == ButtonType.AddonSub ? 0.75f : 1)));
                    SizeRectangle = new Rectangle(0, 0, (int) Size.X, (int) Size.Y);
                    UpdateCropRectangle();

                    // Update TextObject positions to current position
                    TextObjects.ForEach(o => o.ApplyToControlPosition(this));
                    break;

                default:

                    // Update base theme
                    base.OnThemeChange();
                    break;
            }
        }
    }
}
