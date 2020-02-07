using System;
using System.Diagnostics;
using System.Windows;
using System.Windows.Forms;
using System.Windows.Input;
using System.Windows.Shell;
using EloBuddy.Loader.Data;
using EloBuddy.Loader.Globals;
using EloBuddy.Loader.Logger;
using EloBuddy.Loader.Utils;
using MessageBox = System.Windows.MessageBox;
using MouseEventArgs = System.Windows.Input.MouseEventArgs;

namespace EloBuddy.Loader.Views
{
    /// <summary>
    ///     Interaction logic for SettingsWindow.xaml
    /// </summary>
    public partial class SettingsWindow
    {
        public SettingsWindow()
        {
            InitializeComponent();
        }

        public static string LogPath
        {
            get { return Log.Instance.LogFilePath; }
        }

        private void CloseButton_OnClick(object sender, RoutedEventArgs e)
        {
            Close();
        }

        private void Grid_MouseMove(object sender, MouseEventArgs e)
        {
            if (e.LeftButton == MouseButtonState.Pressed)
            {
                DragMove();
            }
        }

        private void Window_Loaded(object sender, RoutedEventArgs e)
        {
            WindowChrome.SetWindowChrome(this, new WindowChrome { CaptionHeight = 0 });

            // Update UI
            foreach (var language in Enum.GetValues(typeof (Language)))
            {
                LanguageComboBox.Items.Add(language);

                if ((Language) language == Settings.Instance.SelectedLanguage)
                {
                    LanguageComboBox.SelectedIndex = LanguageComboBox.Items.Count - 1;
                }
            }

            AssemblyLocationTextBox.Text = Settings.Instance.Directories.AssembliesDirectory;
            AssemblyLocationTextBox.IsReadOnly = true;

            TelemetryCheckBox.IsChecked = !Settings.Instance.DisableTelemetry;
            UpdateAssembliesCheckBox.IsChecked = Settings.Instance.UpdateAssembliesOnStart;
            InjectCheckBox.IsChecked = Settings.Instance.EnableInjection;
            DisableAutomaticUpdatesCheckBox.IsChecked = Settings.Instance.DisableAutomaticUpdates;

            AntiAfkCheckBox.IsChecked = Settings.Instance.Configuration.AntiAfk;
            ConsoleCheckBox.IsChecked = Settings.Instance.Configuration.Console;
            ExtendedZoomCheckBox.IsChecked = Settings.Instance.Configuration.ExtendedZoom;
            TowerRangeCheckBox.IsChecked = Settings.Instance.Configuration.TowerRange;
            StreamingModeCheckBox.IsChecked = Settings.Instance.Configuration.StreamingMode;
            DrawWatermarkCheckBox.IsEnabled = Authenticator.IsBuddy;
            DrawWatermarkCheckBox.IsChecked = !DrawWatermarkCheckBox.IsEnabled || Settings.Instance.Configuration.DrawWaterMark;
            DisableChatCheckBox.IsChecked = Settings.Instance.Configuration.DisableChatFunction;
            DisableRangeIndicatorCheckBox.IsChecked = Settings.Instance.Configuration.DisableChatFunction;
            StreamingModeCheckBox.IsEnabled = Authenticator.IsBuddy;
            StreamingModeCheckBox.IsChecked = StreamingModeCheckBox.IsEnabled && Settings.Instance.Configuration.StreamingMode;
        }

        private void BrowseFilesButton_Click(object sender, RoutedEventArgs e)
        {
            var dialog = new FolderBrowserDialog();
            if (dialog.ShowDialog() == System.Windows.Forms.DialogResult.OK)
            {
                AssemblyLocationTextBox.Text = dialog.SelectedPath;
            }
        }

        private void AppDataButton_OnClick(object sender, RoutedEventArgs e)
        {
            Process.Start(Settings.Instance.Directories.AppDataDirectory);
        }

        private void CancelButton_OnClick(object sender, RoutedEventArgs e)
        {
            Close();
        }

        private void DoneButton_OnClick(object sender, RoutedEventArgs e)
        {
            // Save settings
            Settings.Instance.DisableTelemetry = !TelemetryCheckBox.IsChecked ?? false;
            Settings.Instance.UpdateAssembliesOnStart = UpdateAssembliesCheckBox.IsChecked ?? true;
            Settings.Instance.EnableInjection = InjectCheckBox.IsChecked ?? true;
            Settings.Instance.DisableAutomaticUpdates = DisableAutomaticUpdatesCheckBox.IsChecked ?? false;
            Settings.Instance.Configuration.AntiAfk = AntiAfkCheckBox.IsChecked ?? true;
            Settings.Instance.Configuration.Console = ConsoleCheckBox.IsChecked ?? false;
            Settings.Instance.Configuration.ExtendedZoom = ExtendedZoomCheckBox.IsChecked ?? false;
            Settings.Instance.Configuration.TowerRange = TowerRangeCheckBox.IsChecked ?? true;
            Settings.Instance.Configuration.MovementHack = false;
            Settings.Instance.Configuration.StreamingMode = StreamingModeCheckBox.IsChecked ?? false;
            Settings.Instance.Configuration.DrawWaterMark = DrawWatermarkCheckBox.IsChecked ?? true;
            Settings.Instance.Configuration.DisableChatFunction = DisableChatCheckBox.IsChecked ?? false;
            Settings.Instance.Configuration.DisableRangeIndicator = DisableRangeIndicatorCheckBox.IsChecked ?? false;

            if (LanguageComboBox.SelectedIndex != -1)
            {
                Settings.Instance.SelectedLanguage = (Language) Enum.Parse(typeof (Language), LanguageComboBox.SelectedItem.ToString());
                MultiLanguage.Text = LocalizedText.Load(Settings.Instance.SelectedLanguage.Value);
            }

            if (AssemblyLocationTextBox.Text != Settings.Instance.Directories.AssembliesDirectory)
            {
                try
                {
                    DirectoryHelper.CopyDirectory(Settings.Instance.Directories.AssembliesDirectory,
                        AssemblyLocationTextBox.Text, true);
                    DirectoryHelper.DeleteDirectory(Settings.Instance.Directories.AssembliesDirectory, false);
                    Settings.Instance.Directories.AssembliesDirectory = AssemblyLocationTextBox.Text;
                }
                catch (Exception)
                {
                    MessageBox.Show("Failed to create new Assemblies Directory!", "Settings", MessageBoxButton.OK,
                        MessageBoxImage.Exclamation);
                }
            }

            Close();
        }

        private void LogsButton_OnClick(object sender, RoutedEventArgs e)
        {
            Process.Start(Settings.Instance.Directories.LogsDirectory);
        }
    }
}
