using System;
using System.Collections.Generic;
using System.Linq;
using SharpDX;
using Color = System.Drawing.Color;

namespace EloBuddy.SDK.Rendering
{
    public sealed class Line
    {
        internal const float DefaultWidth = 2;

        internal static SharpDX.Direct3D9.Line Handle { get; set; }
        internal static bool IsDrawing { get; set; }

        static Line()
        {
            // Initialize properties
            Handle = new SharpDX.Direct3D9.Line(Drawing.Direct3DDevice) { Antialias = true };

            // Listen to events
            AppDomain.CurrentDomain.DomainUnload += OnAppDomainUnload;
            AppDomain.CurrentDomain.ProcessExit += OnAppDomainUnload;
            Drawing.OnFlushEndScene += OnFlush;
            Drawing.OnPreReset += OnPreReset;
            Drawing.OnPostReset += OnPostReset;

            // For static drawing
            LineHandle = new Line();
        }

        internal static Line LineHandle { get; set; }

        public static void DrawLine(Color color, params Vector2[] screenVertices)
        {
            DrawLine(color, DefaultWidth, screenVertices);
        }

        public static void DrawLine(Color color, float width = DefaultWidth, params Vector2[] screenVertices)
        {
            LineHandle.Draw(color, width, screenVertices);
        }

        public static void DrawLine(Color color, params Vector3[] worldVertices)
        {
            DrawLine(color, DefaultWidth, worldVertices);
        }

        public static void DrawLine(Color color, float width = DefaultWidth, params Vector3[] worldVertices)
        {
            LineHandle.Draw(color, width, worldVertices);
        }

        public static void DrawLineTransform(Color color, Matrix transform, params Vector2[] screenVertices)
        {
            DrawLineTransform(color, transform, DefaultWidth, screenVertices);
        }

        public static void DrawLineTransform(Color color, Matrix transform, float width = DefaultWidth, params Vector2[] screenVertices)
        {
            LineHandle.DrawTransform(color, transform, width, screenVertices);
        }

        public static void DrawLineTransform(Color color, Matrix transform, params Vector3[] worldVertices)
        {
            DrawLineTransform(color, transform, DefaultWidth, worldVertices);
        }

        public static void DrawLineTransform(Color color, Matrix transform, float width = DefaultWidth, params Vector3[] worldVertices)
        {
            LineHandle.DrawTransform(color, transform, width, worldVertices);
        }

        internal static void OnFlush(EventArgs args)
        {
            if (IsDrawing)
            {
                Handle.End();
                IsDrawing = false;
            }
        }

        internal static void OnPreReset(EventArgs args)
        {
            Handle.OnLostDevice();
        }

        internal static void OnPostReset(EventArgs args)
        {
            Handle.OnResetDevice();
        }

        internal static void OnAppDomainUnload(object sender, EventArgs e)
        {
            if (Handle != null)
            {
                Core.EndAllDrawing();
                Handle.Dispose();
                Handle = null;
            }
        }

        public Color Color { get; set; }
        public IEnumerable<Vector2> ScreenVertices { get; set; }
        public float Width { get; set; }
        public Matrix? Transform { get; set; }
        public bool Antialias { get; set; }

        public Line()
        {
            // Initialize properties
            Width = DefaultWidth;
            Antialias = true;
        }

        internal void Draw(Color color, Matrix? transform, Vector2[] vertices, float width = DefaultWidth, bool antialias = true)
        {
            // Validation
            if (Handle == null || Handle.IsDisposed || vertices.Length < 2 || width <= 0)
            {
                return;
            }

            if (!IsDrawing)
            {
                Core.EndAllDrawing(Core.RenderingType.Line);
                Handle.Antialias = antialias;
                Handle.Begin();
                IsDrawing = true;
            }

            // Draw the line(s)
            if (Math.Abs(Handle.Width - width) > float.Epsilon)
            {
                Handle.End();
                Handle.Width = width;
                Handle.Begin();
            }
            if (!transform.HasValue)
            {
                Handle.Draw(vertices, new ColorBGRA(color.R, color.G, color.B, color.A));
            }
            else
            {
                Handle.DrawTransform(vertices, transform.Value, new ColorBGRA(color.R, color.G, color.B, color.A));
            }

            if (!antialias)
            {
                Handle.End();
                Handle.Antialias = false;
                IsDrawing = false;
            }
        }

        public void Draw(Color color, params Vector2[] screenVertices)
        {
            Draw(color, null, screenVertices);
        }

        public void Draw(Color color, float width = DefaultWidth, params Vector2[] screenVertices)
        {
            Draw(color, null, screenVertices, width);
        }

        public void Draw(Color color, params Vector3[] worldVertices)
        {
            Draw(color, null, worldVertices.Select(o => o.WorldToScreen()).ToArray());
        }

        public void Draw(Color color, float width = DefaultWidth, params Vector3[] worldVertices)
        {
            Draw(color, null, worldVertices.Select(o => o.WorldToScreen()).ToArray(), width);
        }

        public void DrawTransform(Color color, Matrix transform, params Vector2[] screenVertices)
        {
            Draw(color, transform, screenVertices);
        }

        public void DrawTransform(Color color, Matrix transform, float width = DefaultWidth, params Vector2[] screenVertices)
        {
            Draw(color, transform, screenVertices, width);
        }

        public void DrawTransform(Color color, Matrix transform, params Vector3[] worldVertices)
        {
            Draw(color, transform, worldVertices.Select(o => o.WorldToScreen()).ToArray());
        }

        public void DrawTransform(Color color, Matrix transform, float width = DefaultWidth, params Vector3[] worldVertices)
        {
            Draw(color, transform, worldVertices.Select(o => o.WorldToScreen()).ToArray(), width);
        }

        public void Draw()
        {
            // Validation
            if (ScreenVertices == null)
            {
                return;
            }

            Draw(Color, Transform, ScreenVertices.ToArray(), Width);
        }
    }
}
