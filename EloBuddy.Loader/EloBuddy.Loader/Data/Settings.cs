using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Diagnostics;
using System.IO;
using System.Linq;
using System.Timers;
using System.Windows;
using EloBuddy.Loader.Globals;
using EloBuddy.Loader.Logger;
using EloBuddy.Loader.Utils;
using NLog;

namespace EloBuddy.Loader.Data
{
    [Serializable]
    public class Settings
    {
        static Settings()
        {
            SaveTimer = new Timer(60000);
            SaveTimer.Elapsed += OnSaveTimerElapsed;
            SaveTimer.Start();
        }

        private static Timer SaveTimer { get; set; }

        private static void OnSaveTimerElapsed(object sender, ElapsedEventArgs elapsedEventArgs)
        {
            Save();
        }

        public bool DisableTelemetry { get; set; }
        public bool DisableAutomaticUpdates { get; set; }
        public bool RememberCredentials { get; set; }
        public bool EnableInjection { get; set; }
        public bool UpdateAssembliesOnStart { get; set; }
        public bool DeveloperMode { get; set; }
        public Language? SelectedLanguage { get; set; }
        public string LastLoLReleasesFolderPath { get; set; }

        public SettingsConfiguration Configuration { get; set; }
        public Credentials UserCredentials { get; set; }
        public SettingsDirectories Directories { get; private set; }
        public SettingsUI Ui { get; internal set; }
        public InstalledAddonList InstalledAddons { get; private set; }

        private static Settings _instance;

        public static Settings Instance
        {
            get
            {
                if (_instance == null)
                {
                    _instance = new Settings();
                }

                return _instance;
            }
        }

        public static string LastSave { get; private set; }

        public static bool SaveRequired()
        {
            try
            {
                return LastSave != Convert.ToBase64String(Serialization.Serialize(Instance));
            }
            catch (Exception)
            {
                return true;
            }
        }

        public static void Save()
        {
            if (!SaveRequired())
            {
                return;
            }

            Log.Instance.DoLog("Serializing settings.");

            var bytes = Serialization.Serialize(Instance);

            if (bytes != null)
            {
                try
                {
                    File.WriteAllBytes(Instance.Directories.SettingsFilePath, bytes);
                    LastSave = Convert.ToBase64String(bytes);
                }
                catch (Exception ex)
                {
                    Log.Instance.DoLog(
                        string.Format("Failed to save settings.\r\nSettingsFilePath: {0}\r\nException: {1}\r\n",
                            Instance.Directories.SettingsFilePath, ex), Log.LogType.Error);
                }
            }
            else
            {
                Log.Instance.DoLog(
                    string.Format("Failed to serialize settings. SettingsFilePath: \"{0}\".",
                        Instance.Directories.SettingsFilePath), Log.LogType.Error);
            }
        }

        public static void Load()
        {
            if (File.Exists(Instance.Directories.SettingsFilePath))
            {
                Log.Instance.DoLog("Deserializing settings.");

                try
                {
                    var savedSettings = (Settings) Serialization.Deserialize(File.ReadAllBytes(Instance.Directories.SettingsFilePath));

                    if (savedSettings != null)
                    {
                        savedSettings.Directories.Verify();
                        _instance = savedSettings;
                    }
                    else
                    {
                        Log.Instance.DoLog(
                            string.Format("Failed to deserialize settings. SettingsFilePath: \"{0}\".",
                                Instance.Directories.SettingsFilePath), Log.LogType.Error);

                        try
                        {
                            Log.Instance.DoLog(string.Format("Deleting settings file: \"{0}\".",
                                Instance.Directories.SettingsFilePath));

                            File.Delete(Instance.Directories.SettingsFilePath);
                        }
                        catch (Exception ex)
                        {
                            Log.Instance.DoLog(
                                string.Format("Failed to delete settings file: \"{0}\"\r\nException: {1}\r\n",
                                    Instance.Directories.SettingsFilePath, ex), Log.LogType.Error);
                        }
                    }
                }
                catch (Exception ex)
                {
                    Log.Instance.DoLog(
                        string.Format("Failed to load settings.\r\nSettingsFilePath: {0}\r\nException: {1}\r\n",
                            Instance.Directories.SettingsFilePath, ex), Log.LogType.Error);
                }
            }
        }

        private Settings()
        {
            RememberCredentials = true;
            EnableInjection = true;
            UpdateAssembliesOnStart = true;
            SelectedLanguage = Language.Default;

            Configuration = new SettingsConfiguration();
            Directories = new SettingsDirectories();
            Ui = new SettingsUI();
            InstalledAddons = new InstalledAddonList();
            UserCredentials = new Credentials();

            Directories.Verify();
        }
    }

    [Serializable]
    public class SettingsDirectories
    {
        public string AppDataDirectory { get; private set; } // elobuddy appdata directory

        public string LoaderFilePath // loader current path
        {
            get { return Process.GetCurrentProcess().MainModule.FileName; }
        }

        public string CurrentDirectory // current directory (loader directory)
        {
            get { return AppDomain.CurrentDomain.BaseDirectory; }
        }

        public string DependenciesDirectory // dependencies directory
        {
            get { return Path.Combine(CurrentDirectory, "Dependencies") + "\\"; }
        }

        public string SettingsDirectory // settings directory
        {
            get { return Path.Combine(CurrentDirectory, "Settings") + "\\"; }
        }

        public string LanguagesDirectory // languages directory
        {
            get { return Path.Combine(SettingsDirectory, "Languages") + "\\"; }
        }

        public string SettingsFilePath // settings file path
        {
            get { return Path.Combine(SettingsDirectory, "settings.data"); }
        }

        public string LogsDirectory // log direcotry
        {
            get { return Path.Combine(CurrentDirectory, "Logs") + "\\"; }
        }

        public string SystemDirectory // system directory
        {
            get { return Path.Combine(CurrentDirectory, "System") + "\\"; }
        }

        public string CoreDllPath // core dll path (system folder)
        {
            get { return Path.Combine(SystemDirectory, "EloBuddy.Core.dll"); }
        }

        public string EloBuddyDllPath // elobuddy dll path (system folder)
        {
            get { return Path.Combine(SystemDirectory, "EloBuddy.dll"); }
        }

        public string SandboxDllPath // sandbox dll path (system folder)
        {
            get { return Path.Combine(SystemDirectory, "EloBuddy.Sandbox.dll"); }
        }

        public string SdkDllPath // sdk dll path (system folder)
        {
            get { return Path.Combine(SystemDirectory, "EloBuddy.SDK.dll"); }
        }

        public string RepositoryDirectory // repository directory, all remote installed addons are saved here
        {
            get { return Path.Combine(AppDataDirectory, "Repositories") + "\\"; }
        }

        public string TempDirectory // temp directory in appdata, used for storing temporary files
        {
            get { return Path.Combine(AppDataDirectory, "Temp") + "\\"; }
        }

        public string AssembliesDirectory { get; set; } // compiled addons are placed here

        public string LibrariesDirectory // all dlls from system folder are placed here
        {
            get { return Path.Combine(AssembliesDirectory, "Libraries") + "\\"; }
        }

        public string TempCoreDirectory { get; set; } // temp directory for injection
        public string TempCoreDllPath { get; set; } // temp core dll path
        public string TempSandboxDllPath { get; set; } // temp sandbox dll path
        public string TempEloBuddyDllPath { get; set; } // temp elobuddy dll path

        internal SettingsDirectories()
        {
            AppDataDirectory = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.ApplicationData),
                "EloBuddy\\");
            AssembliesDirectory = Path.Combine(AppDataDirectory, "Addons") + "\\";
            CreateDirectories();
        }

        internal void Verify()
        {
            var newInstance = new SettingsDirectories();

            foreach (var property in GetType().GetProperties())
            {
                var propertyValue = property.GetValue(this);

                if (propertyValue == null && property.GetSetMethod() != null)
                {
                    property.SetValue(this, property.GetValue(newInstance));
                }
            }

            CreateDirectories();
        }

        private void CreateDirectories()
        {
            foreach (var property in GetType().GetProperties())
            {
                var propertyName = property.Name;
                var propertyValue = property.GetValue(this) as string;

                if (propertyName.ToLower().Contains("directory") && propertyValue != null)
                {
                    if (!Directory.Exists(propertyValue))
                        Directory.CreateDirectory(propertyValue);
                }
            }
        }
    }

    [Serializable]
    public class SettingsUI
    {
        public bool FirstTimeWizardRan { get; internal set; }
        public Size MainWindowSize { get; set; }
        public double MainWindowPositionLeft { get; set; }
        public double MainWindowPositionTop { get; set; }
        public bool EnableDeveloperOptions { get; set; }

        internal SettingsUI()
        {
            //Default Settings
            MainWindowPositionLeft = double.NaN;
            MainWindowPositionTop = double.NaN;
            MainWindowSize = Size.Empty;
            EnableDeveloperOptions = false;
        }
    }

    [Serializable]
    public class InstalledAddonList : List<ElobuddyAddon>
    {
        private static readonly NLog.Logger NLog = LogManager.GetCurrentClassLogger();

        public void RemoveInvalidAddons()
        {
            var invalidAddons = this.Where(a => !a.IsValid()).ToArray();
            foreach (var addon in invalidAddons)
            {
                UninstallAddon(addon);
            }
        }

        private static BackgroundWorker _uninstallBackgroundWorker;

        public InstalledAddonList()
        {
            _uninstallBackgroundWorker = new BackgroundWorker()
            {
                WorkerSupportsCancellation = false,
                WorkerReportsProgress = false
            };
        }

        public bool IsAddonInstalled(ElobuddyAddon addon)
        {
            return this.Any(a => a.Equals(addon));
        }

        public bool IsAddonInstalled(string url, string projectFile)
        {
            return this.Any(a => a.Equals(new ElobuddyAddon(url, projectFile)));
        }

        public void UninstallAddon(ElobuddyAddon addon)
        {
            Remove(addon);

            if (Windows.MainWindow != null)
            {
                Windows.MainWindow.RemoveAddon(addon);
            }

            _uninstallBackgroundWorker.DoWork += delegate
            {
                if (File.Exists(addon.GetOutputFilePath()))
                {
                    try
                    {
                        File.Delete(addon.GetOutputFilePath());
                    }
                    catch (Exception)
                    {
                        // ignored
                    }
                }

                var directory = addon.GetRemoteAddonRepositoryDirectory();
                if (this.Count(a => a.GetRemoteAddonRepositoryDirectory() == directory) == 0)
                {
                    try
                    {
                        DirectoryHelper.DeleteDirectory(directory);
                    }
                    catch (Exception)
                    {
                        // ignored
                    }
                }
            };

            if (!_uninstallBackgroundWorker.IsBusy)
            {
                _uninstallBackgroundWorker.RunWorkerAsync();
            }
        }

        public void UninstallAddon(int index)
        {
            UninstallAddon(this[index]);
        }

        public bool InstallAddon(ElobuddyAddon addon)
        {
            Log.Instance.DoLog(string.Format("Installing addon: \"{0}\".", addon));

            if (this.Any(a => a.Equals(addon)))
            {
                Log.Instance.DoLog(
                    string.Format("Failed to install addon: \"{0}\", this addon is already installed!", addon));

                return false;
            }

            addon.Compile();

            if (addon.State == AddonState.Ready)
            {
                Add(addon);

                if (Windows.MainWindow != null)
                {
                    Windows.MainWindow.AddLastAddon();
                }

                Log.Instance.DoLog(string.Format("Successfully installed addon: \"{0}\"", addon));
                Settings.Save();
                return true;
            }

            Log.Instance.DoLog(string.Format(
                    "Failed to install addon: \"{0}\". Addon state: \"{1}\". The addon either did not update or did not compile!",
                    addon, addon.State), Log.LogType.Error);

            return false;
        }

        public bool InstallAddon(string url, string projectFile)
        {
            return InstallAddon(new ElobuddyAddon(url, projectFile));
        }

        public void RecompileSelectedAddons()
        {
            foreach (var addon in this.Where(a => a.Enabled).OrderByDescending(a => (int) a.Type).Reverse())
            {
                addon.Compile();
            }

            NLog.Info("Recompile selected Addons");
        }
    }

    [Serializable]
    public class Credentials
    {
        public string Username { get; private set; }
        public string Password { get; private set; }

        public bool IsEmpty
        {
            get { return string.IsNullOrEmpty(Username) || string.IsNullOrEmpty(Password); }
        }

        public Credentials()
        {
        }

        public Credentials(string username, string password)
        {
            Username = username;
            Password = password;
        }

        public override string ToString()
        {
            return string.Format("{0}+{1}", Username, Password);
        }
    }

    [Serializable]
    public class SettingsConfiguration
    {
        public bool AntiAfk { get; set; }
        public bool Console { get; set; }
        public bool ExtendedZoom { get; set; }
        public bool TowerRange { get; set; }
        public bool MovementHack { get; set; }
        public bool DrawWaterMark { get; set; }
        public bool StreamingMode { get; set; }
        public bool DisableChatFunction { get; set; }
        public bool DisableRangeIndicator { get; set; }
        public int MenuKey { get; set; }
        public int MenuToggleKey { get; set; }
        public int ReloadAndRecompileKey { get; set; }
        public int ReloadKey { get; set; }
        public int UnloadKey { get; set; }

        public SettingsConfiguration()
        {
            // Default Settings
            AntiAfk = true;
            Console = false;
            ExtendedZoom = false;
            TowerRange = true;
            MovementHack = false;
            DrawWaterMark = true;
            StreamingMode = false;
            DisableRangeIndicator = true;
            MenuKey = 0x0;
            MenuToggleKey = 0x1;
            ReloadAndRecompileKey = 0x77;
            ReloadKey = 0x74;
            UnloadKey = 0x75;
        }
    }

    public enum Language
    {
        Default = 0,
        English = 100,
        Arabic = 200,
        German = 300,
        Spanish = 400,
        French = 500,
        Italian = 600,
        Polish = 700,
        Hungarian = 800,
        Dutch = 900,
        Swedish = 1000,
        Portuguese = 1100,
        Slovenian = 1200,
        Romanian = 1300,
        Vietnamese = 1400,
        Turkish = 1500,
        Chinese = 1600,
        ChineseTraditional = 1610,
        Korean = 1700,
        Balkan = 1800,
        Greek = 1900,
    }
}
