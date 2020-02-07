using System;
using System.Collections.Generic;
using System.Linq;
using EloBuddy.SDK.Rendering;
using SharpDX;

namespace EloBuddy.SDK.Menu
{
    public abstract class Control
    {
        public delegate void ControlHandler(Control sender, EventArgs args);

        // Events
        public event ControlHandler OnMouseEnter;
        public event ControlHandler OnMouseLeave;
        public event ControlHandler OnMouseMove;
        public event ControlHandler OnLeftMouseDown;
        public event ControlHandler OnRightMouseDown;
        public event ControlHandler OnLeftMouseUp;
        public event ControlHandler OnRightMouseUp;

        internal static readonly List<Control> OverlayControls = new List<Control>();

        internal static bool IsOnOverlay(Vector2 mousePosition)
        {
            return OverlayControls.Any(o => o.IsVisible && o.IsInside(mousePosition));
        }

        // The parent of the control (only used with inside of a ControlContainer)
        internal ControlContainerBase Parent { get; set; }

        // Sprite handle of the MainMenu
        internal Sprite Sprite
        {
            get { return MainMenu.Sprite; }
        }

        // Screen position of the control
        internal Vector2 _position;
        public virtual Vector2 Position
        {
            get { return _position; }
            internal set
            {
                _position = value;

                // Update text positions
                foreach (var text in TextObjects)
                {
                    text.ApplyToControlPosition(this);
                }
            }
        }

        // Offset relative to the parent content container position
        public virtual Vector2 Offset
        {
            get { return StaticRectangle.Offset; }
        }

        // Used by ControlContainers only
        internal Vector2 _alignOffset;
        internal Vector2 AlignOffset
        {
            get { return _alignOffset; }
            set
            {
                if (_alignOffset != value)
                {
                    _alignOffset = value;
                    RecalculatePosition();
                }
            }
        }
        internal virtual bool ExcludeFromParent { get; set; }

        internal Control _overlayControl;
        protected internal Control OverlayControl
        {
            get { return _overlayControl; }
            set
            {
                if (_overlayControl != value)
                {
                    if (_overlayControl != null)
                    {
                        _overlayControl.IsOverlay = false;
                        OverlayControls.Remove(_overlayControl);
                    }
                    _overlayControl = value;
                    if (_overlayControl != null)
                    {
                        _overlayControl.IsOverlay = true;
                        OverlayControls.Add(_overlayControl);
                    }
                }
            }
        }
        protected internal virtual bool IsOverlay
        {
            get { return OverlayControls.Contains(this); }
            internal set
            {
                if (value)
                {
                    if (!IsOverlay)
                    {
                        OverlayControls.Add(this);
                    }
                }
                else
                {
                    OverlayControls.Remove(this);
                }
            }
        }

        // Static rectangle of the control
        internal Theme.StaticRectangle StaticRectangle { get; set; }

        internal virtual Rectangle Rectangle
        {
            get { return Type == ThemeManager.SpriteType.FormContentContainer ? StaticRectangle.Rectangle.Add(new Rectangle(0, 0, ContainerView.ScrollbarWidth, 0)) : StaticRectangle.Rectangle; }
        }
        internal Rectangle CropRectangle { get; set; }
        internal Rectangle DrawingRectangle
        {
            get { return Rectangle.Height == 0 ? Rectangle : Rectangle.Add(CropRectangle); }
        }

        // Size of the control
        public Vector2 Size { get; internal set; }
        public Rectangle SizeRectangle { get; internal set; }

        // Screen position of the control
        public Vector2 RelativePosition
        {
            get { return Parent == null ? Vector2.Zero : Offset + AlignOffset + new Vector2(0, Parent.ContainerView.CurrentViewIndex); }
        }
        public Rectangle RelativePositionRectangle
        {
            get { return Parent == null ? Rectangle.Empty : new Rectangle((int) RelativePosition.X, (int) RelativePosition.Y, (int) Size.X, (int) Size.Y); }
        }
        internal Vector2 MinPos
        {
            get { return Position; }
        }
        internal Vector2 MaxPos
        {
            get { return Position + Size; }
        }

        internal int _crop;
        public virtual int Crop
        {
            get { return _crop; }
            internal set
            {
                // Apply value to field
                _crop = value;

                // Update CropRectangle
                if (!Size.IsZero)
                {
                    UpdateCropRectangle();
                }
            }
        }
        internal bool FullCrop { get; set; }

        // Current type of the control
        internal ThemeManager.SpriteType Type { get; set; }

        internal bool _isVisible = true;
        public virtual bool IsVisible
        {
            get { return _isVisible; }
            set
            {
                if (_isVisible != value)
                {
                    _isVisible = value;
                    if (!value)
                    {
                        IsLeftMouseDown = false;
                        IsRightMouseDown = false;
                        IsMouseInside = false;
                    }
                }
            }
        }

        public bool IsMouseInside { get; internal set; }
        public bool IsLeftMouseDown { get; internal set; }
        public bool IsRightMouseDown { get; internal set; }

        // Sets whether to draw the background image or not
        internal bool _drawBase = true;
        internal bool DrawBase
        {
            get { return _drawBase; }
            set { _drawBase = value; }
        }

        protected internal readonly List<Text> TextObjects = new List<Text>();

        protected Control(ThemeManager.SpriteType type)
        {
            // Apply properties
            Type = type;
        }

        protected internal virtual void OnThemeChange()
        {
            // Update theme related things
            StaticRectangle = ThemeManager.GetStaticRectangle(this);
            Size = new Vector2(Rectangle.Width, Rectangle.Height);
            SizeRectangle = new Rectangle(0, 0, Rectangle.Width, Rectangle.Height);
            UpdateCropRectangle();

            // Update TextObject positions to current position
            TextObjects.ForEach(o => o.ApplyToControlPosition(this));
        }

        internal virtual void SetParent(ControlContainerBase parent)
        {
            if (parent == null)
            {
                Parent = null;
                AlignOffset = Vector2.Zero;
                RecalculatePosition();
            }
            else
            {
                Parent = parent;
            }
        }

        internal virtual void RecalculatePosition()
        {
            Position = (Parent == null ? Vector2.Zero : Parent.Position) + RelativePosition;
        }

        internal void UpdateCropRectangle()
        {
            if (Crop == 0)
            {
                FullCrop = false;
                CropRectangle = Rectangle.Empty;
                return;
            }

            // Validate crop
            if (Crop > 0 && Crop >= Size.Y ||
                Crop < 0 && Crop <= -Size.Y)
            {
                FullCrop = true;
                CropRectangle = new Rectangle(0, 0, (int) -Size.X, (int) -Size.Y);
                return;
            }

            FullCrop = false;
            CropRectangle = Crop > 0
                ? new Rectangle(0, Crop, 0, -Crop)
                : new Rectangle(0, 0, 0, Crop);
        }

        public virtual bool IsInside(Vector2 position)
        {
            if ((int) Size.Y + CropRectangle.Height == 0)
            {
                return false;
            }

            var pos = position - Position;
            var newY = CropRectangle.Y == 0 ? 0 : (CropRectangle.Y > 0 ? CropRectangle.Y : 0);
            return pos.X >= 0 && pos.Y >= newY && pos.X < Size.X && pos.Y < newY + (Size.Y + CropRectangle.Height);
        }

        public virtual bool Draw()
        {
            if (IsVisible && !FullCrop)
            {
                // Check wheather to draw the background or not
                if (!DrawBase)
                {
                    return true;
                }

                // Draw the control
                if (Crop == 0)
                {
                    if (Rectangle.Height > 0)
                    {
                        Sprite.Draw(Position, Rectangle);
                    }
                    return true;
                }

                // Get the rectangle with cropping
                var rectangle = DrawingRectangle;
                if (rectangle.Height > 0)
                {
                    // Draw the control
                    Sprite.Draw(Position + new Vector2(0, Crop > 0 ? Crop : 0), rectangle);
                    return true;
                }

                // For empty backgrounds
                return Parent != null && Parent.ContainerView.ViewSize.IsPartialInside(RelativePositionRectangle);
            }

            return false;
        }

        internal virtual bool CallMouseEnter()
        {
            if (IsVisible)
            {
                if (OnMouseEnter != null)
                {
                    OnMouseEnter(this, EventArgs.Empty);
                }
                return true;
            }
            return false;
        }

        internal virtual bool CallMouseLeave()
        {
            if (IsVisible)
            {
                IsLeftMouseDown = false;
                IsRightMouseDown = false;
                if (OnMouseLeave != null)
                {
                    OnMouseLeave(this, EventArgs.Empty);
                }
                return true;
            }
            return false;
        }

        internal virtual bool CallMouseMove()
        {
            if (IsVisible)
            {
                if (OnMouseMove != null)
                {
                    OnMouseMove(this, EventArgs.Empty);
                }
                return true;
            }
            return false;
        }

        internal virtual bool CallLeftMouseDown()
        {
            if (IsVisible)
            {
                if (OnLeftMouseDown != null)
                {
                    OnLeftMouseDown(this, EventArgs.Empty);
                }
                return true;
            }
            return false;
        }

        internal virtual bool CallRightMouseDown()
        {
            if (IsVisible)
            {
                if (OnRightMouseDown != null)
                {
                    OnRightMouseDown(this, EventArgs.Empty);
                }
                return true;
            }
            return false;
        }

        internal virtual bool CallLeftMouseUp()
        {
            if (IsVisible)
            {
                if (OnLeftMouseUp != null)
                {
                    OnLeftMouseUp(this, EventArgs.Empty);
                }
                return true;
            }
            return false;
        }

        internal virtual bool CallRightMouseUp()
        {
            if (IsVisible)
            {
                if (OnRightMouseUp != null)
                {
                    OnRightMouseUp(this, EventArgs.Empty);
                }
                return true;
            }
            return false;
        }

        protected internal virtual bool OnKeyDown(Messages.KeyDown args)
        {
            return true;
        }

        protected internal virtual bool OnKeyUp(Messages.KeyUp args)
        {
            return true;
        }
    }
}
