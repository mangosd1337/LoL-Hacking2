using System.Collections.Generic;
using System.Drawing;
using System.IO;
using System.Runtime.Serialization;
using System.Runtime.Serialization.Json;
using System.Security.Permissions;
using System.Text;
using SharpDX;
using SharpDX.Direct3D9;
using Rectangle = SharpDX.Rectangle;

namespace EloBuddy.SDK.Menu
{
    public sealed class Theme
    {
        public const string ThemeFileNaming = "theme.png";
        public const string ConfigFileNaming = "config.json";

        internal static readonly DataContractJsonSerializer ThemeSerializer = new DataContractJsonSerializer(typeof (ThemeConfig));

        public string Name { get; internal set; }
        internal string TextureRef { get; set; }
        internal Texture Texture
        {
            get { return MainMenu.TextureLoader[TextureRef]; }
        }
        public ThemeConfig Config { get; set; }

        internal Theme(string name, string textureRef, ThemeConfig positions)
        {
            Name = name;
            TextureRef = textureRef;
            Config = positions;
        }

        [PermissionSet(SecurityAction.Assert, Unrestricted = true)]
        public static Theme FromFolder(string folderPath)
        {
            // Check if the folder exists
            if (!Directory.Exists(folderPath))
            {
                throw new FileNotFoundException(folderPath);
            }

            // Check if the theme file exists
            var themePath = string.Concat(folderPath, Path.DirectorySeparatorChar, ThemeFileNaming);
            if (!File.Exists(themePath))
            {
                throw new FileNotFoundException(themePath);
            }

            // Check if the atlas file exists
            var atlasPath = string.Concat(folderPath, Path.DirectorySeparatorChar, ConfigFileNaming);
            if (!File.Exists(atlasPath))
            {
                throw new FileNotFoundException(atlasPath);
            }

            // Load the theme texture as bitmap
            var bitmap = (Bitmap) Image.FromFile(themePath);

            // Load the atlas
            ThemeConfig themeConfig;
            using (var reader = File.OpenText(atlasPath))
            {
                using (var stream = new MemoryStream(Encoding.UTF8.GetBytes(reader.ReadToEnd())))
                {
                    themeConfig = (ThemeConfig) ThemeSerializer.ReadObject(stream);
                }
            }

            // Convert the bitmap to texture
            string textureRef;
            MainMenu.TextureLoader.Load(bitmap, out textureRef);

            // Return the theme
            return new Theme(Path.GetDirectoryName(folderPath), textureRef, themeConfig);
        }

        [PermissionSet(SecurityAction.Assert, Unrestricted = true)]
        public static Theme FromMemory(string name, Bitmap bitmap, string themeAtlas)
        {
            // Load the atlas
            ThemeConfig themeConfig;

            using (var stream = new MemoryStream(Encoding.Default.GetBytes(themeAtlas)))
            {
                themeConfig = (ThemeConfig) ThemeSerializer.ReadObject(stream);
            }

            // Convert the bitmap to texture
            string textureRef;
            MainMenu.TextureLoader.Load(bitmap, out textureRef);

            // Return the theme
            return new Theme(name, textureRef, themeConfig);
        }

        // ReSharper disable InconsistentNaming
        [DataContract]
        public class ThemeConfig
        {
            [DataMember(Order = 0, IsRequired = true)]
            public Colors Colors { get; internal set; }
            [DataMember(Order = 1, IsRequired = true)]
            public SpriteAtlas SpriteAtlas { get; internal set; }

            public ThemeConfig()
            {
                // Initialize properties
                Colors = new Colors();
                SpriteAtlas = new SpriteAtlas();
            }
        }

        [DataContract]
        public class SpriteAtlas
        {
            [DataMember(Order = 0, IsRequired = true)]
            public MainForm MainForm { get; internal set; }
            [DataMember(Order = 1, IsRequired = true)]
            public Backgrounds Backgrounds { get; internal set; }
            [DataMember(Order = 2, IsRequired = true)]
            public Controls Controls { get; internal set; }

            public SpriteAtlas()
            {
                // Initialize properties
                MainForm = new MainForm();
                Backgrounds = new Backgrounds();
                Controls = new Controls();
            }
        }

        [DataContract]
        public class Colors
        {
            [DataMember(Order = 0, IsRequired = true)]
            internal Color Scrollbar;
            [DataMember(Order = 1, IsRequired = true)]
            internal Color ScrollbarBackground;
        }

        [DataContract]
        public struct Color
        {
            [DataMember(Order = 0, IsRequired = true)]
            public byte A;
            [DataMember(Order = 1, IsRequired = true)]
            public byte R;
            [DataMember(Order = 2, IsRequired = true)]
            public byte G;
            [DataMember(Order = 3, IsRequired = true)]
            public byte B;

            public System.Drawing.Color DrawingColor
            {
                get { return System.Drawing.Color.FromArgb(A, R, G, B); }
            }
        }

        [DataContract]
        public class MainForm
        {
            [DataMember(Order = 0, IsRequired = true)]
            public StaticRectangle Complete { get; internal set; }
            [DataMember(Order = 1, IsRequired = true)]
            public StaticRectangle Header { get; internal set; }
            [DataMember(Order = 2, IsRequired = true)]
            public StaticRectangle Footer { get; internal set; }
            [DataMember(Order = 3, IsRequired = true)]
            public StaticRectangle AddonButtonContainer { get; internal set; }
            [DataMember(Order = 4, IsRequired = true)]
            public StaticRectangle ContentHeader { get; internal set; }
            [DataMember(Order = 5, IsRequired = true)]
            public StaticRectangle ContentContainer { get; internal set; }

            public MainForm()
            {
                Complete = new StaticRectangle();
                Header = new StaticRectangle();
                Footer = new StaticRectangle();
                AddonButtonContainer = new StaticRectangle();
                ContentHeader = new StaticRectangle();
                ContentContainer = new StaticRectangle();
            }
        }

        [DataContract]
        public class Controls
        {
            [DataMember(Order = 0, IsRequired = true)]
            public Buttons Buttons { get; internal set; }

            [DataMember(Order = 1, IsRequired = true)]
            public DynamicRectangle CheckBox { get; internal set; }
            [DataMember(Order = 2, IsRequired = true)]
            public DynamicRectangle Slider { get; internal set; }
            [DataMember(Order = 3, IsRequired = true)]
            public DynamicRectangle ComboBox { get; internal set; }

            public Controls()
            {
                // Initialize properties
                Buttons = new Buttons();
                CheckBox = new DynamicRectangle();
                Slider = new DynamicRectangle();
                ComboBox = new DynamicRectangle();
            }
        }

        [DataContract]
        public class Buttons
        {
            [DataMember(Order = 0, IsRequired = true)]
            public DynamicRectangle Exit { get; internal set; }
            [DataMember(Order = 1, IsRequired = true)]
            public DynamicRectangle Addon { get; internal set; }
            [DataMember(Order = 2, IsRequired = true)]
            public DynamicRectangle Normal { get; internal set; }
            [DataMember(Order = 3, IsRequired = true)]
            public DynamicRectangle Confirm { get; internal set; }
            [DataMember(Order = 4, IsRequired = true)]
            public DynamicRectangle Mini { get; internal set; }

            public Buttons()
            {
                // Initialize properties
                Exit = new DynamicRectangle();
                Addon = new DynamicRectangle();
                Normal = new DynamicRectangle();
                Confirm = new DynamicRectangle();
                Mini = new DynamicRectangle();
            }
        }

        [DataContract]
        public class Backgrounds
        {
            [DataMember(Order = 0, IsRequired = true)]
            public StaticRectangle Slider { get; internal set; }
            [DataMember(Order = 1, IsRequired = true)]
            public StaticRectangle ScrollBar { get; internal set; }

            public Backgrounds()
            {
                // Initialize properties
                Slider = new StaticRectangle();
                ScrollBar = new StaticRectangle();
            }
        }

        [DataContract]
        public class StaticRectangle
        {
            [DataMember(Order = 0, IsRequired = true)]
            public int X;
            [DataMember(Order = 1, IsRequired = true)]
            public int Y;
            [DataMember(Order = 2, IsRequired = true)]
            public int Width;
            [DataMember(Order = 3, IsRequired = true)]
            public int Height;

            [DataMember(Order = 4, IsRequired = false)]
            public Vector2 Offset = Vector2.Zero;

            internal Rectangle? _rectangle;
            public Rectangle Rectangle
            {
                get { return _rectangle ?? (_rectangle = new Rectangle(X, Y, Width, Height)).Value; }
            }
        }

        [DataContract]
        internal class DynamicRectangleEntry
        {
            [DataMember(Order = 0, IsRequired = true)]
            internal int X;
            [DataMember(Order = 1, IsRequired = true)]
            internal int Y;

            internal bool IsEmpty { get; set; }
            internal Rectangle Rectangle { get; set; }
        }

        [DataContract]
        public class DynamicRectangle
        {
            [DataMember(Order = 0, IsRequired = true)]
            public int Width;
            [DataMember(Order = 1, IsRequired = true)]
            public int Height;

            [DataMember(Order = 2, IsRequired = false)]
            internal Vector2 Offset = Vector2.Zero;

            [DataMember(Order = 3, IsRequired = false)]
            internal DynamicRectangleEntry Normal { get; set; }
            [DataMember(Order = 4, IsRequired = false)]
            internal DynamicRectangleEntry Hover { get; set; }
            [DataMember(Order = 5, IsRequired = false)]
            internal DynamicRectangleEntry Down { get; set; }
            [DataMember(Order = 6, IsRequired = false)]
            internal DynamicRectangleEntry ActiveNormal { get; set; }
            [DataMember(Order = 7, IsRequired = false)]
            internal DynamicRectangleEntry ActiveHover { get; set; }
            [DataMember(Order = 8, IsRequired = false)]
            internal DynamicRectangleEntry ActiveDown { get; set; }
            [DataMember(Order = 9, IsRequired = false)]
            internal DynamicRectangleEntry Disabled { get; set; }

            internal Dictionary<DynamicControl.States, DynamicRectangleEntry> Entries { get; set; }

            [OnDeserialized]
            private void OnDeserialized(StreamingContext context)
            {
                // Initialize properties
                Normal = Normal ?? new DynamicRectangleEntry { IsEmpty = true };
                Hover = Hover ?? new DynamicRectangleEntry { IsEmpty = true };
                Down = Down ?? new DynamicRectangleEntry { IsEmpty = true };
                ActiveNormal = ActiveNormal ?? new DynamicRectangleEntry { IsEmpty = true };
                ActiveHover = ActiveHover ?? new DynamicRectangleEntry { IsEmpty = true };
                ActiveDown = ActiveDown ?? new DynamicRectangleEntry { IsEmpty = true };
                Disabled = Disabled ?? new DynamicRectangleEntry { IsEmpty = true };

                Entries = new Dictionary<DynamicControl.States, DynamicRectangleEntry>
                {
                    { DynamicControl.States.Normal, Normal },
                    { DynamicControl.States.Hover, Hover },
                    { DynamicControl.States.Down, Down },
                    { DynamicControl.States.ActiveNormal, ActiveNormal },
                    { DynamicControl.States.ActiveHover, ActiveHover },
                    { DynamicControl.States.ActiveDown, ActiveDown },
                    { DynamicControl.States.Disabled, Disabled }
                };

                // Initialize rectangles
                foreach (var entry in Entries.Values)
                {
                    entry.Rectangle = entry.IsEmpty ? Rectangle.Empty : new Rectangle(entry.X, entry.Y, Width, Height);
                }
            }

            public Rectangle GetRectangle(DynamicControl.States state)
            {
                return Entries.ContainsKey(state) ? Entries[state].Rectangle : Rectangle.Empty;
            }
        }

        // ReSharper enable InconsistentNaming
    }
}
