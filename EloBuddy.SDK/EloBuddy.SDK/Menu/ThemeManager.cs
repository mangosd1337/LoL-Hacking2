using System;
using EloBuddy.SDK.Properties;

namespace EloBuddy.SDK.Menu
{
    public static class ThemeManager
    {
        public delegate void ThemeChangedHandler(EventArgs args);

        public static event ThemeChangedHandler OnThemeChanged;

        public const string DefaultFontFaceName = "Gill Sans MT Pro Book";
        public static readonly Theme DefaultTheme = Theme.FromMemory("Default", Resources.Theme, Resources.Config);

        internal static Theme _currentTheme = DefaultTheme;
        public static Theme CurrentTheme
        {
            get { return _currentTheme; }
            set
            {
                _currentTheme = value;

                if (OnThemeChanged != null)
                {
                    OnThemeChanged(EventArgs.Empty);
                }
            }
        }

        internal static Theme.DynamicRectangle GetDynamicRectangle(DynamicControl control)
        {
            if (control == null)
            {
                throw new ArgumentNullException("control");
            }

            switch (control.Type)
            {
                // Buttons
                case SpriteType.ButtonExit:
                    return CurrentTheme.Config.SpriteAtlas.Controls.Buttons.Exit;
                case SpriteType.ButtonAddon:
                    return CurrentTheme.Config.SpriteAtlas.Controls.Buttons.Addon;
                case SpriteType.ButtonNormal:
                    return CurrentTheme.Config.SpriteAtlas.Controls.Buttons.Normal;
                case SpriteType.ButtonConfirm:
                    return CurrentTheme.Config.SpriteAtlas.Controls.Buttons.Confirm;
                case SpriteType.ButtonMini:
                    return CurrentTheme.Config.SpriteAtlas.Controls.Buttons.Mini;

                // Other controls
                case SpriteType.ControlCheckBox:
                    return CurrentTheme.Config.SpriteAtlas.Controls.CheckBox;
                case SpriteType.ControlSlider:
                    return CurrentTheme.Config.SpriteAtlas.Controls.Slider;
                case SpriteType.ControlComboBox:
                    return CurrentTheme.Config.SpriteAtlas.Controls.ComboBox;
            }

            Console.WriteLine("ThemeManager.GetDynamicRectangle: {0} is not handled yet!", control.Type);
            return null;
        }

        internal static Theme.StaticRectangle GetStaticRectangle(Control control)
        {
            if (control == null)
            {
                throw new ArgumentNullException("control");
            }

            switch (control.Type)
            {
                // Form parts
                case SpriteType.FormComplete:
                    return CurrentTheme.Config.SpriteAtlas.MainForm.Complete;
                case SpriteType.FormHeader:
                    return CurrentTheme.Config.SpriteAtlas.MainForm.Header;
                case SpriteType.FormFooter:
                    return CurrentTheme.Config.SpriteAtlas.MainForm.Footer;
                case SpriteType.FormAddonButtonContainer:
                    return CurrentTheme.Config.SpriteAtlas.MainForm.AddonButtonContainer;
                case SpriteType.FormContentHeader:
                    return CurrentTheme.Config.SpriteAtlas.MainForm.ContentHeader;
                case SpriteType.FormContentContainer:
                    return CurrentTheme.Config.SpriteAtlas.MainForm.ContentContainer;

                // Backgrounds
                case SpriteType.BackgroundSlider:
                    return CurrentTheme.Config.SpriteAtlas.Backgrounds.Slider;
            }

            Console.WriteLine("ThemeManager.GetStaticRectangle: {0} is not handled yet!", control.Type);
            return null;
        }

        public enum SpriteType
        {
            // Empty
            Empty,

            // Static
            FormComplete,
            FormHeader,
            FormFooter,
            FormAddonButtonContainer,
            FormContentHeader,
            FormContentContainer,

            // Static
            BackgroundSlider,

            // Dynamic
            ButtonExit,
            ButtonAddon,
            ButtonNormal,
            ButtonConfirm,
            ButtonMini,

            // Dynamic
            ControlCheckBox,
            ControlSlider,
            ControlComboBox
        }
    }
}
