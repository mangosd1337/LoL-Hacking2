using System;
using System.Collections.Generic;
using System.Linq;
using EloBuddy.SDK.Rendering;
using SharpDX;

namespace EloBuddy.SDK.Menu.Values
{
    public sealed class KeyBind : ValueBase<bool>
    {
        public const uint UnboundKey = EscapeKey;
        internal const uint EscapeKey = 27;
        internal const string UnboundText = "---";
        internal const string BindKeyText = "Press a new key";

        internal static readonly List<uint> CurrentlyDownKeys = new List<uint>();

        internal static readonly Dictionary<uint, string> ReadableKeys = new Dictionary<uint, string>
        {
            { 8, "Backspace" },
            { 9, "Tab" },
            { 13, "Enter (Return)" },
            { 16, "Shift" },
            { 17, "Control (CTRL)" },
            { 19, "Pause" },
            { 20, "Shift (Toggle)" },
            { EscapeKey, "Escape (ESC)" },
            { 32, "Spacebar" },
            { 33, "Page Up" },
            { 34, "Page Down" },
            { 35, "End" },
            { 36, "Home" },
            { 37, "Left" },
            { 38, "Up" },
            { 39, "Right" },
            { 40, "Down" },
            { 45, "Insert" },
            { 46, "Delete" },
            { 106, "* (NumPad)" },
            { 107, "+ (NumPad)" },
            { 109, "- (NumPad)" },
            { 110, "Delete (NumPad)" },
            { 111, "/ (NumPad)" },
            { 144, "Num Lock (Toggle)" },
            { 145, "Scroll" },
            { 192, "`" }
        };

        static KeyBind()
        {
            // Add missing readable keys
            for (uint i = 65; i <= 90; i++)
            {
                // Keys A - Z
                ReadableKeys.Add(i, Convert.ToString((char) i));
            }
            for (uint i = 48; i <= 57; i++)
            {
                // Keys 0 - 9
                ReadableKeys.Add(i, Convert.ToString((char) i));
            }
            var count = 0;
            for (uint i = 96; i <= 105; i++)
            {
                // Keys 0 - 9 (NumPad)
                ReadableKeys.Add(i, string.Concat(count++, " (NumPad)"));
            }
            count = 1;
            for (uint i = 112; i <= 123; i++)
            {
                // Keys F1 - F12
                ReadableKeys.Add(i, string.Concat("F", count++));
            }

            // Listen to required events
            Messages.OnMessage += OnMessage;
        }

        internal static void OnMessage(Messages.WindowMessage args)
        {
            switch (args.Message)
            {
                case WindowMessages.KeyDown:
                    CurrentlyDownKeys.Add(args.Handle.WParam);
                    break;
                case WindowMessages.KeyUp:
                    CurrentlyDownKeys.RemoveAll(o => o == args.Handle.WParam);
                    break;
            }
        }

        public static string UnicodeToReadableString(uint character)
        {
            return ReadableKeys.ContainsKey(character) ? ReadableKeys[character] : string.Format("Unknown ({0})", Convert.ToString(character));
        }

        internal static string UnicodeToKeyBindString(uint character)
        {
            switch (character)
            {
                case UnboundKey:
                    return UnboundText;
                default:
                    return UnicodeToReadableString(character);
            }
        }

        public enum BindTypes
        {
            HoldActive,
            PressToggle
        }

        internal CheckBoxHandle ControlHandle { get; set; }
        internal Text TextHandle { get; set; }
        internal Tuple<Text, Text> HeaderText { get; set; }

        public override Vector2 Position
        {
            get { return base.Position; }
            internal set
            {
                // Set base position
                base.Position = value;

                // Header handling
                if (DrawHeader)
                {
                    // Apply to this container
                    HeaderText.Item1.ApplyToControlPosition(this);
                    HeaderText.Item2.ApplyToControlPosition(this);
                }
            }
        }

        // Property indicating wheather to draw the desciption or not
        internal bool _drawHeader;
        internal bool DrawHeader
        {
            get { return _drawHeader; }
            set
            {
                if (_drawHeader != value)
                {
                    _drawHeader = value;
                    if (value && HeaderText == null)
                    {
                        HeaderText =
                            new Tuple<Text, Text>(
                                new Text("Key 1", DefaultFont)
                                {
                                    Color = DefaultColorGreen,
                                    TextOrientation = Text.Orientation.Top
                                },
                                new Text("Key 2", DefaultFont)
                                {
                                    Color = DefaultColorGreen,
                                    TextOrientation = Text.Orientation.Top
                                });
                    }

                    // Calculate new height
                    Height = DefaultHeight + (value ? HeaderHeight : 0);

                    // Recalculate Bounding
                    RecalculateBounding();

                    // Recalculate Cropping
                    ContainerView.UpdateChildrenCropping();
                }
            }
        }
        internal const int HeaderHeight = DefaultHeight;

        internal Tuple<uint, uint> _keys;
        public Tuple<uint, uint> Keys
        {
            get { return _keys; }
            set
            {
                _keys = value;
                KeyStrings = new Tuple<string, string>(UnicodeToKeyBindString(value.Item1), UnicodeToKeyBindString(value.Item2));

                if (Buttons != null)
                {
                    Buttons.Item1.KeyText = KeyStrings.Item1;
                    Buttons.Item2.KeyText = KeyStrings.Item2;
                }
            }
        }
        public Tuple<string, string> KeyStrings { get; internal set; }

        public bool DefaultValue { get; internal set; }

        public override bool CurrentValue
        {
            get { return base.CurrentValue; }
            set
            {
                switch (BindType)
                {
                    case BindTypes.HoldActive:

                        // Do not allow user override
                        if (ControlHandle.IsActive != base.CurrentValue)
                        {
                            base.CurrentValue = ControlHandle.IsActive;
                        }
                        break;

                    default:
                        ControlHandle.IsActive = value;
                        base.CurrentValue = value;
                        break;
                }
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

        public BindTypes BindType { get; internal set; }

        internal Tuple<KeyButtonHandle, KeyButtonHandle> Buttons { get; set; }
        internal KeyButtonHandle WaitingForInput { get; set; }

        public KeyBind(string displayName, bool defaultValue, BindTypes bindType, Tuple<uint, uint> defaultKeys)
            : base(displayName, DefaultHeight)
        {
            // Initialize properties
            ControlHandle = new CheckBoxHandle(bindType)
            {
                IsActive = defaultValue
            };
            TextObjects.Add(TextHandle = new Text(DisplayName, DefaultFont)
            {
                TextOrientation = Text.Orientation.Bottom,
                Color = DefaultColorGold
            });
            DefaultValue = defaultValue;
            CurrentValue = defaultValue;
            BindType = bindType;
            Keys = defaultKeys;
            Buttons = new Tuple<KeyButtonHandle, KeyButtonHandle>(new KeyButtonHandle(KeyStrings.Item1, this, true), new KeyButtonHandle(KeyStrings.Item2, this));

            // Initalize theme specific properties
            OnThemeChange();

            // Listen to active state changes
            ControlHandle.OnActiveStateChanged += delegate(DynamicControl sender, EventArgs args) { CurrentValue = sender.IsActive; };

            // Add controls to base container
            Add(ControlHandle);
            Add(Buttons.Item1);
            Add(Buttons.Item2);

            // Listen to required events
            Buttons.Item1.OnActiveStateChanged += OnActiveStateChanged;
            Buttons.Item2.OnActiveStateChanged += OnActiveStateChanged;
        }

        public KeyBind(
            string displayName,
            bool defaultValue,
            BindTypes bindType,
            uint defaultKey1 = UnboundKey,
            uint defaultKey2 = UnboundKey)
            : this(displayName, defaultValue, bindType, new Tuple<uint, uint>(defaultKey1, defaultKey2))
        {
        }

        public override bool Draw()
        {
            // Draw the base
            if (base.Draw())
            {
                if (DrawHeader)
                {
                    // Draw the headers
                    HeaderText.Item1.Draw();
                    HeaderText.Item2.Draw();
                }
                return true;
            }
            return false;
        }

        internal void OnActiveStateChanged(Control sender, EventArgs args)
        {
            var button = (KeyButtonHandle) sender;
            WaitingForInput = button.IsActive ? button : null;
        }

        protected internal override bool OnKeyDown(Messages.KeyDown args)
        {
            if (base.OnKeyDown(args))
            {
                if (WaitingForInput != null)
                {
                    // Update key for button
                    Keys = new Tuple<uint, uint>(WaitingForInput.IsFirstButton ? args.Key : Keys.Item1, !WaitingForInput.IsFirstButton ? args.Key : Keys.Item2);
                    WaitingForInput.IsActive = false;
                }

                // Don't handle unbound key
                if (args.Key == UnboundKey)
                {
                    return true;
                }

                switch (BindType)
                {
                    case BindTypes.PressToggle:
                        // Update toggle value
                        if (Keys.Item1 == args.Key || Keys.Item2 == args.Key)
                        {
                            ControlHandle.IsActive = !ControlHandle.IsActive;
                        }
                        break;

                    case BindTypes.HoldActive:
                        CurrentlyDownKeys.Add(args.Key);
                        UpdateHoldActiveState();
                        break;
                }

                return true;
            }
            return false;
        }

        protected internal override bool OnKeyUp(Messages.KeyUp args)
        {
            if (base.OnKeyUp(args))
            {
                // Don't handle unbound key
                if (args.Key == UnboundKey)
                {
                    return true;
                }

                switch (BindType)
                {
                    case BindTypes.HoldActive:
                        CurrentlyDownKeys.RemoveAll(o => o == args.Key);
                        UpdateHoldActiveState();
                        break;
                }
                return true;
            }
            return false;
        }

        internal void UpdateHoldActiveState()
        {
            ControlHandle.IsActive = DefaultValue
                ? CurrentlyDownKeys.All(o => o != Keys.Item1 && o != Keys.Item2)
                : CurrentlyDownKeys.Any(o => o == Keys.Item1 || o == Keys.Item2);
        }

        protected internal override void OnThemeChange()
        {
            // Update base theme
            base.OnThemeChange();

            // Recalculate checkbox control position
            ControlHandle.AlignOffset = new Vector2(0, ((DefaultHeight - ControlHandle.Size.Y) / 2) + (DrawHeader ? HeaderHeight : 0));

            // Recalculate text position
            TextHandle.Padding = new Vector2(ControlHandle.Size.Y, (DefaultHeight - TextHandle.Bounding.Height) / -2f);
            TextHandle.Width =
                (int) (DefaultWidth - DefaultWidth * KeyButtonHandle.WidthMultiplier * 2 - ControlHandle.Size.Y);
            TextHandle.ApplyToControlPosition(this);

            // Header positions
            if (DrawHeader)
            {
                HeaderText.Item1.Padding =
                    new Vector2(
                        Width - DefaultWidth * KeyButtonHandle.WidthMultiplier * 2 +
                        (DefaultWidth * KeyButtonHandle.WidthMultiplier - HeaderText.Item1.Bounding.Width) / 2,
                        (HeaderHeight - HeaderText.Item1.Bounding.Height) / 2f);
                HeaderText.Item2.Padding =
                    new Vector2(
                        Width - DefaultWidth * KeyButtonHandle.WidthMultiplier +
                        (DefaultWidth * KeyButtonHandle.WidthMultiplier - HeaderText.Item1.Bounding.Width) / 2,
                        (HeaderHeight - HeaderText.Item1.Bounding.Height) / 2f);

                // Apply to this container
                HeaderText.Item1.ApplyToControlPosition(this);
                HeaderText.Item2.ApplyToControlPosition(this);
            }
        }

        internal sealed class CheckBoxHandle : DynamicControl
        {
            public override States CurrentState
            {
                get { return base.CurrentState; }
                internal set
                {
                    if (CurrentState != value)
                    {
                        switch (BindType)
                        {
                            case BindTypes.HoldActive:

                                switch (value)
                                {
                                    case States.ActiveDown:
                                    case States.ActiveHover:
                                        base.CurrentState = States.ActiveNormal;
                                        break;

                                    case States.Down:
                                    case States.Hover:
                                        base.CurrentState = States.Normal;
                                        break;

                                    default:
                                        base.CurrentState = value;
                                        break;
                                }
                                break;

                            default:
                                base.CurrentState = value;
                                break;
                        }
                    }
                }
            }

            internal BindTypes BindType { get; set; }

            internal CheckBoxHandle(BindTypes bindType) : base(ThemeManager.SpriteType.ControlCheckBox)
            {
                // Initialize properties
                BindType = bindType;

                // Initalize theme specific properties
                OnThemeChange();
            }

            internal override bool CallLeftMouseDown()
            {
                switch (BindType)
                {
                    // Do not allow manual changes
                    case BindTypes.HoldActive:
                        return false;

                    default:
                        return base.CallLeftMouseDown();
                }
            }

            internal override bool CallLeftMouseUp()
            {
                switch (BindType)
                {
                    // Do not allow manual changes
                    case BindTypes.HoldActive:
                        return false;

                    default:
                        return base.CallLeftMouseUp();
                }
            }
        }

        internal sealed class KeyButtonHandle : DynamicControl
        {
            internal const float WidthMultiplier = 0.25f;

            internal Text TextHandle { get; set; }

            public override States CurrentState
            {
                get { return base.CurrentState; }
                internal set
                {
                    base.CurrentState = value;
                    if (TextHandle != null)
                    {
                        TextHandle.Color = CurrentColorModificationValue.Combine(DefaultColorGold);
                    }
                }
            }

            public override bool IsActive
            {
                get { return base.IsActive; }
                set
                {
                    if (base.IsActive != value)
                    {
                        base.IsActive = value;
                        TextHandle.TextValue = value
                            ? BindKeyText
                            : IsFirstButton ? ParentHandle.KeyStrings.Item1 : ParentHandle.KeyStrings.Item2;
                        TextHandle.ApplyToControlPosition(this);

                        // Hook messages to avoid 2 key binds at the same time to be active
                        if (value)
                        {
                            Messages.OnMessage += OnMessage;
                        }
                        else
                        {
                            Messages.OnMessage -= OnMessage;
                        }
                    }
                }
            }

            internal KeyBind ParentHandle { get; set; }

            internal int Width
            {
                get { return (int) (ParentHandle.Width * WidthMultiplier); }
            }

            internal Rectangle _rectangle;
            internal override Rectangle Rectangle
            {
                get { return _rectangle; }
            }

            internal string KeyText
            {
                get { return TextHandle.TextValue; }
                set
                {
                    TextHandle.TextValue = value;
                    TextHandle.ApplyToControlPosition(this);
                }
            }
            internal bool IsFirstButton { get; set; }

            public KeyButtonHandle(string keyText, KeyBind parent, bool isFirstButton = false) : base(ThemeManager.SpriteType.Empty)
            {
                // Initialize properties
                TextObjects.Add(TextHandle = new Text(keyText, DefaultFont)
                {
                    TextAlign = Text.Align.Center,
                    Color = DefaultColorGold
                });
                KeyText = keyText;
                ParentHandle = parent;
                IsFirstButton = isFirstButton;

                // Initalize theme specific properties
                OnThemeChange();
            }

            public override bool Draw()
            {
                // Don't draw base
                //base.Draw();

                return true;
            }

            protected internal override void OnThemeChange()
            {
                // Don't update base
                //base.OnThemeChange();

                // Update fake sizes
                _rectangle = new Rectangle(0, 0, Width, DefaultHeight);
                DynamicRectangle = new Theme.DynamicRectangle
                {
                    Offset = new Vector2(DefaultWidth - (Width * (IsFirstButton ? 2 : 1)), ParentHandle.Height - DefaultHeight)
                };
                Size = new Vector2(Rectangle.Width, Rectangle.Height);
                SizeRectangle = new Rectangle(0, 0, Rectangle.Width, Rectangle.Height);
                UpdateCropRectangle();

                // Update text handle position
                TextHandle.ApplyToControlPosition(this);
            }

            internal void OnMessage(Messages.WindowMessage args)
            {
                switch (args.Message)
                {
                    case WindowMessages.LeftButtonDown:
                        OnMouseDown((Messages.LeftButtonDown) args);
                        break;
                }
            }

            internal void OnMouseDown(Messages.LeftButtonDown args)
            {
                IsActive = IsMouseInside;
            }
        }

        #region Serialization

        public override Dictionary<string, object> Serialize()
        {
            var baseData = base.Serialize();
            //baseData.Add("DisplayName", DisplayName);
            //baseData.Add("DefaultValue", DefaultValue);
            baseData.Add("CurrentValue", CurrentValue);
            //baseData.Add("BindType", BindType);
            baseData.Add("Key1", Keys.Item1);
            baseData.Add("Key2", Keys.Item2);
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
                //DefaultValue = (bool) data["DefaultValue"];
                //BindType = (BindTypes) Convert.ToInt64(data["BindType"]);
                CurrentValue = (bool) data["CurrentValue"];
                Keys = new Tuple<uint, uint>(Convert.ToUInt32(data["Key1"]), Convert.ToUInt32(data["Key2"]));

                return true;
            }
            return false;
        }

        internal static readonly List<string> DeserializationNeededKeys = new List<string>
        {
            //"DisplayName",
            //"DefaultValue",
            //"BindType",
            "CurrentValue",
            "Key1",
            "Key2"
        };

        #endregion
    }
}
