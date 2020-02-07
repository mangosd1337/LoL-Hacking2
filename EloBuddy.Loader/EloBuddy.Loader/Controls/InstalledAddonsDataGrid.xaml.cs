using System;
using System.Collections.ObjectModel;
using System.ComponentModel;
using System.Diagnostics;
using System.IO;
using System.Linq;
using System.Text.RegularExpressions;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Data;
using System.Windows.Input;
using System.Windows.Media;
using EloBuddy.Loader.Data;
using EloBuddy.Loader.Routines;
using EloBuddy.Loader.Types;
using EloBuddy.Loader.Update;
using EloBuddy.Loader.Utils.UI;
using EloBuddy.Loader.Views;

namespace EloBuddy.Loader.Controls
{
    /// <summary>
    ///     Interaction logic for InstalledAddonsDataGrid.xaml
    /// </summary>
    public partial class InstalledAddonsDataGrid : INotifyPropertyChanged
    {
        private Point _prevMousePoint;
        private int _prevRowIndex = -1;

        public InstalledAddonsDataGrid()
        {
            InitializeComponent();

            DataContext = this;
            Items = new ObservableCollection<InstalledAddonDataGridItem>();
        }

        public ObservableCollection<InstalledAddonDataGridItem> Items { get; set; }
        public event PropertyChangedEventHandler PropertyChanged;

        private void InstallAssemblyButton_Click(object sender, RoutedEventArgs e)
        {
            (new AddonInstallerWindow { Owner = Window.GetWindow(this) }).ShowDialog();
        }

        private void DeleteAddonsButton_Click(object sender, RoutedEventArgs e)
        {
            var selectedItems = new InstalledAddonDataGridItem[Grid.SelectedItems.Count];
            Grid.SelectedItems.CopyTo(selectedItems, 0);

            foreach (var item in selectedItems)
            {
                var addon = item.Addon;
                Settings.Instance.InstalledAddons.UninstallAddon(addon);
                Items.Remove(item);
            }
        }

        private void UpdateAssembliesButton_Click(object sender, RoutedEventArgs e)
        {
            if (Settings.Instance.InstalledAddons.Count > 0)
            {
                LoaderUpdate.UpdateInstalledAddons();
            }
        }

        private void RaisePropertyChanged(string propName)
        {
            if (PropertyChanged != null)
            {
                PropertyChanged(this, new PropertyChangedEventArgs(propName));
            }
        }

        private void CheckBox_Unchecked(object sender, RoutedEventArgs e)
        {
            var checkBox = (CheckBox) e.OriginalSource;
            var dataGridRow = VisualTreeHelpers.FindAncestor<DataGridRow>(checkBox);

            if (dataGridRow == null)
            {
                return;
            }

            var item = dataGridRow.DataContext as InstalledAddonDataGridItem;
            item.IsActive = checkBox.IsChecked ?? false;
        }

        private void CheckBox_Checked(object sender, RoutedEventArgs e)
        {
            var checkBox = (CheckBox) e.OriginalSource;
            var dataGridRow = VisualTreeHelpers.FindAncestor<DataGridRow>(checkBox);

            if (dataGridRow == null)
            {
                return;
            }

            var item = dataGridRow.DataContext as InstalledAddonDataGridItem;
            item.IsActive = checkBox.IsChecked ?? false;
        }

        private void MenuOpenLocation_Click(object sender, RoutedEventArgs e)
        {
            foreach (InstalledAddonDataGridItem item in Grid.SelectedItems)
            {
                var addon = item.Addon;
                if (addon != null && addon.IsValid())
                {
                    Process.Start(addon.IsLocal ? Path.GetDirectoryName(addon.ProjectFilePath) : addon.Url);
                }
            }
        }

        private void MenuCopyLocation_Click(object sender, RoutedEventArgs e)
        {
            var locationString =
                Grid.SelectedItems.Cast<InstalledAddonDataGridItem>()
                    .Select(item => item.Addon)
                    .Where(addon => addon != null && addon.IsValid())
                    .Aggregate("",
                        (current, addon) =>
                            current + (addon.IsLocal ? Path.GetDirectoryName(addon.ProjectFilePath) : addon.Url + "\n"));
            Clipboard.SetText(locationString);
        }

        private void MenuRecompileSelected_Click(object sender, RoutedEventArgs e)
        {
            AddonUpdateRoutine.UpdateAddons(
                (from InstalledAddonDataGridItem item in Grid.SelectedItems select item.Addon).ToArray(), true);
        }

        private void MenuRecompileAll_Click(object sender, RoutedEventArgs e)
        {
            AddonUpdateRoutine.UpdateAddons(Settings.Instance.InstalledAddons.ToArray(), true);
        }

        private void MenuUpdateSelected_Click(object sender, RoutedEventArgs e)
        {
            AddonUpdateRoutine.UpdateAddons(
                (from InstalledAddonDataGridItem item in Grid.SelectedItems select item.Addon).ToArray());
        }

        private void MenuUpdateAll_Click(object sender, RoutedEventArgs e)
        {
            AddonUpdateRoutine.UpdateAddons(Settings.Instance.InstalledAddons.ToArray(), true);
        }

        private void MenuRemoveSelected_Click(object sender, RoutedEventArgs e)
        {
            var selectedItems = new InstalledAddonDataGridItem[Grid.SelectedItems.Count];
            Grid.SelectedItems.CopyTo(selectedItems, 0);

            foreach (InstalledAddonDataGridItem item in selectedItems)
            {
                Settings.Instance.InstalledAddons.UninstallAddon(item.Addon);
            }
        }

        private void SearchTextBox_OnTextChanged(object sender, TextChangedEventArgs e)
        {
            var searchText = SearchTextBox.Text;
            var view = CollectionViewSource.GetDefaultView(Items);
            searchText = searchText.Replace("*", "(.*)");
            view.Filter = obj =>
            {
                try
                {
                    var addon = obj as InstalledAddonDataGridItem;
                    if (addon == null)
                    {
                        return true;
                    }

                    switch (searchText.ToLowerInvariant())
                    {
                        case "checked":
                            return addon.IsActive;
                        case "unchecked":
                            return !addon.IsActive;
                    }

                    var nameMatch = Regex.Match(addon.AssemblyName, searchText, RegexOptions.IgnoreCase);
                    //var locationMatch = Regex.Match(addon.Location, searchText, RegexOptions.IgnoreCase);
                    //var authorMatch = Regex.Match(addon.Author, searchText, RegexOptions.IgnoreCase);

                    return nameMatch.Success; //|| locationMatch.Success || authorMatch.Success;
                }
                catch (Exception)
                {
                    return true;
                }
            };
        }

        private void InstalledAddonsDataGrid_OnPreviewMouseLeftButtonDown(object sender, MouseButtonEventArgs e)
        {
            _prevMousePoint = e.GetPosition(null);
        }

        private void InstalledAddonsDataGrid_OnDrop(object sender, DragEventArgs e)
        {
            if (_prevRowIndex < 0)
            {
                return;
            }

            var index = Grid.GetDataGridItemCurrentRowIndex(e.GetPosition);
            if (index < 0)
            {
                return;
            }

            if (index == _prevRowIndex)
            {
                return;
            }

            if (index == Grid.Items.Count - 1)
            {
                return;
            }

            var movedAddonInGrid = Items[_prevRowIndex];
            Items.RemoveAt(_prevRowIndex);
            Items.Insert(index, movedAddonInGrid);

            var movedAddonInInstance = Settings.Instance.InstalledAddons[_prevRowIndex];
            Settings.Instance.InstalledAddons.RemoveAt(_prevRowIndex);
            Settings.Instance.InstalledAddons.Insert(index, movedAddonInInstance);
        }

        private void InstalledAddonDataGrid_OnDragOver(object sender, DragEventArgs e)
        {
            var scrollviewer = (ScrollViewer) FindChild(Grid, typeof (ScrollViewer));
            var mouseposition = e.GetPosition(Grid);
            if (mouseposition.Y < 10)
            {
                scrollviewer.ScrollToVerticalOffset(scrollviewer.VerticalOffset - 1);
            }

            if (mouseposition.Y > Grid.RenderSize.Height - 10)
            {
                scrollviewer.ScrollToVerticalOffset(scrollviewer.VerticalOffset + 1);
            }
        }

        private void InstalledAddonDataGrid_OnPreviewMouseMove(object sender, MouseEventArgs e)
        {
            if (e.LeftButton != MouseButtonState.Pressed)
            {
                return;
            }

            var position = e.GetPosition(null);
            if (!(Math.Abs(position.X - _prevMousePoint.X) > SystemParameters.MinimumHorizontalDragDistance) &&
                !(Math.Abs(position.Y - _prevMousePoint.Y) > SystemParameters.MinimumVerticalDragDistance))
            {
                return;
            }

            _prevRowIndex = Grid.GetDataGridItemCurrentRowIndex(e.GetPosition);
            if (_prevRowIndex < 0)
            {
                return;
            }

            if (e.IsColumnSelected())
            {
                return;
            }

            Grid.SelectedIndex = _prevRowIndex;

            var selectedAddon = Grid.Items[_prevRowIndex] as InstalledAddonDataGridItem;
            if (selectedAddon == null)
            {
                return;
            }

            const DragDropEffects dragDropEffects = DragDropEffects.Move;
            if (DragDrop.DoDragDrop(Grid, selectedAddon, dragDropEffects) != DragDropEffects.None)
            {
                Grid.SelectedItem = selectedAddon;
            }
        }

        public DependencyObject FindChild(DependencyObject o, Type childType)
        {
            DependencyObject foundChild = null;
            if (o != null)
            {
                var childrenCount = VisualTreeHelper.GetChildrenCount(o);
                for (var i = 0; i < childrenCount; i++)
                {
                    var child = VisualTreeHelper.GetChild(o, i);
                    if (child.GetType() != childType)
                    {
                        foundChild = FindChild(child, childType);
                    }
                    else
                    {
                        foundChild = child;
                        break;
                    }
                }
            }

            return foundChild;
        }
    }
}
