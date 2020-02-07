using System;
using EloBuddy.SDK.Menu.Values;
using EloBuddy.SDK.Rendering;
using EloBuddy.SDK.ThirdParty.Glide;
using SharpDX;

namespace EloBuddy.SDK.Menu
{
    internal class ContainerView : IDisposable
    {
        internal const int MinScrollbarHeight = 20;
        internal const int ScrollbarWidth = 10;

        internal const int ScrollStep = 200;

        internal static readonly Tweener Tweener = new Tweener();
        internal static int _lastUpdate;

        internal static System.Drawing.Color ScrollbarColor
        {
            //get { return System.Drawing.Color.Red; }
            get { return ThemeManager.CurrentTheme.Config.Colors.Scrollbar.DrawingColor; }
        }

        static ContainerView()
        {
            // Initialize fields
            _lastUpdate = Core.GameTickCount;

            // Listen to required events
            Drawing.OnEndScene += delegate
            {
                Tweener.Update((Core.GameTickCount - _lastUpdate) / 1000f);
                _lastUpdate = Core.GameTickCount;
            };
        }

        internal Tween CurrentTween { get; set; }

        internal ControlContainerBase Handle { get; set; }

        internal Rectangle ViewSize { get; set; }
        internal Rectangle ContentSize
        {
            get { return Handle.BoundingRectangle; }
        }
        internal Vector2 Size
        {
            get { return Handle.Size; }
        }
        internal int Crop
        {
            get { return Handle.Crop; }
        }
        internal int _currentViewIndex;
        internal int CurrentViewIndex
        {
            get { return _currentViewIndex; }
            set
            {
                if (_currentViewIndex != value)
                {
                    _currentViewIndex = value;
                    UpdateChildrenCropping();
                }
            }
        }
        internal int MaxViewIndex
        {
            get { return Math.Max(0, ContentSize.Height - ViewSize.Height); }
        }
        internal Rectangle CurrentViewRectangle { get; set; }

        internal bool Tweening { get; set; }
        internal int MoveOffset { get; set; }

        internal bool CanScroll
        {
            get { return Handle.CropChildren && MaxViewIndex > 0; }
        }

        internal Rectangle ScrollbarArea
        {
            get
            {
                return new Rectangle((int) Handle.Position.X + ViewSize.Width - ScrollbarWidth,
                    (int) Handle.Position.Y,
                    ScrollbarWidth,
                    ViewSize.Height);
            }
        }
        internal int ScrollbarHeight
        {
            get { return Math.Max(Math.Min(MinScrollbarHeight, ViewSize.Height), (int) (ScrollbarHeightPercent * ViewSize.Height)); }
        }
        internal int ScrollbarIndex
        {
            get { return (int) Math.Ceiling(-CurrentViewIndex * (ScrollbarHeight / (float) ViewSize.Height)); }
            set
            {
                var index = (int) Math.Min(MaxViewIndex, Math.Max(0, (value / (ScrollbarHeight / (float) ViewSize.Height))));
                CurrentViewIndex = -index;
            }
        }
        internal int ScrollbarMaxIndex { get; set; }
        internal Rectangle ScrollbarPosition
        {
            get
            {
                return new Rectangle((int) Handle.Position.X + ViewSize.Width - ScrollbarWidth,
                    (int) Handle.Position.Y + ScrollbarIndex,
                    ScrollbarWidth,
                    ScrollbarHeight);
            }
        }
        internal float ScrollbarHeightPercent
        {
            get { return Math.Max(Math.Min(MinScrollbarHeight, ViewSize.Height), ViewSize.Height) / (float) ContentSize.Height; }
        }
        internal bool IsScrollbarNeeded
        {
            get { return CanScroll && MaxViewIndex > 0 && !(Handle is ValueBase); }
        }
        internal bool IsScrollbarVisible
        {
            get { return IsScrollbarMoving || ScrollbarArea.IsNear(Game.CursorPos2D, 20); }
        }
        internal bool IsCursorOnScrollbar { get; set; }
        internal bool IsScrollbarMoving { get; set; }

        internal ContainerView(ControlContainerBase container)
        {
            if (container == null)
            {
                throw new ArgumentNullException("container");
            }

            // Initialize properties
            Handle = container;

            // Listen to required events
            Handle.OnMouseMove += OnMouseMove;
            Handle.OnMouseWheel += OnMouseWheel;
            Messages.RegisterEventHandler<Messages.LeftButtonUp>(OnLeftMouseUp);
        }

        internal void OnThemeChange()
        {
            UpdateChildrenCropping();
        }

        internal bool CheckScrollbarDown(Vector2 mousePosition)
        {
            // Check if mouse is on scrollbar
            if (IsScrollbarNeeded && ScrollbarArea.Contains(Game.CursorPos2D))
            {
                // Calculate new move offset
                MoveOffset = (int) (ScrollbarIndex - mousePosition.Y);

                // Allow the scrollbar to move
                IsScrollbarMoving = true;

                // Cancel current tween
                if (CurrentTween != null)
                {
                    CurrentTween.Cancel();
                    Tweening = false;
                }
                return true;
            }

            return false;
        }

        internal void OnMouseMove(Control sender, EventArgs args)
        {
            if (IsScrollbarMoving)
            {
                ScrollbarIndex = (int) (Game.CursorPos2D.Y + MoveOffset);
            }
        }

        internal void OnLeftMouseUp(Messages.LeftButtonUp args)
        {
            // Disable scrolling
            IsScrollbarMoving = false;
        }

        internal void DrawScrollbar()
        {
            if (IsScrollbarNeeded && (Tweening || IsScrollbarVisible))
            {
                var pos = ScrollbarPosition;
                Line.DrawLine(ScrollbarColor, ScrollbarWidth, new Vector2(pos.X + (pos.Width / 2f), pos.Y), new Vector2(pos.X + (pos.Width / 2f), pos.Y + pos.Height));
            }
        }

        internal void OnMouseWheel(Control sender, Messages.MouseWheel args)
        {
            if (!CanScroll)
            {
                return;
            }

            var direction = args.Direction == Messages.MouseWheel.Directions.Down ? 1 : -1;
            var step = args.ScrollSteps * ScrollStep;

            // Don't tween if the new position is the same as the previous position
            var tweenTo = -Math.Min(MaxViewIndex, Math.Max(0, -CurrentViewIndex + direction * step));
            if (tweenTo == CurrentViewIndex)
            {
                return;
            }

            if (CurrentTween != null)
            {
                CurrentTween.Cancel();
            }

            CurrentTween = Tweener.Tween(this, new { CurrentViewIndex = -Math.Min(MaxViewIndex, Math.Max(0, -CurrentViewIndex + direction * step)) }, 0.5f);
            CurrentTween.Ease(Ease.CircOut);
            CurrentTween.OnBegin(delegate { Tweening = true; });
            CurrentTween.OnComplete(delegate { Tweening = false; });
        }

        internal void UpdateViewSize()
        {
            if (Crop == 0)
            {
                ViewSize = new Rectangle(0, 0, (int) Size.X, (int) Size.Y);
            }
            else
            {
                // Validate crop
                if (Crop > 0 && Crop >= Size.Y || Crop < 0 && Crop <= -Size.Y)
                {
                    ViewSize = Rectangle.Empty;
                }
                else if (Crop > 0)
                {
                    ViewSize = new Rectangle(0, Crop, (int) Size.X, (int) Size.Y - Crop);
                }
                else
                {
                    ViewSize = new Rectangle(0, 0, (int) Size.X, (int) Size.Y + Crop);
                }
            }

            if (MaxViewIndex == 0)
            {
                _currentViewIndex = 0;
            }

            CurrentViewRectangle = ViewSize.Add(new Rectangle(0, CurrentViewIndex, 0, 0));
        }

        internal void UpdateChildrenCropping()
        {
            UpdateViewSize();
            if (Handle != MainMenu.Instance)
            {
                Handle.RecalculatePosition();
            }
            UpdateChildrenCropping(Handle);
        }

        internal void UpdateChildrenCropping(ControlContainerBase container)
        {
            if (!container.CropChildren)
            {
                return;
            }

            foreach (var child in container.Children)
            {
                UpdateCropping(child);
                var containerChild = child as ControlContainerBase;
                if (containerChild != null)
                {
                    containerChild.ContainerView.UpdateChildrenCropping();
                }
            }
        }

        internal void UpdateCropping(Control control)
        {
            if (control == MainMenu.Instance || control.ExcludeFromParent)
            {
                return;
            }

            // Not even partial inside
            if (!ViewSize.IsPartialInside(control.RelativePositionRectangle))
            {
                control.Crop = int.MaxValue;
            }
            // Completely inside
            else if (ViewSize.IsCompletlyInside(control.RelativePositionRectangle))
            {
                control.Crop = 0;
            }
            // Partial inside
            else
            {
                control.Crop = control.RelativePosition.Y > ViewSize.Y
                    ? ViewSize.Bottom - control.RelativePositionRectangle.Bottom
                    : -(control.RelativePositionRectangle.Top - ViewSize.Top);
            }

            // Update control position
            control.RecalculatePosition();
        }

        public void Dispose()
        {
            if (Handle != null)
            {
                Handle.OnMouseWheel -= OnMouseWheel;
                Handle = null;
            }
        }
    }
}
