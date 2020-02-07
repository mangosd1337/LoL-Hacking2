using System.Drawing;

namespace EloBuddy.SDK.Notifications
{
    public abstract class NotificationBase : INotification
    {
        public const string DefaultFontName = "Gill Sans MT Pro Medium";

        public static readonly Color DefaultHeaderTextColor = Color.FromArgb(255, 143, 122, 72);
        public static readonly Color DefaultContentTextColor = Color.FromArgb(255, 44, 99, 94);
        public static readonly Color DefaultBackgroundColor = Color.White;

        public static readonly int DefaultRightPadding = 0;

        public virtual string FontName
        {
            get { return DefaultFontName; }
        }

        public abstract string HeaderText { get; }
        public virtual Color HeaderColor
        {
            get { return DefaultHeaderTextColor; }
        }
        public abstract string ContentText { get; }
        public virtual Color ContentColor
        {
            get { return DefaultContentTextColor; }
        }

        public abstract NotificationTexture Texture { get; }
        public virtual int RightPadding
        {
            get { return DefaultRightPadding; }
        }
    }
}
