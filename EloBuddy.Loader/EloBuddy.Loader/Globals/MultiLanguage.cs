using System.ComponentModel;
using System.IO;
using System.Xaml;
using EloBuddy.Loader.Data;
using EloBuddy.Loader.Logger;
using EloBuddy.Loader.Utils;

namespace EloBuddy.Loader.Globals
{
    public class LocalizedText : INotifyPropertyChanged
    {
        public string LabelWelcome { get; set; }
        public string LabelEloBuddyStatus { get; set; }
        public string StatusStringUpdated { get; set; }
        public string StatusStringOutdated { get; set; }
        public string StatusStringFailedToLocate { get; set; }
        public string StatusStringUnknown { get; set; }
        public string TitleSettings { get; set; }
        public string TabGeneral { get; set; }
        public string TabHotkeys { get; set; }
        public string TabGameSettings { get; set; }
        public string TabLogs { get; set; }
        public string GroupLabelFileSettings { get; set; }
        public string GroupLabelLanguage { get; set; }
        public string GroupLabelMiscellaneous { get; set; }
        public string GroupLabelGameSettings { get; set; }
        public string GroupLabelLogs { get; set; }
        public string LabelInstallLocation { get; set; }
        public string LabelDeveloperOptions { get; set; }
        public string LabelSendAnonymousData { get; set; }
        public string LabelUpdateAddonsStartup { get; set; }
        public string LabelDisableAutomaticUpdates { get; set; }
        public string LabelInject { get; set; }
        public string LabelAntiAFK { get; set; }
        public string LabelExtendedZoom { get; set; }
        public string LabelMovementHack { get; set; }
        public string LabelConsole { get; set; }
        public string LabelChatPrints { get; set; }
        public string LabelTowerRange { get; set; }
        public string LabelDisableRangeIndicator { get; set; }
        public string LabelWatermark { get; set; }
        public string LabelStreamingMode { get; set; }
        public string ButtonDone { get; set; }
        public string ButtonCancel { get; set; }
        public string TitleAddonInstaller { get; set; }
        public string LabelLocalAddon { get; set; }
        public string LabelRemoteAddon { get; set; }
        public string ButtonCancel2 { get; set; }
        public string ButtonInstall { get; set; }
        public string TitleRemoteAddonInstaller { get; set; }
        public string LabelSelectAddon { get; set; }
        public string LabelNoAddons { get; set; }
        public string DataGridButtonUpdateAddons { get; set; }
        public string DataGridButtonInstallAddon { get; set; }
        public string DataGridButtonUninstallAddon { get; set; }
        public string DataGridContextMenuOpenLocation { get; set; }
        public string DataGridContextMenuCopyLocation { get; set; }
        public string DataGridContextMenuRecompile { get; set; }
        public string DataGridContextMenuUpdate { get; set; }
        public string DataGridContextMenuUninstall { get; set; }
        public string DataGridCollumnActive { get; set; }
        public string DataGridCollumnAddon { get; set; }
        public string DataGridCollumnAuthor { get; set; }
        public string DataGridCollumnType { get; set; }
        public string DataGridCollumnVersion { get; set; }
        public string DataGridCollumnLocation { get; set; }
        public string DataGridCollumnStatus { get; set; }
        public string TaskDownloadingAddon { get; set; }
        public string TaskInstallingLocalAddon { get; set; }
        public string TaskInstallingRemoteAddon { get; set; }
        public string ErrorFailedToDownloadAddon { get; set; }
        public string ErrorFailedToInstallAddon { get; set; }
        public string ErrorInstalationDisabled { get; set; }
        public string TitleTaskAddonInstaller { get; set; }
        public string ErrorUpdateFailedToDownloadFile { get; set; }
        public string TitleMsgBoxUpdateFailedToDownloadFile { get; set; }
        public string ErrorUpdateFailedToDeserialize { get; set; }
        public string TitleMsgBoxUpdateFailedToDeserialize { get; set; }
        public string ErrorUpdateFailedToCopyFile { get; set; }
        public string UpdateStatusLoader { get; set; }
        public string UpdateDetailsLoader { get; set; }
        public string UpdateDetailsLoaderDownloading { get; set; }
        public string UpdateStatusSystemFiles { get; set; }
        public string UpdateDetailsCheckingFile { get; set; }
        public string UpdateDetailsDownloadingFile { get; set; }
        public string UpdateStatusPatchFiles { get; set; }
        public string UpdateStatusInstallingFiles { get; set; }
        public string UpdateDetailsInstallingFiles { get; set; }
        public string ErrorInjectionHashMissmatch { get; set; }
        public string LabelForgotPassword { get; set; }
        public string LabelCreateAccount { get; set; }
        public string LabelRemember { get; set; }
        public string DataGridCollumnInstall { get; set; }
        public string TitleFirstTimeWizard { get; set; }
        public string ButtonSkip { get; set; }
        public string ButtonNext { get; set; }

        public event PropertyChangedEventHandler PropertyChanged;

        public void RaisePropertyChanged(string propName)
        {
            if (PropertyChanged != null)
            {
                PropertyChanged(this, new PropertyChangedEventArgs(propName));
            }
        }

        public void Refresh()
        {
            foreach (var prop in GetType().GetProperties())
            {
                RaisePropertyChanged(prop.Name);
            }
        }

        public void Save(string file)
        {
            using (TextWriter writer = File.CreateText(file))
            {
                XamlServices.Save(writer, this);
            }
        }

        public static LocalizedText LoadDefault()
        {
            return new LocalizedText
            {
                LabelWelcome = "Welcome back, Buddy!",
                LabelEloBuddyStatus = "EloBuddy Status:",
                StatusStringUpdated = "Updated",
                StatusStringOutdated = "Outdated",
                StatusStringFailedToLocate = "Failed to locate install location",
                StatusStringUnknown = "Unknown",
                TitleSettings = "Settings & Preferences",
                TabGeneral = "General",
                TabHotkeys = "Hotkeys",
                TabGameSettings = "In-Game Settings",
                TabLogs = "Logs",
                GroupLabelFileSettings = "File Settings",
                GroupLabelLanguage = "Language",
                GroupLabelMiscellaneous = "Miscellaneous",
                GroupLabelGameSettings = "Game Settings",
                GroupLabelLogs = "Loader Logs",
                LabelInstallLocation = "Addons Install Location",
                LabelDeveloperOptions = "Enable developer options",
                LabelUpdateAddonsStartup = "Update addons on start",
                LabelDisableAutomaticUpdates = "Disable automatic updates",
                LabelInject = "Inject",
                LabelAntiAFK = "Enable Anti AFK",
                LabelExtendedZoom = "Enable Extended Zoom",
                LabelMovementHack = "Enable Movement Hack",
                LabelConsole = "Enable Console",
                LabelChatPrints = "Disable Chat Prints",
                LabelTowerRange = "Show Tower Ranges",
                LabelWatermark = "Draw Watermark",
                LabelStreamingMode = "Enable Streaming Mode",
                ButtonDone = "DONE",
                ButtonCancel = "CANCEL",
                TitleAddonInstaller = "Addon Installer",
                LabelLocalAddon = "Local Addon",
                LabelRemoteAddon = "Remote Addon",
                ButtonCancel2 = "Cancel",
                ButtonInstall = "Install",
                TitleRemoteAddonInstaller = "Addon Installer",
                LabelSelectAddon = "Select addon:",
                LabelNoAddons = "No valid addons found",
                DataGridButtonUpdateAddons = "UPDATE ADDONS",
                DataGridButtonInstallAddon = "INSTALL ADDON",
                DataGridButtonUninstallAddon = "UNINSTALL SELECTED ADDONS",
                DataGridContextMenuOpenLocation = "Open Location",
                DataGridContextMenuCopyLocation = "Copy Location",
                DataGridContextMenuRecompile = "Recompile Selected",
                DataGridContextMenuUpdate = "Update Selected",
                DataGridContextMenuUninstall = "Uninstall Selected",
                DataGridCollumnActive = "Active",
                DataGridCollumnAddon = "Addon",
                DataGridCollumnAuthor = "Author",
                DataGridCollumnType = "Type",
                DataGridCollumnVersion = "Version",
                DataGridCollumnLocation = "Location",
                DataGridCollumnStatus = "Status",
                TaskDownloadingAddon = "Downloading remote addon: ",
                TaskInstallingLocalAddon = "Installing local addon: ",
                TaskInstallingRemoteAddon = "Installing remote addon: ",
                ErrorFailedToDownloadAddon = "Failed to download addon \"{0}\", please check the logs.",
                ErrorFailedToInstallAddon = "Failed to install addon \"{0}\", please check the logs.",
                ErrorInstalationDisabled =
                    "Elobuddy cannot install any addons at the moment. Please resolve any update issues first.",
                TitleTaskAddonInstaller = "Addon installer",
                ErrorUpdateFailedToDownloadFile = "Failed to download file: {0}, the loader will now exit.",
                TitleMsgBoxUpdateFailedToDownloadFile = "Download Error",
                ErrorUpdateFailedToDeserialize = "Failed to deserialize update data!\r\n Exception: {0}",
                TitleMsgBoxUpdateFailedToDeserialize = "Deserialization error",
                ErrorUpdateFailedToCopyFile = "Failed to install file: {0}. Make sure elobuddy is not injected.",
                UpdateStatusLoader = "Updating Elobuddy.Loader",
                UpdateDetailsLoader = "Downloading update data...",
                UpdateDetailsLoaderDownloading = "Downloading new Elobuddy.Loader version.",
                UpdateStatusSystemFiles = "Updating system files",
                UpdateDetailsCheckingFile = "Checking file: {0}",
                UpdateDetailsDownloadingFile = "Downloading file: {0}",
                UpdateStatusPatchFiles = "Updating patch files",
                UpdateStatusInstallingFiles = "Installing files",
                UpdateDetailsInstallingFiles = "Copying files, please wait...",
                ErrorInjectionHashMissmatch =
                    "League of Legends hash missmatch!\n\n League Hash: {0}\n Update Hash: {1}",
                LabelForgotPassword = "Forgot your password?",
                LabelCreateAccount = "Create Account",
                LabelRemember = "Remember me",
                LabelDisableRangeIndicator = "Disable Range Indicator",
                DataGridCollumnInstall = "Install",
                TitleFirstTimeWizard = "Elobuddy settings wizard",
                ButtonSkip = "Skip",
                ButtonNext = "Next",
                LabelSendAnonymousData = "Send anonymous data and statistics",
            };
        }

        public static LocalizedText LoadFrom(string path)
        {
            var @default = LoadDefault();

            if (string.IsNullOrEmpty(path) || !File.Exists(path))
            {
                return @default;
            }

            LocalizedText text;
            try
            {
                using (TextReader reader = File.OpenText(path))
                {
                    text = (LocalizedText) XamlServices.Load(reader);
                }
            }
            catch
            {
                Log.Instance.DoLog(string.Format("Failed to deserialize language file: {0}", path), Log.LogType.Error);

                return @default;
            }

            foreach (var p in text.GetType().GetProperties())
            {
                if (string.IsNullOrEmpty((string) p.GetValue(text)))
                {
                    p.SetValue(text, p.GetValue(@default));
                }
            }

            return text;
        }

        public static LocalizedText Load(Language language)
        {
            if (language == Language.Default)
            {
                language = EnvironmentHelper.GetDefaultLanguage();
            }

            return LoadFrom(Path.Combine(Settings.Instance.Directories.LanguagesDirectory, language + ".xml"));
        }
    }

    public class MultiLanguage
    {
        private static LocalizedText _text;

        public static LocalizedText Text
        {
            get { return _text ?? (_text = LocalizedText.LoadFrom("0en.xaml")); }

            set
            {
                _text = value;
                _text.Refresh();
            }
        }
    }
}
