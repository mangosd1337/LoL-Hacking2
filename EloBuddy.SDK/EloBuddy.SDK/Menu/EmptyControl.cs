namespace EloBuddy.SDK.Menu
{
    public sealed class EmptyControl : Control
    {
        public EmptyControl(ThemeManager.SpriteType type) : base(type)
        {
            // Because this class is intended to serve as a pure placeholder, we don't need to draw the base
            DrawBase = false;

            // Initalize theme specific properties
            OnThemeChange();
        }
    }
}
