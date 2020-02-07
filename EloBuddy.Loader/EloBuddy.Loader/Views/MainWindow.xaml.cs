using System;
using System.Collections.ObjectModel;
using System.Diagnostics;
using System.Drawing;
using System.Linq;
using System.Windows;
using System.Windows.Input;
using System.Windows.Interop;
using System.Windows.Media;
using EloBuddy.Loader.Data;
using EloBuddy.Loader.Globals;
using EloBuddy.Loader.Types;
using EloBuddy.Loader.Update;
using EloBuddy.Loader.Utils;
using EloBuddy.Loader.Views;
using Color = System.Windows.Media.Color;

namespace Elobuddy.Loader.Views
{
    /// <summary>
    ///     Interaction logic for MainWindow.xaml
    /// </summary>
    public partial class MainWindow
    {
        public MainWindow()
        {
            InitializeComponent();

            InstalledAddonsGrid.Items = new ObservableCollection<InstalledAddonDataGridItem>();
        }

        private void CloseButton_OnClick(object sender, RoutedEventArgs e)
        {
            Close();
        }

        private void MaximizeButton_OnClick(object sender, RoutedEventArgs e)
        {
            WindowState = WindowState == WindowState.Normal ? WindowState.Maximized : WindowState.Normal;
        }

        private void MinimizeButton_OnClick(object sender, RoutedEventArgs e)
        {
            WindowState = WindowState.Minimized;
        }

        private void Grid_MouseMove(object sender, MouseEventArgs e)
        {
            if (e.LeftButton != MouseButtonState.Pressed)
            {
                return;
            }

            if (WindowState == WindowState.Maximized)
            {
                WindowState = WindowState.Normal;
                Top = 0;
                Left = GetMousePosition().X - Width / 2;
            }

            DragMove();
        }

        private void mainWin_SourceInitialized(object sender, EventArgs e)
        {
            var handle = (new WindowInteropHelper(this)).Handle;
            var hwndSource = HwndSource.FromHwnd(handle);
            if (hwndSource != null)
            {
                hwndSource.AddHook(WindowProc);
            }

            SupportUsButton.Visibility = Authenticator.IsBuddy ? Visibility.Hidden : Visibility.Visible;
        }

        private static IntPtr WindowProc(IntPtr hwnd, int msg, IntPtr wParam, IntPtr lParam, ref bool handled)
        {
            switch (msg)
            {
                case 0x0024: /* WM_GETMINMAXINFO */
                    //WmGetMinMaxInfo(hwnd, lParam);
                    //handled = true;
                    break;
            }

            return (IntPtr) 0;
        }

        private void SettingsButton_OnClick(object sender, RoutedEventArgs e)
        {
            (new SettingsWindow { Owner = this }).ShowDialog();
        }

        private void mainWin_Initialized(object sender, EventArgs e)
        {
            // MainWindow Settings
            if (Settings.Instance.Ui == null)
            {
                Settings.Instance.Ui = new SettingsUI();
            }

            if (!Settings.Instance.Ui.MainWindowSize.IsEmpty)
            {
                Width = Settings.Instance.Ui.MainWindowSize.Width;
                Height = Settings.Instance.Ui.MainWindowSize.Height;
            }

            // User data
            uncUser.UserName = Authenticator.DisplayName;
            uncUser.Avatar = Authenticator.Avatar == null ? EloBuddy.Loader.Properties.Resources.AnonymousMale : (Bitmap) Authenticator.Avatar;
            RefreshDaysLeft();

            // Display update status
            RefreshEloBuddyStatus();

            Events.RaiseOnMainWindowInitialized(this, e);
        }

        // triggers before the window has been shown
        private void mainWin_Loaded(object sender, RoutedEventArgs e)
        {
            Events.RaiseOnMainWindowLoaded(this, e);

            // Display loaded addons
            RefreshAddons();
        }

        // triggers after the window has been shown
        private void mainWin_ContentRendered(object sender, EventArgs e)
        {
            if (Settings.Instance.UpdateAssembliesOnStart && Settings.Instance.InstalledAddons.Count > 0)
            {
                LoaderUpdate.UpdateInstalledAddons();
            }
        }

        private void mainWin_SizeChanged(object sender, SizeChangedEventArgs e)
        {
            Settings.Instance.Ui.MainWindowSize = e.NewSize;
        }

        private void mainWin_LocationChanged(object sender, EventArgs e)
        {
            Settings.Instance.Ui.MainWindowPositionLeft = Left;
            Settings.Instance.Ui.MainWindowPositionTop = Top;
        }

        private void uncUser_OnClick(object sender, RoutedEventArgs e)
        {
            //(new DummyWindow()).Show();

            Settings.Instance.UserCredentials = new Credentials();
            EnvironmentHelper.Restart(true);
        }

        private void UpdateStatusLabel_MouseLeftButtonDown(object sender, MouseButtonEventArgs e)
        {
            LoaderUpdate.UpdateSystem();
        }

        public void RefreshAddons()
        {
            InstalledAddonsGrid.Items.Clear();

            foreach (var addon in Settings.Instance.InstalledAddons)
            {
                var item = new InstalledAddonDataGridItem(addon);
                InstalledAddonsGrid.Items.Add(item);
            }
        }

        public void RemoveAddon(ElobuddyAddon addon)
        {
            foreach (var item in InstalledAddonsGrid.Items.ToArray())
            {
                if (item.Addon.Equals(addon))
                {
                    InstalledAddonsGrid.Items.Remove(item);
                }
            }
        }

        public void AddLastAddon()
        {
            Dispatcher.Invoke(() =>
            {
                var addon = Settings.Instance.InstalledAddons.LastOrDefault();

                if (addon == null)
                {
                    return;
                }

                var item = new InstalledAddonDataGridItem(addon);
                InstalledAddonsGrid.Items.Add(item);
            });
        }

        public void RefreshDaysLeft()
        {
            //var timespan = (Authenticator.MembershipExpirationDate - DateTime.Now);
            //DaysLabel.Content = timespan.TotalDays <= 1
            //    ? timespan.Hours + " hours"
            //    : (timespan.Days > 1000 ? "never" : timespan.Days + " days");
        }

        public void RefreshEloBuddyStatus()
        {
            UpdateStatusLabel.Content = LoaderUpdate.StatusString;
            UpdateStatusLabel.Foreground = LoaderUpdate.UpToDate
                ? new SolidColorBrush(Color.FromRgb(64, 209, 81))
                : new SolidColorBrush(Color.FromRgb(255, 0, 0));
        }

        public void RefreshNews()
        {
            //TODO
        }

        private void SupportUsButton_OnClick(object sender, RoutedEventArgs e)
        {
            Process.Start("https://www.elobuddy.net/upgrade/product/2-buddy/");
        }
    }
}
