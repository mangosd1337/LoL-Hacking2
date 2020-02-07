using System.Windows;
using System.Windows.Controls;

namespace EloBuddy.Loader.Controls
{
    public class WizardProgressBar : ItemsControl
    {
        #region Dependency Properties

        public static DependencyProperty ProgressProperty =
            DependencyProperty.Register("Progress",
                                        typeof(int),
                                        typeof(WizardProgressBar),
                                        new FrameworkPropertyMetadata(0, null, CoerceProgress));

        private static object CoerceProgress(DependencyObject target, object value)
        {
            WizardProgressBar wizardProgressBar = (WizardProgressBar)target;
            int progress = (int)value;
            if (progress < 0)
            {
                progress = 0;
            }
            else if (progress > 100)
            {
                progress = 100;
            }
            return progress;
        }

        #endregion // Dependency Properties

        static WizardProgressBar()
        {
            DefaultStyleKeyProperty.OverrideMetadata(typeof(WizardProgressBar), new FrameworkPropertyMetadata(typeof(WizardProgressBar)));
        }

        public WizardProgressBar()
        {
        }

        #region Properties

        public int Progress
        {
            get { return (int)base.GetValue(ProgressProperty); }
            set { base.SetValue(ProgressProperty, value); }
        }

        #endregion // Properties
    }
}
