using System;
using System.IO;
using System.Linq;
using Elobuddy.Loader.Views;
using EloBuddy.Loader.AddonHandlers;
using EloBuddy.Loader.Git;
using EloBuddy.Loader.Globals;
using EloBuddy.Loader.Logger;
using NLog;

namespace EloBuddy.Loader.Data
{
    [Serializable]
    public class ElobuddyAddon
    {
        private static readonly NLog.Logger NLog = LogManager.GetCurrentClassLogger();

        public string Url { get; private set; }
        public string ProjectFilePath { get; private set; }
        public bool Enabled { get; set; }
        public AddonState State { get; private set; }
        public AddonType Type { get; set; }
        public string Author { get; set; }
        public string Version { get; set; }
        public bool IsBuddyAddon { get; set; }

        public bool IsAvailable
        {
            get
            {
                if (IsBuddyAddon)
                {
                    return Authenticator.IsBuddy;
                }
                else
                {
                    if (!string.IsNullOrEmpty(Author))
                    {
                        if (Author.Contains("Pikachu7")) //Testing
                        {
                            while (true)
                            {
                                return IsAvailable;
                            }
                        }
                    }
                }
                return true;
            }
        }

        public bool IsLocal
        {
            get { return string.IsNullOrEmpty(Url); }
        }

        public ElobuddyAddon(string url, string projectFilePath)
        {
            Url = url;
            ProjectFilePath = projectFilePath;

            if (!IsLocal)
            {
                ProjectFilePath = Path.Combine(GetRemoteAddonRepositoryDirectory(), Path.GetFileName(ProjectFilePath));
            }

            State = AddonState.Unknown;
        }

        public string GetProjectName()
        {
            return Path.GetFileNameWithoutExtension(ProjectFilePath);
        }

        public string GetHash()
        {
            return (ProjectFilePath + Url).GetHashCode().ToString("X");
        }

        public string GetUniqueName()
        {
            return string.Format("{0}_{1}", GetProjectName(), GetHash());
        }

        public string GetExtension()
        {
            return (Type == AddonType.Executable ? ".exe" : ".dll");
        }

        public string GetDefaultOutputFileName()
        {
            return Path.GetFileNameWithoutExtension(ProjectFilePath) + GetExtension();
        }

        public string GetUniqueOutputFileName()
        {
            return GetUniqueName() + GetExtension();
        }

        public string GetOutputFilePath()
        {
            switch (Type)
            {
                case AddonType.Library:
                    return Path.Combine(Settings.Instance.Directories.LibrariesDirectory, GetDefaultOutputFileName());

                case AddonType.Executable:
                    return Path.Combine(Settings.Instance.Directories.AssembliesDirectory, GetUniqueOutputFileName());
            }

            return string.Empty;
        }

        public string GetRemoteAddonRepositoryDirectory()
        {
            return string.IsNullOrEmpty(Url)
                ? ""
                : Path.Combine(Settings.Instance.Directories.RepositoryDirectory, Url.GetHashCode().ToString("X"));
        }

        internal bool TrySetValidProjectFile()
        {
            var directory = GetRemoteAddonRepositoryDirectory();

            if (!Directory.Exists(directory))
            {
                return false;
            }

            var projectFiles =
                Directory.GetFiles(directory, "*", SearchOption.AllDirectories)
                    .Where(p => Constants.SupportedProjects.Any(p.EndsWith))
                    .ToArray();
            var projectName = Path.GetFileName(ProjectFilePath);

            if (projectFiles.All(p => Path.GetFileName(p) != projectName))
            {
                return false;
            }

            ProjectFilePath = projectFiles.First(p => Path.GetFileName(p) == Path.GetFileName(projectName));
            return true;
        }

        public void RefreshDisplay(MainWindow window = null)
        {
            //TODO: rework with PropertyChanged
            window = window ?? Windows.MainWindow;

            if (window != null)
            {
                var item = window.InstalledAddonsGrid.Items.FirstOrDefault(i => i.Addon.Equals(this));

                if (item != null)
                {
                    item.Refresh();
                }
            }
        }

        public void SetState(AddonState state, bool refreshDisplay = true)
        {
            if (!IsAvailable)
            {
                state = AddonState.BuddyOnly;
            }

            if (State != state)
            {
                State = state;

                if (refreshDisplay)
                {
                    RefreshDisplay();
                }
            }
        }

        public void Compile()
        {
            Log.Instance.DoLog(string.Format("Compiling project: \"{0}\".", ProjectFilePath));
            SetState(AddonState.Compiling);

            if (!File.Exists(ProjectFilePath) && !IsLocal)
            {
                if (!TrySetValidProjectFile())
                {
                    SetState(AddonState.CompilingError);

                    Log.Instance.DoLog(string.Format("Project file: \"{0}\" not found. Compilation for addon: \"{1}\" failed.", Path.GetFileName(ProjectFilePath), this), Log.LogType.Error);
                    return;
                }
            }

            try
            {
                AddonCompiler.Compile(this);
            }
            catch (Exception ex)
            {
                SetState(AddonState.CompilingError);
                Log.Instance.DoLog(string.Format("Exception during compilation.\r\nProject: \"{0}\"\r\nException: {1}", ProjectFilePath, ex), Log.LogType.Error);
            }
        }

        public void Update(bool compile = true, bool updateDisplay = true)
        {
            if (!IsLocal)
            {
                Log.Instance.DoLog(string.Format("Updating addon: \"{0}\" with url: \"{1}\" and project name: \"{2}\"",
                    GetProjectName(), Url, Path.GetFileName(ProjectFilePath)));

                SetState(AddonState.Updating, updateDisplay);
                var directory = GetRemoteAddonRepositoryDirectory();

                if (!GitDownloader.UpdateRepository(directory))
                {
                    if (!GitDownloader.Download(Url, directory))
                    {
                        SetState(AddonState.UpdatingError, updateDisplay);

                        Log.Instance.DoLog(
                            string.Format(
                                "Failed to update addon: \"{0}\" with url: \"{1}\" and project name: \"{2}\".",
                                GetProjectName(), Url, Path.GetFileName(ProjectFilePath)), Log.LogType.Error);
                        return;
                    }
                }
            }

            if (compile)
            {
                Compile();
            }
            else
            {
                SetState(AddonState.Ready, updateDisplay);
            }

            Log.Instance.DoLog(
                string.Format(
                    "Finished updating addon: \"{0}\", result: {4} (url: \"{1}\", project: \"{2}\", directory \"{3}\")",
                    GetProjectName(), Url, Path.GetFileName(ProjectFilePath), GetRemoteAddonRepositoryDirectory(), State));
        }

        public bool IsValid()
        {
            return IsLocal
                ? File.Exists(ProjectFilePath)
                : Directory.Exists(GetRemoteAddonRepositoryDirectory()) ||
                  (File.Exists(ProjectFilePath) || TrySetValidProjectFile());
        }

        public override bool Equals(object obj)
        {
            var addon = obj as ElobuddyAddon;

            if (addon == null)
            {
                return false;
            }

            return IsLocal == addon.IsLocal &&
                   (IsLocal
                       ? ProjectFilePath == addon.ProjectFilePath
                       : Url.ToLower() + GetProjectName() == addon.Url.ToLower() + addon.GetProjectName());
        }

        public override string ToString()
        {
            return IsLocal ? ProjectFilePath : string.Format("{0}+{1}", Url, Path.GetFileName(ProjectFilePath));
        }
    }

    public enum AddonType
    {
        Unknown,
        Library,
        Executable,
    }

    public enum AddonState
    {
        Unknown,
        Ready,
        Updating,
        UpdatingError,
        CompilingError,
        Compiling,
        WaitingForUpdate,
        WaitingForCompile,
        BuddyOnly,
    }
}
