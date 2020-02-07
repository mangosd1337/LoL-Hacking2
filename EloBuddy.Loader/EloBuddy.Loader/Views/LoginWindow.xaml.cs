using System;
using System.ComponentModel;
using System.Diagnostics;
using System.Globalization;
using System.Runtime.CompilerServices;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Data;
using System.Windows.Input;
using System.Windows.Shell;
using EloBuddy.Loader.Annotations;
using EloBuddy.Loader.Data;
using EloBuddy.Loader.Globals;
using EloBuddy.Loader.Update;

namespace Elobuddy.Loader.Views
{
    /// <summary>
    ///     Interaction logic for LoginWindow.xaml
    /// </summary>
    public partial class LoginWindow : INotifyPropertyChanged
    {
        private bool _isAvailable = true;

        public bool IsAvailable
        {
            get { return _isAvailable; }

            set
            {
                _isAvailable = value;
                OnPropertyChanged();
            }
        }

        private string _errorMessage;

        public string ErrorMessage
        {
            get { return _errorMessage; }

            set
            {
                _errorMessage = value;
                OnPropertyChanged();
            }
        }

        public LoginWindow()
        {
            InitializeComponent();
            DataContext = this;
        }

        private void Window_Initialized(object sender, EventArgs e)
        {
            // Update EloBuddy
            LoaderUpdate.UpdateSystem();

            // Set UI
            RememberCheckBox.IsChecked = Settings.Instance.RememberCredentials;

            // Auto login
            if (Settings.Instance.RememberCredentials && Settings.Instance.UserCredentials != null && !Settings.Instance.UserCredentials.IsEmpty)
            {
                UsernameTextBox.Text = Settings.Instance.UserCredentials.Username;
                PasswordTextBox.Password = Settings.Instance.UserCredentials.Password;
                DoLogin(Settings.Instance.UserCredentials.Username, Settings.Instance.UserCredentials.Password, true);
            }

            Events.RaiseOnLoginWindowInitialized(this, e);
        }

        private void Grid_MouseMove(object sender, MouseEventArgs e)
        {
            if (e.LeftButton == MouseButtonState.Pressed)
            {
                DragMove();
            }
        }

        private void MinimizeButton_OnClick(object sender, RoutedEventArgs e)
        {
            WindowState = WindowState.Minimized;
        }

        private void CloseButton_OnClick(object sender, RoutedEventArgs e)
        {
            Close();
        }

        private void Window_Loaded(object sender, RoutedEventArgs e)
        {
            WindowChrome.SetWindowChrome(this, new WindowChrome { CaptionHeight = 3 });
        }

        private void Window_Closing(object sender, CancelEventArgs e)
        {
            Settings.Instance.RememberCredentials = RememberCheckBox.IsChecked ?? false;
        }

        private void LoginButton_OnClick(object sender, RoutedEventArgs e)
        {
            DoLogin(UsernameTextBox.Text, PasswordTextBox.Password);
        }

        private async void DoLogin(string username, string password, bool autoLogin = false)
        {
            IsAvailable = false;
            ErrorMessage = "";

            new Task(() =>
            {
                var result = Authenticator.Login(username, password, autoLogin);
                IsAvailable = true;

                if (!result)
                {
                    ErrorMessage = Authenticator.LastError;
                    return;
                }

                Dispatcher.Invoke(() =>
                {
                    if (!autoLogin)
                    {
                        Settings.Instance.RememberCredentials = RememberCheckBox.IsChecked ?? false;
                        Settings.Instance.UserCredentials = Settings.Instance.RememberCredentials ? Authenticator.Credentials : new Credentials();
                    }

                    Hide();
                    Events.RaiseOnSuccessLogin(new EventArgs());
                    Windows.MainWindow = new MainWindow();
                    Windows.MainWindow.Show();
                    Close();
                });
            }).Start();
        }

        private void PasswordTextBox_KeyDown(object sender, KeyEventArgs e)
        {
            if (e.IsDown && e.Key == Key.Enter)
            {
                LoginButton_OnClick(sender, e);
            }
        }

        private void TextBlock_MouseLeftButtonUp(object sender, MouseButtonEventArgs e)
        {
            Process.Start(new ProcessStartInfo("https://www.elobuddy.net/lostpassword/"));
        }

        private void TextBlock2_MouseLeftButtonUp(object sender, MouseButtonEventArgs e)
        {
            Process.Start(new ProcessStartInfo("https://www.elobuddy.net/register/"));
        }

        public event PropertyChangedEventHandler PropertyChanged;

        [NotifyPropertyChangedInvocator]
        protected virtual void OnPropertyChanged([CallerMemberName] string propertyName = null)
        {
            var handler = PropertyChanged;
            if (handler != null)
                handler(this, new PropertyChangedEventArgs(propertyName));
        }
    }
}
