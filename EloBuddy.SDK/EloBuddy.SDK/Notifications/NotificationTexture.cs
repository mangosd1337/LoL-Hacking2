using System;
using SharpDX;
using SharpDX.Direct3D9;

namespace EloBuddy.SDK.Notifications
{
    public sealed class NotificationTexture
    {
        public PartialTexture Header { get; set; }
        public PartialTexture Content { get; set; }
        public PartialTexture Footer { get; set; }

        public sealed class PartialTexture
        {
            public Func<Texture> Texture { get; set; }
            public Rectangle? SourceRectangle { get; set; }
            public Vector2? Position { get; set; }
        }
    }
}
