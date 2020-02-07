using System;
using System.Collections.Generic;
using System.Linq;
using EloBuddy.SDK.Rendering;
using SharpDX;

namespace EloBuddy.SDK.Menu.Values
{
    public sealed class Slider : ValueBase<int>
    {
        internal Text TextHandle { get; set; }
        internal Text ValueDisplayHandle { get; set; }
        internal EmptyControl Background { get; set; }
        internal DynamicControl ControlHandle { get; set; }

        public override string DisplayName
        {
            get { return base.DisplayName; }
            set
            {
                base.DisplayName = value;
                TextHandle.TextValue = string.Format(value, CurrentValue, MinValue, MaxValue);
            }
        }

        public override string VisibleName
        {
            get { return TextHandle.DisplayedText; }
        }

        public override int CurrentValue
        {
            get { return base.CurrentValue; }
            set
            {
                var newValue = Math.Max((Math.Min(value, MaxValue)), MinValue);
                if (base.CurrentValue != newValue)
                {
                    base.CurrentValue = Math.Max((Math.Min(value, MaxValue)), MinValue);
                    RecalculateSliderPosition();
                }
            }
        }

        internal int _minValue;
        public int MinValue
        {
            get { return _minValue; }
            set
            {
                _minValue = Math.Min(value, _maxValue);
                RecalculateSliderPosition();

                if (_currentValue < _minValue)
                {
                    CurrentValue = _minValue;
                }
            }
        }
        internal int _maxValue;
        public int MaxValue
        {
            get { return _maxValue; }
            set
            {
                _maxValue = Math.Max(value, _minValue);
                RecalculateSliderPosition();

                if (_currentValue > _maxValue)
                {
                    CurrentValue = _maxValue;
                }
            }
        }

        internal int SliderSize
        {
            get { return (int) Background.Size.Y; }
        }
        internal int SliderWidth
        {
            get { return (int) Background.Size.X - SliderSize; }
        }
        internal Vector2 RelativeOffset
        {
            get { return new Vector2(-(ControlHandle.Size.X / 2f - SliderSize / 2f), -(ControlHandle.Size.Y / 2f - SliderSize / 2f)); }
        }

        internal int MoveOffset { get; set; }

        public Slider(string displayName, int defaultValue = 0, int minValue = 0, int maxValue = 100)
            : base(displayName, DefaultHeight + ThemeManager.CurrentTheme.Config.SpriteAtlas.Backgrounds.Slider.Height)
        {
            // Initialize properties
            TextObjects.AddRange(new[]
            {
                TextHandle = new Text(displayName, DefaultFont)
                {
                    TextOrientation = Text.Orientation.Top,
                    Color = DefaultColorGold
                },
                ValueDisplayHandle = new Text("placeholder", DefaultFont)
                {
                    TextOrientation = Text.Orientation.Top,
                    TextAlign = Text.Align.Right,
                    Color = DefaultColorGold
                }
            });
            Background = new EmptyControl(ThemeManager.SpriteType.BackgroundSlider) { DrawBase = true };
            ControlHandle = new SliderHandle();
            _minValue = Math.Min(minValue, maxValue);
            _maxValue = Math.Max(minValue, maxValue);
            CurrentValue = defaultValue;

            // Add handle to base
            Add(Background);
            Add(ControlHandle);

            // Listen to required events
            Background.OnLeftMouseDown += delegate
            {
                if (!ControlHandle.IsLeftMouseDown && Background.IsInside(Game.CursorPos2D))
                {
                    MoveOffset = 0 - SliderSize / 2;
                    RecalculateSliderPosition((int) Game.CursorPos2D.X - SliderSize / 2);
                    ControlHandle.IsLeftMouseDown = true;
                }
            };
            ControlHandle.OnLeftMouseDown += delegate { MoveOffset = (int) ((ControlHandle.Position.X - RelativeOffset.X) - Game.CursorPos2D.X); };
            ControlHandle.OnActiveStateChanged += delegate
            {
                if (ControlHandle.IsActive)
                {
                    ControlHandle.IsActive = false;
                }
            };

            // Initalize theme specific properties
            OnThemeChange();
        }

        protected internal override void OnThemeChange()
        {
            // Apply base theme
            base.OnThemeChange();

            // Update height
            Height = DefaultHeight + (int) Background.Size.Y;

            // Update text offset
            TextHandle.Padding = new Vector2((Width - Background.Size.X) / 4, (DefaultHeight - TextHandle.Bounding.Height) / 2f);
            TextHandle.ApplyToControlPosition(this);
            ValueDisplayHandle.Padding = new Vector2(-TextHandle.Padding.X, TextHandle.Padding.Y);
            ValueDisplayHandle.ApplyToControlPosition(this);

            // Update aligns
            Background.AlignOffset = new Vector2(Size.X / 2 - Background.Size.X / 2, Size.Y - Background.Size.Y);
            RecalculateSliderPosition();
        }

        internal void RecalculateSliderPosition(int posX = -1)
        {
            var offset = RelativeOffset + Background.AlignOffset;
            if (MaxValue - MinValue == 0)
            {
                ControlHandle.AlignOffset = offset;
            }
            else if (posX > 0)
            {
                if (posX <= Background.Position.X)
                {
                    CurrentValue = MinValue;
                    ControlHandle.AlignOffset = offset;
                }
                else if (posX >= Background.Position.X + SliderWidth)
                {
                    CurrentValue = MaxValue;
                    ControlHandle.AlignOffset = offset + new Vector2(SliderWidth, 0);
                }
                else
                {
                    CurrentValue = (int) Math.Round(MinValue + ((posX - Background.Position.X) / SliderWidth) * (MaxValue - MinValue));
                    ControlHandle.AlignOffset = offset + new Vector2(((float) SliderWidth / (MaxValue - MinValue)) * (CurrentValue - MinValue), 0);
                }
            }
            else
            {
                if (CurrentValue == MinValue)
                {
                    ControlHandle.AlignOffset = offset;
                }
                else if (CurrentValue == MaxValue)
                {
                    ControlHandle.AlignOffset = offset + new Vector2(SliderWidth, 0);
                }
                else
                {
                    ControlHandle.AlignOffset = offset + new Vector2(((float) SliderWidth / (MaxValue - MinValue)) * (CurrentValue - MinValue), 0);
                }
            }

            if (DisplayName.Contains("{"))
            {
                TextHandle.TextValue = string.Format(DisplayName, CurrentValue, MinValue, MaxValue);
            }
            ValueDisplayHandle.TextValue = string.Format("{0}/{1}", CurrentValue, MaxValue);
            ValueDisplayHandle.ApplyToControlPosition(this);
        }

        public override bool Draw()
        {
            if (ControlHandle.IsLeftMouseDown)
            {
                RecalculateSliderPosition((int) Game.CursorPos2D.X + MoveOffset);
            }

            // Draw base
            return base.Draw();
        }

        internal sealed class SliderHandle : DynamicControl
        {
            public SliderHandle() : base(ThemeManager.SpriteType.ControlSlider)
            {
                // Initalize theme specific properties
                OnThemeChange();
            }

            public override bool IsInside(Vector2 position)
            {
                if ((int) Size.Y + CropRectangle.Height == 0)
                {
                    return false;
                }

                var newSize = ThemeManager.CurrentTheme.Config.SpriteAtlas.Backgrounds.Slider.Height;
                var overlapping = (int) Math.Max(0, (ThemeManager.CurrentTheme.Config.SpriteAtlas.Controls.Slider.Height - newSize) / 2f);
                var newCrop = Crop == 0 ? 0 : Crop > 0 ? Crop - overlapping : Crop + overlapping;
                var pos = Position + new Vector2((ThemeManager.CurrentTheme.Config.SpriteAtlas.Controls.Slider.Height - newSize) / 2f);

                return new Rectangle((int) pos.X, (int) pos.Y + (newCrop > 0 ? newCrop : 0), newSize, newSize + (newCrop < 0 ? newCrop : 0)).IsInside(position);
            }

            internal override bool CallMouseLeave()
            {
                if (IsVisible)
                {
                    return IsLeftMouseDown || base.CallMouseLeave();
                }
                return false;
            }

            internal override bool CallLeftMouseUp()
            {
                if (base.CallLeftMouseUp())
                {
                    return !IsLeftMouseDown || base.CallMouseLeave();
                }
                return false;
            }

            internal override bool CallMouseEnter()
            {
                if (IsLeftMouseDown && IsMouseInside)
                {
                    return false;
                }
                return base.CallMouseEnter();
            }
        }

        #region Serialization

        public override Dictionary<string, object> Serialize()
        {
            var baseData = base.Serialize();
            //baseData.Add("DisplayName", DisplayName);
            baseData.Add("CurrentValue", CurrentValue);
            //baseData.Add("MinValue", MinValue);
            //baseData.Add("MaxValue", MaxValue);
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
                //MinValue = Convert.ToInt32(data["MinValue"]);
                //MaxValue = Convert.ToInt32(data["MaxValue"]);
                CurrentValue = Convert.ToInt32(data["CurrentValue"]);

                return true;
            }
            return false;
        }

        internal static readonly List<string> DeserializationNeededKeys = new List<string>
        {
            //"DisplayName",
            //"MinValue",
            //"MaxValue",
            "CurrentValue"
        };

        #endregion
    }
}
