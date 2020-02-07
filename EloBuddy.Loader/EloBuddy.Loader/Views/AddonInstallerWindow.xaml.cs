using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Windows;
using System.Windows.Input;
using System.Windows.Shell;
using EloBuddy.Loader.Data;
using EloBuddy.Loader.Globals;
using EloBuddy.Loader.Installers;
using Microsoft.Win32;

namespace EloBuddy.Loader.Views
{
    /// <summary>
    ///     Interaction logic for InstallAddonWindow.xaml
    /// </summary>
    public partial class AddonInstallerWindow
    {
        private string _url = "";

        public AddonInstallerWindow()
        {
            InitializeComponent();

            ShowInTaskbar = false;
        }

        private void Grid_MouseMove(object sender, MouseEventArgs e)
        {
            if (e.LeftButton == MouseButtonState.Pressed)
            {
                DragMove();
            }
        }

        private void Window_ContentRendered(object sender, EventArgs e)
        {
            if (!string.IsNullOrEmpty(_url))
            {
                ProcessInstallRequest(true, _url);
                Close();
            }
        }

        private void Window_Loaded(object sender, RoutedEventArgs e)
        {
            WindowChrome.SetWindowChrome(this, new WindowChrome { CaptionHeight = 0 });
        }

        private void Button_Click_1(object sender, RoutedEventArgs e)
        {
            Close();
        }

        private void Button_Click_2(object sender, RoutedEventArgs e)
        {
            LocalAddonTextBox_GotFocus(sender, e);

            var fileDialog = new OpenFileDialog
            {
                Filter = "project files |" + string.Concat(Constants.SupportedProjects.Select(s => "*" + s + ";")).TrimEnd(';'),
                FileName = ""
            };

            if (fileDialog.ShowDialog() == true)
            {
                LocalAddonTextBox.Text = fileDialog.FileName;
            }
        }

        private void LocalAddonTextBox_GotFocus(object sender, RoutedEventArgs e)
        {
            LocalAddonRadiobutton.IsChecked = true;
        }

        private void RemoteAddonTextbox_GotFocus(object sender, RoutedEventArgs e)
        {
            RemoteAddonRadiobutton.IsChecked = true;
        }

        private void InstallButton_Click(object sender, RoutedEventArgs e)
        {
            ProcessInstallRequest(LocalAddonRadiobutton.IsChecked != true,
                LocalAddonRadiobutton.IsChecked == true ? LocalAddonTextBox.Text : RemoteAddonTextbox.Text);
        }

        private void ProcessInstallRequest(bool remoteAddon, string requestString)
        {
            if (!remoteAddon)
            {
                //TODO: check for valid path

                var args = new Dictionary<string, object> { { "projectPath", requestString } };
                var taskWindow = new TaskWindow { Owner = Owner };

                Hide();
                taskWindow.WindowTitle = MultiLanguage.Text.TitleTaskAddonInstaller;
                taskWindow.BeginTask(new TaskWindow.TaskWindowDelegate[] { InstallLocalAddon }, args);

                if (taskWindow.Success)
                {
                    Close();
                }
                else
                {
                    Show();
                    MessageBox.Show(
                        string.Format(MultiLanguage.Text.ErrorFailedToInstallAddon,
                            Path.GetFileNameWithoutExtension(LocalAddonTextBox.Text)),
                        MultiLanguage.Text.TitleTaskAddonInstaller,
                        MessageBoxButton.OK, MessageBoxImage.Exclamation);
                }
            }
            else
            {
                Hide();
                AddonInstaller.InstallAddonsFromRepo(requestString);
                Close();
            }
        }

        private static bool InstallLocalAddon(TaskWindow ui, Dictionary<string, object> args)
        {
            var projectPath = (string) args["projectPath"];

            ui.CustomStatusString = true;
            ui.Status = MultiLanguage.Text.TaskInstallingLocalAddon + Path.GetFileNameWithoutExtension(projectPath);

            return Settings.Instance.InstalledAddons.InstallAddon("", projectPath);
        }
    }
}
