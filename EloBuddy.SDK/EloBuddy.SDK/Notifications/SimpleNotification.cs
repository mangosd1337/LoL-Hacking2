using EloBuddy.SDK.Properties;
using EloBuddy.SDK.Rendering;
using SharpDX;

namespace EloBuddy.SDK.Notifications
{
    public class SimpleNotification : NotificationBase
    {
        private static readonly TextureLoader TextureLoader = new TextureLoader();

        private static readonly NotificationTexture NotificationTextureTexture;

        static SimpleNotification()
        {
            // Load the texture
            const string textureName = "simpleNotification";
            TextureLoader.Load(textureName, Resources.SimpleNotification);
            NotificationTextureTexture = new NotificationTexture
            {
                // Hardcoded atlas
                Header = new NotificationTexture.PartialTexture
                {
                    Position = new Vector2(0),
                    SourceRectangle = new Rectangle(0, 0, 299, 3),
                    Texture = () => TextureLoader[textureName]
                },
                Content = new NotificationTexture.PartialTexture
                {
                    Position = new Vector2(0, 3),
                    SourceRectangle = new Rectangle(0, 3, 299, 39),
                    Texture = () => TextureLoader[textureName]
                },
                Footer = new NotificationTexture.PartialTexture
                {
                    Position = new Vector2(0, 42),
                    SourceRectangle = new Rectangle(0, 42, 299, 3),
                    Texture = () => TextureLoader[textureName]
                },
            };
        }

        private readonly string _headerText;
        public override string HeaderText
        {
            get { return _headerText; }
        }
        private readonly string _contentText;
        public override string ContentText
        {
            get { return _contentText; }
        }

        public override NotificationTexture Texture
        {
            get { return NotificationTextureTexture; }
        }

        public SimpleNotification(string header, string content)
        {
            // Initialize properties
            _headerText = header;
            _contentText = content;
        }
    }
}
