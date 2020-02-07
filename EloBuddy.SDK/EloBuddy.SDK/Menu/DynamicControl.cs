using System;
using System.Collections.Generic;
using System.Linq;
using SharpDX;
using Color = System.Drawing.Color;

namespace EloBuddy.SDK.Menu
{
    public abstract class DynamicControl : Control
    {
        public enum States
        {
            ActiveDown = 1,
            ActiveHover = 2,
            ActiveNormal = 3,
            Down = 4,
            Hover = 5,
            Disabled = 6,
            Normal = 0
        }

        public delegate void DynamicControlHandler(DynamicControl sender, EventArgs args);

        // Events
        public event DynamicControlHandler OnStateChanged;
        public event DynamicControlHandler OnActiveStateChanged;

        internal static readonly Dictionary<States, ColorModificationValue> ColorModificationValues = new Dictionary<States, ColorModificationValue>
        {
            { States.Normal, new ColorModificationValue() },
            { States.Hover, new ColorModificationValue { R = 50, G = 50, B = 50 } },
            { States.Down, new ColorModificationValue { R = 50, G = 50, B = 50 } },
            { States.ActiveNormal, new ColorModificationValue { R = 100, G = 100, B = 100 } },
            { States.ActiveHover, new ColorModificationValue { R = 100, G = 100, B = 100 } },
            { States.ActiveDown, new ColorModificationValue { R = 100, G = 100, B = 100 } },
            { States.Disabled, new ColorModificationValue { Gray = true } }
        };

        internal ColorModificationValue CurrentColorModificationValue
        {
            get { return ColorModificationValues[CurrentState]; }
        }

        internal States _currentState;
        public virtual States CurrentState
        {
            get { return _currentState; }
            internal set
            {
                if (_currentState != value)
                {
                    _currentState = value;
                    UpdateCropRectangle();

                    if (OnStateChanged != null)
                    {
                        OnStateChanged(this, EventArgs.Empty);
                    }
                }
            }
        }

        internal bool _isActive;
        public virtual bool IsActive
        {
            get { return _isActive; }
            set
            {
                if (_isActive != value)
                {
                    _isActive = value;
                    if (IsMouseInside)
                    {
                        if (IsLeftMouseDown)
                        {
                            CurrentState = value ? States.ActiveDown : States.Down;
                        }
                        else
                        {
                            CurrentState = value ? States.ActiveHover : States.Hover;
                        }
                    }
                    else
                    {
                        CurrentState = value ? States.ActiveNormal : States.Normal;
                    }

                    if (OnActiveStateChanged != null)
                    {
                        OnActiveStateChanged(this, EventArgs.Empty);
                    }
                }
            }
        }

        // Offset relative to the parent content container position
        public override Vector2 Offset
        {
            get { return DynamicRectangle.Offset; }
        }

        // Dynamic rectangle of the control
        internal Theme.DynamicRectangle DynamicRectangle { get; set; }

        internal override Rectangle Rectangle
        {
            get { return DynamicRectangle.GetRectangle(CurrentState); }
        }

        internal bool _isDisabled;
        public bool IsDisabled
        {
            get { return _isDisabled; }
            set
            {
                if (_isDisabled != value)
                {
                    _isDisabled = value;
                    CurrentState = value ? States.Disabled : States.Normal;
                }
            }
        }

        protected DynamicControl(ThemeManager.SpriteType type)
            : base(type)
        {
        }

        internal virtual void SetDefaultState()
        {
            if (IsDisabled)
            {
                CurrentState = States.Disabled;
            }
            else
            {
                CurrentState = IsActive ? States.ActiveNormal : States.Normal;
            }
        }

        protected internal override void OnThemeChange()
        {
            DynamicRectangle = ThemeManager.GetDynamicRectangle(this);
            var rectangle =
                Enum.GetValues(typeof (States))
                    .Cast<States>()
                    .Select(state => DynamicRectangle.GetRectangle(state))
                    .FirstOrDefault(rect => !rect.IsEmpty);
            Size = new Vector2(rectangle.Width, rectangle.Height);
            SizeRectangle = new Rectangle(0, 0, rectangle.Width, rectangle.Height);
            UpdateCropRectangle();

            // Update TextObject positions to current position
            TextObjects.ForEach(o => o.ApplyToControlPosition(this));
        }

        internal override bool CallLeftMouseDown()
        {
            if (!IsDisabled && base.CallLeftMouseDown())
            {
                CurrentState = IsActive ? States.ActiveDown : States.Down;
                return true;
            }
            return false;
        }

        internal override bool CallMouseEnter()
        {
            if (!IsDisabled && base.CallMouseEnter())
            {
                CurrentState = IsActive ? States.ActiveHover : States.Hover;
                return true;
            }
            return false;
        }

        internal override bool CallMouseLeave()
        {
            if (!IsDisabled && base.CallMouseLeave())
            {
                CurrentState = IsActive ? States.ActiveNormal : States.Normal;
                return true;
            }
            return false;
        }

        internal override bool CallMouseMove()
        {
            return !IsDisabled && base.CallMouseMove();
        }

        internal override bool CallLeftMouseUp()
        {
            if (!IsDisabled && base.CallLeftMouseUp())
            {
                IsActive = !IsActive;
                return true;
            }
            return false;
        }

        internal struct ColorModificationValue
        {
            internal byte A;
            internal byte R;
            internal byte G;
            internal byte B;

            internal bool Gray;

            internal bool IsNull
            {
                get { return A == 0 && R == 0 && G == 0 && B == 0; }
            }

            internal Color Combine(Color color)
            {
                return Gray ? Color.DimGray : Color.FromArgb(Validate(color.A + A), Validate(color.R + R), Validate(color.G + G), Validate(color.B + B));
            }

            internal byte Validate(int value)
            {
                return (byte) Math.Max(0, Math.Min(255, value));
            }
        }
    }
}
