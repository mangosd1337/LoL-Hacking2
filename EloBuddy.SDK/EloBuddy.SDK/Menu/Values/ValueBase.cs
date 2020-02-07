using System;
using System.Collections.Generic;
using System.Text;
using SharpDX;
using SharpDX.Direct3D9;

namespace EloBuddy.SDK.Menu.Values
{
    public abstract class ValueBase<T> : ValueBase, IValue<T>
    {
        public delegate void ValueChangeHandler(ValueBase<T> sender, ValueChangeArgs args);

        public class ValueChangeArgs : EventArgs
        {
            public T OldValue { get; internal set; }
            public T NewValue { get; internal set; }

            internal ValueChangeArgs(T oldValue, T newValue)
            {
                // Initialize properties
                OldValue = oldValue;
                NewValue = newValue;
            }
        }

        public event ValueChangeHandler OnValueChange;

        // The parent of the control (only used with inside of a ValueContainer)
        internal new ValueContainer Parent
        {
            get { return (ValueContainer) base.Parent; }
            set { base.Parent = value; }
        }

        internal T _currentValue;
        public virtual T CurrentValue
        {
            get { return _currentValue; }
            set
            {
                var args = new ValueChangeArgs(_currentValue, value);
                _currentValue = value;
                if (OnValueChange != null)
                {
                    OnValueChange(this, args);
                }
            }
        }

        public override string SerializationId
        {
            get { return _serializationId ?? (_serializationId = GenerateUniqueSerializationId()); }
        }
        public override bool ShouldSerialize
        {
            get { return true; }
        }

        protected internal ValueBase(string serializationId, string displayName, int height) : base(displayName, height)
        {
            // Initialize SerializationId
            _serializationId = serializationId;
        }

        protected internal ValueBase(string displayName, int height) : base(displayName, height)
        {
        }

        internal string GenerateUniqueSerializationId()
        {
            var id = GenerateUniqueSerializationId(Parent);
            return id.Length > 0 ? string.Concat(id, ".", DisplayName) : DisplayName;
        }

        internal string GenerateUniqueSerializationId(ControlContainerBase parentContainer)
        {
            var stringBuilder = new StringBuilder();
            if (parentContainer.Parent != null)
            {
                stringBuilder.Append(GenerateUniqueSerializationId(parentContainer.Parent));
                stringBuilder.Append(".");
            }
            return stringBuilder.Append(parentContainer.GetType().Name).ToString();
        }

        public override Dictionary<string, object> Serialize()
        {
            return new Dictionary<string, object>
            {
                { "SerializationId", SerializationId },
                { "Type", GetType().FullName }
            };
        }

        protected internal override bool ApplySerializedData(Dictionary<string, object> data)
        {
            // Check if the serialization Id and the type matches
            return data.ContainsKey("SerializationId") && (string) data["SerializationId"] == SerializationId &&
                   data.ContainsKey("Type") && (string) data["Type"] == GetType().FullName;
        }
    }

    public abstract class ValueBase : ControlContainer<Control>
    {
        // TODO: Known issue if Height == Sprite Height, nothing being cropped then!
        protected internal const int DefaultHeight = 25;
        protected internal static int DefaultWidth
        {
            get { return ThemeManager.CurrentTheme.Config.SpriteAtlas.MainForm.ContentContainer.Width; }
        }

        protected internal static readonly FontDescription DefaultFont = new FontDescription
        {
            FaceName = ThemeManager.DefaultFontFaceName,
            Height = 16
        };
        protected internal static readonly System.Drawing.Color DefaultColorGold = System.Drawing.Color.FromArgb(255, 143, 122, 72);
        protected internal static readonly System.Drawing.Color DefaultColorGreen = System.Drawing.Color.FromArgb(255, 44, 99, 94);

        internal string _serializationId;
        public abstract string SerializationId { get; }
        public abstract bool ShouldSerialize { get; }

        internal string _displayName;
        public virtual string DisplayName
        {
            get { return _displayName; }
            set { _displayName = value; }
        }
        public abstract string VisibleName { get; }

        internal int _height;
        protected internal int Height
        {
            get { return _height; }
            set
            {
                if (_height != value)
                {
                    _height = value;
                    OnThemeChange();
                }
            }
        }
        protected internal virtual int Width
        {
            get { return DefaultWidth; }
        }

        protected internal ValueBase(string displayName, int height)
            : base(ThemeManager.SpriteType.Empty, false, cropChildren: true)
        {
            // Initialize properties
            _displayName = displayName;
            _height = height;
        }

        public T Cast<T>() where T : ValueBase
        {
            return (T) this;
        }

        protected internal override void OnThemeChange()
        {
            // Don't update base
            //base.OnThemeChange();

            // Update theme related things
            StaticRectangle = new Theme.StaticRectangle
            {
                X = ThemeManager.CurrentTheme.Config.SpriteAtlas.MainForm.ContentContainer.X,
                Y = ThemeManager.CurrentTheme.Config.SpriteAtlas.MainForm.ContentContainer.Y,
                Width = Width,
                Height = Height,
                Offset = new Vector2(0, 7)
            };
            Size = new Vector2(Rectangle.Width, Rectangle.Height);
            SizeRectangle = new Rectangle(0, 0, Rectangle.Width, Rectangle.Height);
            UpdateCropRectangle();

            // Update container view
            ContainerView.OnThemeChange();

            // Update all cilds
            foreach (var child in Children)
            {
                child.OnThemeChange();
            }
        }

        internal override bool CallMouseWheel(Messages.MouseWheel args)
        {
            // Base values do not have scroll bars
            return Parent != null && Parent.CallMouseWheel(args);
        }

        public abstract Dictionary<string, object> Serialize();

        protected internal abstract bool ApplySerializedData(Dictionary<string, object> data);
    }
}
