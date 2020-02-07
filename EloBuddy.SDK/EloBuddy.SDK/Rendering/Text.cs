using System;
using System.Collections.Generic;
using System.Drawing.Text;
using System.IO;
using System.Linq;
using System.Runtime.InteropServices;
using System.Security.Permissions;
using EloBuddy.SDK.Menu;
using EloBuddy.SDK.Properties;
using EloBuddy.SDK.Utils;
using SharpDX;
using SharpDX.Direct3D9;
using Color = System.Drawing.Color;

namespace EloBuddy.SDK.Rendering
{
    public sealed class Text : IDisposable
    {
        [DllImport("gdi32.dll")]
        internal static extern int AddFontResourceEx(string lpszFilename, uint fl, IntPtr pdv);

        [DllImport("gdi32.dll")]
        public static extern IntPtr AddFontMemResourceEx(IntPtr pbFont, uint cbFont, IntPtr pdv, [In] ref uint pcFonts);

        [DllImport("gdi32.dll")]
        [return: MarshalAs(UnmanagedType.Bool)]
        public static extern bool RemoveFontMemResourceEx(IntPtr fh);

        internal static readonly string FontDirectoryPath = string.Concat(DefaultSettings.EloBuddyPath, Path.DirectorySeparatorChar, "Fonts");

        internal static readonly List<byte[]> DefaultFonts = new List<byte[]>
        {
            Resources.Gill_Sans_MT_Light,
            Resources.Gill_Sans_MT_Pro_Book,
            Resources.Gill_Sans_MT_Pro_Medium,
        };

        internal static readonly string[] ValidFontEndings =
        {
            ".fon",
            ".fnt",
            ".ttf",
            ".ttc",
            ".fot",
            ".otf",
            ".mmm",
            ".pfb",
            ".pfm"
        };

        internal static readonly List<IntPtr> FontHandles = new List<IntPtr>();
        internal static readonly PrivateFontCollection PrivateFonts = new PrivateFontCollection();
        public static List<string> CustomFonts
        {
            get { return new List<string>(PrivateFonts.Families.Select(o => o.Name)); }
        }



        [PermissionSet(SecurityAction.Assert, Unrestricted = true)]
        static Text()
        {
            try
            {
                // Load all default fonts
                foreach (var memFont in DefaultFonts)
                {
                    LoadFont(memFont);
                }

                // Load all custom fonts
                Directory.CreateDirectory(FontDirectoryPath);
                foreach (var file in from file in Directory.GetFiles(FontDirectoryPath) from ending in ValidFontEndings.Where(file.EndsWith) select file)
                {
                    using (var fontStream = new FileStream(file, FileMode.Open, FileAccess.ReadWrite))
                    {
                        var fontData = new byte[fontStream.Length];
                        fontStream.Read(fontData, 0, fontData.Length);
                        LoadFont(fontData);
                    }
                }

                // Listen to required events
                AppDomain.CurrentDomain.DomainUnload += OnStaticUnload;
                AppDomain.CurrentDomain.ProcessExit += OnStaticUnload;

                // Debug print loaded fonts
                Logger.Info("Loaded a total of {0} fonts:", PrivateFonts.Families.Length);
                foreach (var font in PrivateFonts.Families)
                {
                    Logger.Info(" -> {0}", font.Name);
                }
            }
            catch (Exception e)
            {
                Console.WriteLine(e);
                OnStaticUnload(null, EventArgs.Empty);
            }
        }

        [PermissionSet(SecurityAction.Assert, Unrestricted = true)]
        internal static unsafe void LoadFont(byte[] fontData)
        {
            fixed (byte* ptr = fontData)
            {
                // Load font into PrivateFontCollection
                var intPtr = new IntPtr(ptr);
                PrivateFonts.AddMemoryFont(intPtr, fontData.Length);

                // Load font into project
                uint resourceCount = 1;
                FontHandles.Add(AddFontMemResourceEx(intPtr, (uint) fontData.Length, IntPtr.Zero, ref resourceCount));
            }
        }

        [PermissionSet(SecurityAction.Assert, Unrestricted = true)]
        internal static void OnStaticUnload(object sender, EventArgs e)
        {
            // Unload loaded fonts
            if (FontHandles != null && FontHandles.Count > 0)
            {
                foreach (var intPtr in FontHandles)
                {
                    RemoveFontMemResourceEx(intPtr);
                }
                FontHandles.Clear();
            }
        }

        internal SharpDX.Direct3D9.Sprite SpriteHandle
        {
            get { return Sprite.Handle; }
        }
        internal Font TextHandle { get; set; }
        internal bool IsDrawing
        {
            get { return Sprite.IsDrawing; }
            set { Sprite.IsDrawing = value; }
        }

        public enum Align
        {
            Left,
            Center,
            Right
        }

        public enum Orientation
        {
            Center,
            Top,
            Bottom
        }

        public FontDescription Description
        {
            get { return TextHandle.Description; }
        }

        internal string _textValue;
        public string TextValue
        {
            get { return _textValue; }
            set
            {
                if (_textValue != value)
                {
                    _textValue = value;
                    RecalculateBoundingAndDisplayedText();
                }
            }
        }
        public string DisplayedText { get; internal set; }

        internal Vector2 _position;
        public Vector2 Position
        {
            get { return _position; }
            set
            {
                if (_position != value)
                {
                    _position = value;
                    RecalculatePositionRectangle();
                }
            }
        }
        internal Vector2 _size = new Vector2(10000, 10000);
        public Vector2 Size
        {
            get { return _size; }
            set
            {
                _size = value;
                RecalculateBoundingAndDisplayedText();
            }
        }
        public int X
        {
            get { return (int) Position.X; }
            set { Position = new Vector2(value, Position.Y); }
        }
        public int Y
        {
            get { return (int) Position.Y; }
            set { Position = new Vector2(Position.X, value); }
        }
        public int Width
        {
            get { return (int) Size.X; }
            set
            {
                if (value != Width)
                {
                    Size = new Vector2(value, Size.Y);
                }
            }
        }
        public int Height
        {
            get { return (int) Size.Y; }
            set
            {
                if (value != Height)
                {
                    Size = new Vector2(Size.X, value);
                }
            }
        }
        public Rectangle PositionRectangle { get; internal set; }

        public Align TextAlign { get; set; }
        public Orientation TextOrientation { get; set; }

        internal FontDrawFlags _drawFlags = FontDrawFlags.Left | FontDrawFlags.Top;
        public FontDrawFlags DrawFlags
        {
            get { return _drawFlags; }
            set { _drawFlags = value; }
        }

        internal ColorBGRA _color = SharpDX.Color.White;
        public Color Color
        {
            get { return Color.FromArgb(_color.A, _color.R, _color.G, _color.B); }
            set { _color = new ColorBGRA(value.R, value.G, value.B, value.A); }
        }

        internal Vector2 _padding;
        public Vector2 Padding
        {
            get { return _padding; }
            set
            {
                if (_padding != value)
                {
                    _padding = value;
                    RecalculatePositionRectangle();
                }
            }
        }

        internal int _crop;
        internal int Crop
        {
            get { return _crop; }
            set
            {
                if (_crop != value)
                {
                    _crop = value;
                    RecalculatePositionRectangle();
                }
            }
        }
        internal bool FullCrop { get; set; }

        public Rectangle Bounding { get; internal set; }
        public Rectangle BoundingIgnoredSizeAndFlags { get; internal set; }

        public Text()
        {
            //Using Default properties
            RegisterEventHandlers();
        }

        public Text(string textValue, System.Drawing.Font font)
        {
            // Initialize properties
            _textValue = textValue;
            ReplaceFont(font);
            RegisterEventHandlers();
        }

        public Text(string textValue, FontDescription fontDescription)
        {
            // Initialize properties
            _textValue = textValue;
            ReplaceFont(fontDescription);
            RegisterEventHandlers();
        }

        public Text(
            string textValue,
            int height,
            int width,
            FontWeight weight,
            int mipLevels,
            bool isItalic,
            FontCharacterSet characterSet,
            FontPrecision precision,
            FontQuality quality,
            FontPitchAndFamily pitchAndFamily,
            string faceName)
        {
            // Initialize properties
            _textValue = textValue;
            ReplaceFont(height, width, weight, mipLevels, isItalic, characterSet, precision, quality, pitchAndFamily, faceName);
            RegisterEventHandlers();
        }

        public void Draw()
        {
            if (FullCrop)
            {
                return;
            }

            if (!IsDrawing)
            {
                Core.EndAllDrawing(Core.RenderingType.Sprite);
                SpriteHandle.Begin();
                IsDrawing = true;
            }

            // Draw the text
            TextHandle.DrawText(SpriteHandle, DisplayedText, PositionRectangle, DrawFlags, _color);
        }

        public void Draw(string text, SharpDX.Color color, int x, int y)
        {
            if (!IsDrawing)
            {
                Core.EndAllDrawing(Core.RenderingType.Sprite);
                SpriteHandle.Begin();
                IsDrawing = true;
            }

            // Draw the text
            TextHandle.DrawText(SpriteHandle, text, x, y, color);
        }

        public void Draw(string text, Color color, int x, int y)
        {
            // Draw the text
            Draw(text, new ColorBGRA(color.R, color.G, color.B, color.A), x, y);
        }

        public void Draw(string text, Color color, Vector2 screenPosition)
        {
            // Draw the text
            Draw(text, new ColorBGRA(color.R, color.G, color.B, color.A), (int)screenPosition.X, (int)screenPosition.Y);
        }

        public void Draw(string text, Color color, Obj_AI_Base obj, int extraX, int extraY)
        {
            // Draw the text
            var screenPos = obj.Position.WorldToScreen();
            var pos = new Vector2(screenPos.X + extraX, screenPos.Y+ extraY);
            Draw(text, new ColorBGRA(color.R, color.G, color.B, color.A), (int)pos.X, (int)pos.Y);
        }


        public void Draw(string text, SharpDX.Color color, params Vector2[] positions)
        {
            if (!IsDrawing)
            {
                Core.EndAllDrawing(Core.RenderingType.Sprite);
                SpriteHandle.Begin();
                IsDrawing = true;
            }

            // Draw the text
            foreach (var pos in positions)
            {
                TextHandle.DrawText(SpriteHandle, text, (int) pos.X, (int) pos.Y, new ColorBGRA(color.R, color.G, color.B, color.A));
            }
        }

        public void Draw(string text, Color color, params Vector2[] positions)
        {
            // Draw the text
            Draw(text, new ColorBGRA(color.R, color.G, color.B, color.A), positions);
        }

        public void Draw(string text, SharpDX.Color color, params Rectangle[] positions)
        {
            if (!IsDrawing)
            {
                Core.EndAllDrawing(Core.RenderingType.Sprite);
                SpriteHandle.Begin();
                IsDrawing = true;
            }

            // Draw the text
            foreach (var pos in positions)
            {
                TextHandle.DrawText(SpriteHandle, text, pos, DrawFlags, new ColorBGRA(color.R, color.G, color.B, color.A));
            }
        }

        public void Draw(string text, Color color, params Rectangle[] positions)
        {
            // Draw the text
            Draw(text, new ColorBGRA(color.R, color.G, color.B, color.A), positions);
        }

        public void ApplyToControlPosition(Control control)
        {
            float offsetX = 0;
            float offsetY = 0;
            switch (TextAlign)
            {
                case Align.Center:
                    offsetX = (control.Size.X - BoundingIgnoredSizeAndFlags.Width) / 2;
                    break;
                case Align.Right:
                    offsetX = control.Size.X - BoundingIgnoredSizeAndFlags.Width;
                    break;
            }
            switch (TextOrientation)
            {
                case Orientation.Center:
                    offsetY = (control.Size.Y - BoundingIgnoredSizeAndFlags.Height) / 2;
                    break;
                case Orientation.Bottom:
                    offsetY = control.Size.Y - BoundingIgnoredSizeAndFlags.Height;
                    break;
            }
            var alignOffset = new Vector2(offsetX, offsetY);

            // Update text position
            Position = new Vector2(control.Position.X, control.Position.Y) + Padding + alignOffset;

            // Update text max width
            Width = (int) (control.Size.X - (Position.X - control.Position.X));

            // Adjust crop to control crop
            if (control.FullCrop)
            {
                Crop = int.MaxValue;
            }
            else if (control.Crop != 0)
            {
                var space = Padding.Y + alignOffset.Y;
                if (control.Crop > 0)
                {
                    Crop = (int) Math.Max(0, control.Crop - space);
                }
                else
                {
                    space = control.Size.Y - (space + Bounding.Height);
                    Crop = (int) Math.Min(0, control.Crop + space);
                }
            }
            else
            {
                // Set crop to 0 if control has no cropping aswell
                Crop = 0;
            }
        }

        public void ReplaceFont(System.Drawing.Font font)
        {
            if (TextHandle != null)
            {
                TextHandle.Dispose();
            }
            TextHandle = new Font(Drawing.Direct3DDevice, font);
            RecalculateBoundingAndDisplayedText();
        }

        public void ReplaceFont(FontDescription fontDescription)
        {
            if (TextHandle != null)
            {
                TextHandle.Dispose();
            }
            TextHandle = new Font(Drawing.Direct3DDevice, fontDescription);
            RecalculateBoundingAndDisplayedText();
        }

        public void ReplaceFont(
            int height,
            int width,
            FontWeight weight,
            int mipLevels,
            bool isItalic,
            FontCharacterSet characterSet,
            FontPrecision precision,
            FontQuality quality,
            FontPitchAndFamily pitchAndFamily,
            string faceName)
        {
            if (TextHandle != null)
            {
                TextHandle.Dispose();
            }
            TextHandle = new Font(Drawing.Direct3DDevice, height, width, weight, mipLevels, isItalic, characterSet, precision, quality, pitchAndFamily, faceName);
            RecalculateBoundingAndDisplayedText();
        }

        public Rectangle MeasureBounding()
        {
            return MeasureBounding(TextValue, PositionRectangle, DrawFlags);
        }

        public Rectangle MeasureBounding(string text, Rectangle? maxSize = null, FontDrawFlags? flags = null)
        {
            return !maxSize.HasValue
                ? TextHandle.MeasureText(SpriteHandle, text, flags ?? FontDrawFlags.NoClip)
                : TextHandle.MeasureText(SpriteHandle, text, maxSize.Value, flags ?? FontDrawFlags.WordBreak);
        }

        internal void RecalculatePositionRectangle()
        {
            FullCrop = false;
            if (Crop == 0)
            {
                PositionRectangle = new Rectangle((int) Position.X, (int) Position.Y, Bounding.Width, Bounding.Height);
            }
            else
            {
                // Validate crop
                if (Crop >= Height || Crop <= -Height)
                {
                    FullCrop = true;
                    PositionRectangle = new Rectangle((int) Position.X, (int) Position.Y, 0, 0);
                }
                else if (Crop > 0)
                {
                    // Cropping top
                    PositionRectangle = new Rectangle((int) Position.X, (int) Position.Y + Crop, Bounding.Width, Bounding.Height - Crop);
                    DrawFlags = FontDrawFlags.Bottom | FontDrawFlags.Center;
                }
                else
                {
                    // Cropping bottom
                    PositionRectangle = new Rectangle((int) Position.X, (int) Position.Y, Bounding.Width, Bounding.Height + Crop);
                    DrawFlags = FontDrawFlags.Top | FontDrawFlags.Center;
                }

                if (PositionRectangle.Height <= 0)
                {
                    FullCrop = true;
                }
            }
        }

        internal void RecalculateBoundingAndDisplayedText()
        {
            BoundingIgnoredSizeAndFlags = TextHandle.MeasureText(SpriteHandle, TextValue, FontDrawFlags.NoClip);
            DisplayedText = TextValue;
            Bounding = BoundingIgnoredSizeAndFlags;
            if (Bounding.Width - 4 > Width)
            {
                while (Bounding.Width - 4 > Width && DisplayedText.Length > 3)
                {
                    DisplayedText = string.Concat(DisplayedText.Substring(0, DisplayedText.Length - 4), "...");
                    Bounding = TextHandle.MeasureText(SpriteHandle, DisplayedText, DrawFlags);
                }
            }

            // Recalculate the position rectangle
            RecalculatePositionRectangle();

            // Preload the text
            TextHandle.PreloadText(DisplayedText);
        }

        internal void RegisterEventHandlers()
        {
            Drawing.OnPreReset += OnPreReset;
            Drawing.OnPostReset += OnPostReset;
            AppDomain.CurrentDomain.ProcessExit += OnUnload;
            AppDomain.CurrentDomain.DomainUnload += OnUnload;
        }

        internal void OnPreReset(EventArgs args)
        {
            TextHandle.OnLostDevice();
        }

        internal void OnPostReset(EventArgs args)
        {
            TextHandle.OnResetDevice();
        }

        internal void OnUnload(object sender, EventArgs e)
        {
            Dispose();
        }

        public void Dispose()
        {
            if (TextHandle != null)
            {
                TextHandle.Dispose();
                TextHandle = null;
            }

            Drawing.OnPreReset -= OnPreReset;
            Drawing.OnPostReset -= OnPostReset;
            AppDomain.CurrentDomain.ProcessExit -= OnUnload;
            AppDomain.CurrentDomain.DomainUnload -= OnUnload;
        }
    }
}
