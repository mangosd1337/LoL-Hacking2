using System.Drawing;

namespace EloBuddy.SDK.Notifications
{
    public interface INotification
    {
        string FontName { get; }

        string HeaderText { get; }
        Color HeaderColor { get; }
        string ContentText { get; }
        Color ContentColor { get; }

        NotificationTexture Texture { get; }

        int RightPadding { get; }
    }
}
