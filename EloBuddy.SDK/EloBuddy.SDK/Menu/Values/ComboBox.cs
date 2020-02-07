using System;
using System.Collections.Generic;
using System.Linq;
using EloBuddy.SDK.Rendering;
using SharpDX;
using Color = System.Drawing.Color;

namespace EloBuddy.SDK.Menu.Values
{
    public sealed class ComboBox : ValueBase<int>
    {
        internal Text TextHandle { get; set; }
        internal ComboBoxHandle ControlHandle { get; set; }
        public OverlayContainer Overlay
        {
            get { return (OverlayContainer) OverlayControl; }
        }

        public override Vector2 Position
        {
            get { return base.Position; }
            internal set
            {
                base.Position = value;

                // Recalculate overlay position
                if (Overlay != null)
                {
                    Overlay.Position = value + Overlay.AlignOffset;
                }
            }
        }

        public override int CurrentValue
        {
            get { return base.CurrentValue; }
            set
            {
                if (value < Overlay.Children.Count)
                {
                    base.CurrentValue = value;
                    ControlHandle.TextHandle.TextValue = this[value];
                }
            }
        }

        public int SelectedIndex
        {
            get { return CurrentValue; }
            set { CurrentValue = value; }
        }

        public string SelectedText
        {
            get { return ControlHandle.TextHandle.TextValue; }
        }

        public string this[int index]
        {
            get { return Overlay.Children[index].TextValue; }
            set { Overlay.Children[index].SetText(value); }
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

        public ComboBox(string displayName, IEnumerable<string> textValues, int defaultIndex = 0) : base(displayName, 30)
        {
            var values = textValues.ToList();

            if (defaultIndex >= values.Count)
            {
                throw new IndexOutOfRangeException("Default index cannot be greater than values count!");
            }

            // Initialize properties
            TextObjects.Add(TextHandle = new Text(displayName, DefaultFont)
            {
                Color = DefaultColorGold,
                TextOrientation = Text.Orientation.Center
            });
            ControlHandle = new ComboBoxHandle(values[defaultIndex]);
            _currentValue = defaultIndex;
            OverlayControl = new OverlayContainer(this)
            {
                IsVisible = false,
                BackgroundColor = Color.FromArgb(200, 14, 27, 25)
            };

            // Add a button to the container for each combo box entry
            foreach (var value in values)
            {
                Overlay.Add(new Button(Button.ButtonType.ComboBoxSub, value));
            }

            // Add handle to base
            Add(ControlHandle);

            // Initalize theme specific properties
            OnThemeChange();

            // Listen to required events
            ControlHandle.OnActiveStateChanged += delegate
            {
                // Set visibility state of the overlay
                Overlay.IsVisible = ControlHandle.IsActive;
            };
            Messages.OnMessage += delegate(Messages.WindowMessage args)
            {
                switch (args.Message)
                {
                    // Collapse combo box on blacklist message
                    case WindowMessages.KeyUp:
                    case WindowMessages.LeftButtonDoubleClick:
                    case WindowMessages.LeftButtonDown:
                    case WindowMessages.MiddleButtonDoubleClick:
                    case WindowMessages.MiddleButtonDown:
                    case WindowMessages.MouseWheel:
                    case WindowMessages.RightButtonDoubleClick:
                    case WindowMessages.RightButtonDown:

                        CloseOverlayOnInteraction(args.Message == WindowMessages.MouseWheel);
                        break;
                }
            };
        }

        public ComboBox(string displayName, int defaultIndex = 0, params string[] textValues)
            : this(displayName, textValues, defaultIndex)
        {
        }

        internal void CloseOverlayOnInteraction(bool scrolling)
        {
            if (ControlHandle.IsVisible && ControlHandle.IsActive &&
                (scrolling || (!ControlHandle.IsInside(Game.CursorPos2D) && !Overlay.IsInside(Game.CursorPos2D))))
            {
                ControlHandle.IsActive = false;
            }
        }

        public void Add(string value)
        {
            Overlay.Add(new Button(Button.ButtonType.ComboBoxSub, value));
        }

        public void Remove(string value)
        {
            var button = Overlay.Children.FirstOrDefault(o => o.TextValue == value);
            if (button != null)
            {
                var index = Overlay.Children.IndexOf(button);
                Overlay.Remove(button);

                if (index < CurrentValue)
                {
                    CurrentValue = CurrentValue;
                }
            }
        }

        public void RemoveAt(int index)
        {
            var button = Overlay.Children[index];
            Overlay.Remove(button);

            if (index < CurrentValue)
            {
                CurrentValue = CurrentValue;
            }
        }

        protected internal override void OnThemeChange()
        {
            // Apply base theme
            base.OnThemeChange();

            // Update combo box handle
            ControlHandle.AlignOffset = new Vector2(Size.X - ControlHandle.Size.X, Size.Y / 2 - ControlHandle.Size.Y / 2); // Right align
            //ControlHandle.AlignOffset = new Vector2(0, (Size.Y - ControlHandle.Size.Y) / 2); // Left align

            // Update overlay offset
            Overlay.AlignOffset = ControlHandle.AlignOffset + new Vector2(0, ControlHandle.Size.Y);
        }

        internal sealed class ComboBoxHandle : DynamicControl
        {
            internal Text TextHandle { get; set; }

            internal ComboBoxHandle(string defaultText) : base(ThemeManager.SpriteType.ControlComboBox)
            {
                // Initialize properties
                TextObjects.Add(TextHandle = new Text(defaultText, DefaultFont)
                {
                    Color = DefaultColorGold
                });

                // Initalize theme specific properties
                OnThemeChange();
            }

            protected internal override void OnThemeChange()
            {
                // Apply base theme
                base.OnThemeChange();

                // Update text position offset
                TextHandle.Padding = new Vector2(15, 0);
                TextHandle.Width = (int) (Size.X - 35);
            }
        }

        public sealed class OverlayContainer : ControlContainer<Button>
        {
            internal static readonly Color BorderColor = Color.FromArgb(255, 162, 140, 99);

            internal static readonly Line BorderLine = new Line
            {
                Color = BorderColor,
                Antialias = false,
                Width = 3
            };

            internal ComboBox Handle { get; set; }

            public override bool IsVisible
            {
                get { return base.IsVisible; }
                set
                {
                    base.IsVisible = value;
                    foreach (var child in Children)
                    {
                        child.IsVisible = value;
                    }
                }
            }

            public OverlayContainer(ComboBox handle) : base(ThemeManager.SpriteType.Empty, cropChildren: false, autoSize: true)
            {
                // Initialzie properties
                Handle = handle;
            }

            protected override Control BaseAdd(Control control)
            {
                var button = (Button) control;
                button.IsVisible = IsVisible;
                button.OnActiveStateChanged += OnActiveStateChanged;
                return base.BaseAdd(button);
            }

            protected override void BaseRemove(Control control)
            {
                var button = (Button) control;
                button.OnActiveStateChanged -= OnActiveStateChanged;
                base.BaseRemove(control);
            }

            internal void OnActiveStateChanged(DynamicControl sender, EventArgs args)
            {
                if (sender.IsActive)
                {
                    var button = (Button) sender;

                    // Update the selected index
                    Handle.SelectedIndex = Children.IndexOf(button);

                    // Set combo box and entry to inactive
                    Handle.ControlHandle.IsActive = false;
                    button.IsActive = false;
                }
            }

            public override bool Draw()
            {
                // Draw base
                if (base.Draw())
                {
                    // Draw border lines
                    BorderLine.ScreenVertices = new[]
                    {
                        Position + new Vector2(1, 0), Position + new Vector2(1, Size.Y), Position + Size + new Vector2(-1, 0), Position + new Vector2(Size.X - 1, 0)
                    };
                    BorderLine.Draw();
                    return true;
                }
                return false;
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
                CurrentValue = Convert.ToInt32(data["CurrentValue"]);

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
