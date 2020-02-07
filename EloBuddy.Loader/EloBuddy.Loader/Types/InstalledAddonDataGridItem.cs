using System.ComponentModel;
using System.IO;
using EloBuddy.Loader.Data;

namespace EloBuddy.Loader.Types
{
    public class InstalledAddonDataGridItem : INotifyPropertyChanged
    {
        public event PropertyChangedEventHandler PropertyChanged;

        public ElobuddyAddon Addon { get; private set; }
        private string mAssemblyName;
        public string AssemblyName
        {
            get { return mAssemblyName; }
            set
            {
                mAssemblyName = value;
                RaisePropertyChanged("AssemblyName");
            }
        }

        private string mAuthor;
        public string Author
        {
            get { return mAuthor; }
            set
            {
                mAuthor = value;
                RaisePropertyChanged("Author");
            }
        }

        private string mType;
        public string Type
        {
            get { return mType; }
            set
            {
                mType = value;
                RaisePropertyChanged("Type");
            }
        }

        private string mVersion;
        public string Version
        {
            get { return mVersion; }
            set
            {
                mVersion = value;
                RaisePropertyChanged("Version");
            }
        }

        private string mLocation;
        public string Location
        {
            get { return mLocation; }
            set
            {
                mLocation = value;
                RaisePropertyChanged("Location");
            }
        }

        public bool IsActive
        {
            get { return Addon.Enabled && IsAvailable; }
            set { Addon.Enabled = value; }
        }

        public string Status
        {
            get { return Addon.State.ToString(); }
        }

        public bool IsAvailable
        {
            get { return Addon.IsAvailable; }
        }

        public InstalledAddonDataGridItem(ElobuddyAddon addon)
        {
            if (!addon.IsAvailable)
            {
                addon.SetState(AddonState.BuddyOnly);
            }

            Addon = addon;
            Refresh();
        }

        public void Refresh()
        {
            AssemblyName = Addon.GetProjectName();
            Author = Addon.Author;
            Type = Addon.Type.ToString();
            Version = Addon.Version;
            Location = Addon.IsLocal ? Path.GetDirectoryName(Addon.ProjectFilePath) : Addon.Url;
            RaisePropertyChanged("Status");
        }

        public void RaisePropertyChanged(string propName)
        {
            if (PropertyChanged != null)
                PropertyChanged(this, new PropertyChangedEventArgs(propName));
        }
    }
}
