using System;
using System.Collections.Generic;
using System.Collections.ObjectModel;
using System.ComponentModel;
using System.IO;
using System.Linq;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Input;
using EloBuddy.Loader.Data;
using EloBuddy.Loader.Globals;
using EloBuddy.Loader.Installers;
using EloBuddy.Loader.Logger;
using EloBuddy.Loader.Utils.UI;

namespace EloBuddy.Loader.Views
{
    /// <summary>
    ///     Interaction logic for RemoteAddonInstaller.xaml
    /// </summary>
    public partial class RemoteAddonInstallerWindow : INotifyPropertyChanged
    {
        public event PropertyChangedEventHandler PropertyChanged;
        public ObservableCollection<AddonToInstall> Items { get; set; }

        private bool _isWorking;
        public bool IsWorking
        {
            get { return _isWorking; }
            set
            {
                _isWorking = value;
                RaisePropertyChanged("IsWorking");
            }
        }

        private bool _isLoading;
        public bool IsLoading
        {
            get { return _isLoading; }
            set
            {
                _isLoading = value;
                RaisePropertyChanged("IsLoading");
            }
        }

        public string[] ProjectsToInstall { get; set; }
        public string Url { get; set; }
        public ElobuddyAddon RepoHolder { get; set; }

        public RemoteAddonInstallerWindow()
        {
            InitializeComponent();
            
            DataContext = this;
            Items = new ObservableCollection<AddonToInstall>();

            Owner = Windows.MainWindow;
            ShowInTaskbar = false;
            WindowStartupLocation = Owner == null ? WindowStartupLocation.CenterScreen : WindowStartupLocation.CenterOwner;
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
        }

        private void Window_ContentRendered(object sender, EventArgs e)
        {
            PrepareInstallation();
        }

        private void InstallButton_OnClick(object sender, RoutedEventArgs e)
        {
            Install(Items.Where(p => p.Install));
        }

        private void CancelButton_OnClick(object sender, RoutedEventArgs e)
        {
            Close();
        }
        
        public async void PrepareInstallation()
        {
            IsLoading = true;
            IsWorking = true;

            await Task.Run(() =>
            {
                RepoHolder.Update(false, false);
            });

            if (RepoHolder.State == AddonState.Ready)
            {
                foreach (var p in GetAddons())
                {
                    Items.Add(p);
                }
            }
            else
            {
                Items.Add(new AddonToInstall("Failed to download required data", false, false, "Error"));
            }

            IsLoading = false;
            IsWorking = false;

            if (ProjectsToInstall.Length > 0)
            {
                InstallSelected(Items);
            }
        }

        public async void Install(IEnumerable<AddonToInstall> addons)
        {
            IsWorking = true;

            await Task.Run(() =>
            {
                AddonInstaller.PerformInstall(Url, addons);
            });

            if (IsActive && addons.All(a => a.Success))
            {
                Close();
            }

            IsWorking = false;
        }

        public void InstallSelected(IEnumerable<AddonToInstall> addons)
        {
            Install(addons.Where(p => p.Install).OrderBy(p => Array.IndexOf(ProjectsToInstall, p.AddonName)));
        }

        public IEnumerable<AddonToInstall> GetAddons()
        {
            try
            {
                var foundProjects = AddonInstaller.GetProjectsFromRepo(RepoHolder.GetRemoteAddonRepositoryDirectory());

                return
                    foundProjects.Select(p => new Tuple<string, bool>(p, Settings.Instance.InstalledAddons.IsAddonInstalled(Url, p)))
                        .Select(t => new AddonToInstall(t.Item1, ProjectsToInstall.Contains(Path.GetFileNameWithoutExtension(t.Item1)), !t.Item2, t.Item2 ? "Installed" : ""));
            }
            catch (Exception e)
            {
                Log.Instance.DoLog(string.Format("Unexpected error while searching for project files during addon installation. Exception: {0}", e), Log.LogType.Error);

                return new[] { new AddonToInstall("Error while getting project files", false, false, "Error") };
            }
        }

        private void RaisePropertyChanged(string propName)
        {
            if (PropertyChanged != null)
                PropertyChanged(this, new PropertyChangedEventArgs(propName));
        }

        private void CheckBox_Checked(object sender, RoutedEventArgs e)
        {
            var checkBox = (CheckBox) e.OriginalSource;
            var dataGridRow = VisualTreeHelpers.FindAncestor<DataGridRow>(checkBox);

            if (dataGridRow == null)
            {
                return;
            }

            var item = dataGridRow.DataContext as AddonToInstall;
            item.Install = checkBox.IsChecked ?? false;
        }

        private void CheckBox_Unchecked(object sender, RoutedEventArgs e)
        {
            var checkBox = (CheckBox) e.OriginalSource;
            var dataGridRow = VisualTreeHelpers.FindAncestor<DataGridRow>(checkBox);

            if (dataGridRow == null)
            {
                return;
            }

            var item = dataGridRow.DataContext as AddonToInstall;
            item.Install = checkBox.IsChecked ?? false;
        }
    }

    public class AddonToInstall : INotifyPropertyChanged
    {
        public event PropertyChangedEventHandler PropertyChanged;

        private string _addonName;
        public string AddonFullName { get { return _addonName; } }

        public string AddonName
        {
            get { return Path.GetFileNameWithoutExtension(_addonName); }
            set
            {
                _addonName = value;
                RaisePropertyChanged("AddonName");
            }
        }

        private string _status;
        public string Status
        {
            get { return _status; }
            set
            {
                _status = value;
                RaisePropertyChanged("Status");
            }
        }

        private bool _enabled;
        public bool Enabled
        {
            get { return _enabled; }
            set
            {
                _enabled = value;
                RaisePropertyChanged("Enabled");
            }
        }

        private bool _install;
        public bool Install
        {
            get { return _install; }
            set
            {
                _install = value;
                RaisePropertyChanged("Install");
            }
        }

        public bool Success { get; set; }

        public AddonToInstall(string addonName, bool install = false, bool enabled = true, string status = "")
        {
            AddonName = addonName;
            Install = install && enabled;
            Enabled = enabled;
            Status = status;

            Success = true;
        }

        public void RaisePropertyChanged(string propName)
        {
            if (PropertyChanged != null)
                PropertyChanged(this, new PropertyChangedEventArgs(propName));
        }
    }
}
