using System;
using System.Collections.Generic;
using System.Linq;
using EloBuddy.SDK.Rendering;
using SharpDX;

namespace EloBuddy.SDK.Menu
{
    public interface IControlContainer<T> where T : Control
    {
        T Add(T control);
        void Remove(T control);
    }

    public abstract class ControlContainerBase : Control
    {
        public event MouseWheelHandler OnMouseWheel;

        // Events
        public delegate void MouseWheelHandler(Control sender, Messages.MouseWheel args);

        protected internal System.Drawing.Color? BackgroundColor { get; set; }

        protected internal override bool IsOverlay
        {
            get { return base.IsOverlay; }
            internal set
            {
                if (base.IsOverlay != value)
                {
                    base.IsOverlay = value;
                    foreach (var child in Children)
                    {
                        child.IsOverlay = value;
                    }
                }
            }
        }

        // The offset to add the screen position of the container base to
        internal virtual Vector2 DrawOffset { get; set; }
        internal ContainerView ContainerView { get; set; }

        public override Vector2 Position
        {
            get { return base.Position; }
            internal set
            {
                base.Position = value;

                // Update child positions
                foreach (var child in Children)
                {
                    child.RecalculatePosition();
                }
            }
        }

        internal int Padding { get; set; }

        internal List<Control> _baseChildren;
        internal ControlList<Control> _children;
        internal ControlList<Control> Children
        {
            get { return _children ?? (_children = new ControlList<Control>(ref _baseChildren)); }
        }

        internal bool _stackAlign;
        internal bool StackAlign
        {
            get { return _stackAlign; }
            set
            {
                if (_stackAlign != value)
                {
                    _stackAlign = value;
                    RecalculateAlignOffsets();
                }
            }
        }

        internal bool _cropChildren;
        internal bool CropChildren
        {
            get { return _cropChildren; }
            set
            {
                if (_cropChildren != value)
                {
                    _cropChildren = value;
                    if (value)
                    {
                        ContainerView.UpdateChildrenCropping();
                    }
                    else
                    {
                        foreach (var child in Children)
                        {
                            child.Crop = 0;
                        }
                    }
                }
            }
        }

        public override int Crop
        {
            get { return base.Crop; }
            internal set
            {
                if (base.Crop != value)
                {
                    base.Crop = value;
                    ContainerView.UpdateChildrenCropping();
                }
            }
        }

        internal bool _autoSize;
        protected internal bool AutoSize
        {
            get { return _autoSize; }
            set
            {
                if (_autoSize != value)
                {
                    _autoSize = value;
                    if (!value)
                    {
                        // Since it's no longer auto size mode we need to get the original spirte size again
                        OnThemeChange();
                    }
                }
            }
        }

        internal Rectangle BoundingRectangle { get; set; }

        internal static bool DrawOutline { get; set; }
        internal bool OutlineReady { get; set; }

        protected ControlContainerBase(ThemeManager.SpriteType type, bool stackAlign = true, bool drawBase = false, bool cropChildren = true, bool autoSize = false)
            : base(type)
        {
            // Initialize properties
            ContainerView = new ContainerView(this);
            _baseChildren = new List<Control>();
            _stackAlign = stackAlign;
            DrawBase = drawBase;
            _cropChildren = cropChildren;
            _autoSize = autoSize;

#if DEBUG
            DrawOutline = true;
            Drawing.OnFlushEndScene += delegate
            {
                // Debug
                if (DrawOutline && OutlineReady)
                {
                    OutlineReady = false;
                    Rendering.Line.DrawLine(System.Drawing.Color.Red, 1,
                        Position,
                        Position + new Vector2(Size.X, 0),
                        Position + Size,
                        Position + new Vector2(0, Size.Y),
                        Position);
                }
            };
#endif
        }

        internal virtual void RecalculateAlignOffsets()
        {
            // Calculate the align offset for each control
            if (!StackAlign)
            {
                foreach (var child in Children.Where(child => !child.ExcludeFromParent))
                {
                    child.AlignOffset = Vector2.Zero;
                }
            }
            else
            {
                var currentHeight = 0;
                foreach (var child in Children.Where(child => !child.ExcludeFromParent))
                {
                    child.AlignOffset = new Vector2(0, currentHeight);
                    currentHeight += Padding + (int) child.Size.Y;
                }
            }

            // Recalculate Bounding
            RecalculateBounding();

            // Recalculate Cropping
            ContainerView.UpdateChildrenCropping();
        }

        internal virtual void RecalculateBounding()
        {
            // Calculate the bounding of the control container
            var min = Vector2.Zero;
            var max = Vector2.Zero;
            foreach (var child in Children.Where(child => !child.ExcludeFromParent))
            {
                if (child.RelativePosition.Y < min.Y)
                {
                    min = child.RelativePosition;
                }
                if ((child.RelativePosition + child.Size).Y > max.Y)
                {
                    max = child.RelativePosition + child.Size;
                }
            }
            BoundingRectangle = new Rectangle(0, (int) min.Y, (int) Size.Y, (int) max.Y);
        }

        protected internal override void OnThemeChange()
        {
            // Update base if not auto size mode
            if (!AutoSize)
            {
                base.OnThemeChange();
            }

            // Recalculate size
            RecalculateSize();

            // Update ContainerView
            ContainerView.OnThemeChange();

            // Update all cilds
            foreach (var child in Children)
            {
                child.OnThemeChange();
            }
        }

        public override bool Draw()
        {
            if (IsVisible)
            {
                // Draw the background color (if set)
                if (BackgroundColor.HasValue)
                {
                    var rect = !AutoSize ? (Crop == 0 ? Rectangle : DrawingRectangle) : new Rectangle(0, 0, (int) Size.X, (int) Size.Y);
                    if (rect.Height > 0)
                    {
                        var pos = Crop == 0 ? Position : Position + new Vector2(0, Crop > 0 ? Crop : 0);
                        Line.DrawLine(BackgroundColor.Value, rect.Width, pos + new Vector2(rect.Width / 2f, 0), pos + new Vector2(rect.Width / 2f, rect.Height));
                    }
                }

                // Draw the base background
                if (!DrawBase || base.Draw())
                {
                    // Draw the children and the according text objects
                    foreach (var child in Children.Where(child => !child.ExcludeFromParent).Where(child => child.Draw()))
                    {
                        child.TextObjects.ForEach(o => o.Draw());
                    }

                    // Draw the scrollbar (if needed)
                    ContainerView.DrawScrollbar();

                    OutlineReady = true;
                    return true;
                }
            }

            return false;
        }

        protected virtual Control BaseAdd(Control control)
        {
            if (control == null)
            {
                throw new ArgumentNullException("control");
            }

            if (!Children.Contains(control))
            {
                control.SetParent(this);
                Children.Add(control);
                RecalculateAlignOffsets();

                if (IsOverlay)
                {
                    control.IsOverlay = true;
                }
            }

            RecalculateSize();

            return control;
        }

        protected virtual void BaseRemove(Control control)
        {
            if (control == null)
            {
                throw new ArgumentNullException("control");
            }

            control.SetParent(null);
            Children.Remove(control);
            RecalculateAlignOffsets();

            if (IsOverlay)
            {
                control.IsOverlay = false;
            }

            RecalculateSize();
        }

        protected void RecalculateSize()
        {
            if (!AutoSize)
            {
                return;
            }

            var width = 0f;
            var height = 0f;

            foreach (var child in Children)
            {
                if (child.Size.X + child.AlignOffset.X > width)
                {
                    width = child.Size.X + child.AlignOffset.X;
                }
                if (child.Size.Y + child.AlignOffset.Y > height)
                {
                    height = child.Size.Y + child.AlignOffset.Y;
                }
            }

            Size = new Vector2(width, height) - 1;
        }

        internal virtual bool CallMouseWheel(Messages.MouseWheel args)
        {
            if (IsVisible)
            {
                if (OnMouseWheel != null)
                {
                    OnMouseWheel(this, args);
                }
                return true;
            }
            return false;
        }

        protected internal override bool OnKeyDown(Messages.KeyDown args)
        {
            if (base.OnKeyDown(args))
            {
                foreach (var child in Children)
                {
                    child.OnKeyDown(args);
                }
                return true;
            }
            return false;
        }

        protected internal override bool OnKeyUp(Messages.KeyUp args)
        {
            if (base.OnKeyUp(args))
            {
                foreach (var child in Children)
                {
                    child.OnKeyUp(args);
                }
                return true;
            }
            return false;
        }
    }

    public abstract class ControlContainer : ControlContainerBase, IControlContainer<Control>
    {
        protected ControlContainer(ThemeManager.SpriteType type, bool stackAlign = true, bool drawBase = false, bool cropChildren = true, bool autoSize = false)
            : base(type, stackAlign, drawBase, cropChildren, autoSize)
        {
        }

        public Control Add(Control control)
        {
            return BaseAdd(control);
        }

        public void Remove(Control control)
        {
            BaseRemove(control);
        }
    }

    public abstract class ControlContainer<T> : ControlContainerBase, IControlContainer<T> where T : Control
    {
        internal ControlList<T> _typedChildren;
        public new ControlList<T> Children
        {
            get { return _typedChildren ?? (_typedChildren = new ControlList<T>(ref _baseChildren)); }
        }

        protected ControlContainer(ThemeManager.SpriteType type, bool stackAlign = true, bool drawBase = false, bool cropChildren = true, bool autoSize = false)
            : base(type, stackAlign, drawBase, cropChildren, autoSize)
        {
        }

        public T Add(T control)
        {
            return (T) BaseAdd(control);
        }

        public void Remove(T control)
        {
            BaseRemove(control);
        }
    }

    public sealed class SimpleControlContainer : ControlContainer
    {
        public SimpleControlContainer(ThemeManager.SpriteType type, bool stackAlign = true, bool drawBase = false, bool cropChildren = true, bool autoSize = false)
            : base(type, stackAlign, drawBase, cropChildren, autoSize)
        {
            // Initalize theme specific properties
            OnThemeChange();
        }
    }

    public sealed class SimpleControlContainer<T> : ControlContainer<T> where T : Control
    {
        public SimpleControlContainer(ThemeManager.SpriteType type, bool stackAlign = true, bool drawBase = false, bool cropChildren = true, bool autoSize = false)
            : base(type, stackAlign, drawBase, cropChildren, autoSize)
        {
            // Initalize theme specific properties
            OnThemeChange();
        }
    }
}
