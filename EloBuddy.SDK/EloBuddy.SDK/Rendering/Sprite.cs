using System;
using EloBuddy.SDK.Utils;
using SharpDX;
using SharpDX.Direct3D9;
using Color = System.Drawing.Color;

namespace EloBuddy.SDK.Rendering
{
    /// <summary>
    /// Easy to draw sprite class.
    /// </summary>
    public sealed class Sprite
    {
        internal delegate void MenuDrawHandler(EventArgs args);

        internal static event MenuDrawHandler OnMenuDraw;

        internal static SharpDX.Direct3D9.Sprite Handle { get; set; }
        internal static bool IsDrawing { get; set; }
        internal static bool IsOnEndScene { get; set; }

        static Sprite()
        {
            // Initialize properties
            Handle = new SharpDX.Direct3D9.Sprite(Drawing.Direct3DDevice);

            // Listen to events
            AppDomain.CurrentDomain.DomainUnload += OnAppDomainUnload;
            AppDomain.CurrentDomain.ProcessExit += OnAppDomainUnload;
            Drawing.OnEndScene += OnEndScene;
            Drawing.OnFlushEndScene += OnFlush;
            Drawing.OnPreReset += OnPreReset;
            Drawing.OnPostReset += OnPostReset;
        }

        internal static void OnPreReset(EventArgs args)
        {
            Handle.OnLostDevice();
        }

        internal static void OnPostReset(EventArgs args)
        {
            Handle.OnResetDevice();
        }

        internal static void OnAppDomainUnload(object sender, EventArgs eventArgs)
        {
            if (Handle != null)
            {
                Core.EndAllDrawing();
                Handle.Dispose();
                Handle = null;
            }
        }

        internal static void OnEndScene(EventArgs args)
        {
            IsOnEndScene = true;
        }

        internal static void OnFlush(EventArgs args)
        {
            if (IsOnEndScene && OnMenuDraw != null)
            {
                IsOnEndScene = false;
                OnMenuDraw(EventArgs.Empty);
            }

            if (IsDrawing)
            {
                Handle.End();
                IsDrawing = false;
            }
        }

        internal Func<Texture> _textureDelegate;
        internal Texture _texture;
        public Texture Texture
        {
            get { return _texture ?? _textureDelegate(); }
            internal set { _texture = value; }
        }

        internal ColorBGRA _colorBrga = SharpDX.Color.White;
        internal Color _color = Color.White;
        public Color Color
        {
            get { return _color; }
            set
            {
                _color = value;
                _colorBrga = new ColorBGRA(value.R, value.G, value.B, value.A);
            }
        }

        public Rectangle? Rectangle { get; set; }
        public float? Rotation { get; set; }
        public Vector2? Scale { get; set; }
        public Vector3? CenterRef { get; set; }

        public Sprite(Texture texture)
        {
            // Initialize properties
            Texture = texture;
        }

        public Sprite(Func<Texture> textureDelegate)
        {
            // Initialize properties
            _textureDelegate = textureDelegate;
        }

        /// <summary>
        /// Draws the sprite on the screen
        /// </summary>
        /// <param name="position">The position to draw at</param>
        public void Draw(Vector2 position)
        {
            Draw(position, Rectangle);
        }

        /// <summary>
        /// Draws the sprite on the screen
        /// </summary>
        /// <param name="position">The position to draw at</param>
        /// <param name="rectangle">The rectangle to draw from</param>
        public void Draw(Vector2 position, Rectangle? rectangle)
        {
            Draw(position, rectangle, CenterRef, Rotation, Scale);
        }

        /// <summary>
        /// Draws the sprite on the screen
        /// </summary>
        /// <param name="position">The position to draw at</param>
        /// <param name="rectangle">The rectangle to draw from</param>
        /// <param name="centerRef">The center reference of the sprite</param>
        /// <param name="rotation">The rotation in radians</param>
        /// <param name="scale">The scale</param>
        public void Draw(Vector2 position, Rectangle? rectangle, Vector3? centerRef, float? rotation = null, Vector2? scale = null)
        {
            if (Handle == null || Handle.IsDisposed)
            {
                return;
            }

            if (!IsDrawing)
            {
                Core.EndAllDrawing(Core.RenderingType.Sprite);
                Handle.Begin();
                IsDrawing = true;
            }

            if (!rotation.HasValue && !scale.HasValue)
            {
                // Draw the sprite
                Handle.Draw(Texture, _colorBrga, rectangle, centerRef, new Vector3(position, 0) + (centerRef ?? Vector3.Zero));
            }
            else
            {
                // Store previous transform
                var transform = Handle.Transform;

                try
                {
                    // Apply transformation
                    Handle.Transform *= (Matrix.Scaling(new Vector3(scale ?? new Vector2(1), 0))) * Matrix.RotationZ(rotation ?? 0) *
                                        Matrix.Translation(new Vector3(position, 0) + (centerRef ?? Vector3.Zero));

                    // Draw the sprite
                    Handle.Draw(Texture, _colorBrga, rectangle, centerRef);
                }
                catch (Exception e)
                {
                    Logger.Debug("Failed to draw sprite with transformation:");
                    Logger.Debug(e.ToString());
                }

                // Restore previous transform
                Handle.Transform = transform;
            }
        }
    }
}
