using System;
using System.Threading.Tasks;
using EloBuddy.Loader.Data;
using EloBuddy.Loader.Globals;
using EloBuddy.Loader.Logger;
using EloBuddy.Loader.Routines;
using EloBuddy.Loader.Views;

namespace EloBuddy.Loader.Update
{
    public static class LoaderUpdate
    {
        public static bool IsRunning { get; private set; }
        public static bool UpToDate { get; set; }
        public static string LeagueHash { get; set; }
        public static string LeagueVersion { get; set; }
        public static string CoreHash { get; set; }
        public static string CoreBuild { get; set; }

        internal static string LatestUpdateJson { get; private set; }
        internal static string LatestCoreJson { get; private set; }

        public static bool LeaguePathFound
        {
            get { return !string.IsNullOrEmpty(LeagueHash) && !string.IsNullOrEmpty(LeagueVersion); }
        }

        public static string StatusString
        {
            get
            {
                if (LeagueHash == null)
                {
                    return MultiLanguage.Text.StatusStringUnknown;
                }

                if (!LeaguePathFound)
                {
                    return MultiLanguage.Text.StatusStringFailedToLocate;
                }

                return string.Format("{0}, League Version: {1} (Core: {2}{3})",
                    UpToDate ? MultiLanguage.Text.StatusStringUpdated : MultiLanguage.Text.StatusStringOutdated,
                    LeagueVersion, CoreBuild != "Unknown" ? "#" : string.Empty, CoreBuild);
            }
        }

        static LoaderUpdate()
        {
        }

        public static void UpdateSystem()
        {
            IsRunning = true;
            Log.Instance.DoLog("Running elobuddy system updater.");

            var updateWindow = new UpdateWindow();
            updateWindow.BeginUpdate(
                new UpdateWindow.UpdateWindowDelegate[]
                {
                    LoaderUpdateRoutines.InitializeUpdateRoutine, LoaderUpdateRoutines.LoaderUpdateRoutine, LoaderUpdateRoutines.SystemFilesUpdateRoutine, LoaderUpdateRoutines.PatchFilesUpdateRoutine,
                    LoaderUpdateRoutines.InstallFilesRoutine
                }, null);

            object json;
            updateWindow.Args.TryGetValue("updateDataJson", out json);
            LatestUpdateJson = (json ?? string.Empty).ToString();

            updateWindow.Args.TryGetValue("coreJson", out json);
            LatestCoreJson = (json ?? string.Empty).ToString();

            Log.Instance.DoLog("Elobuddy system updater has finished updating.");
            IsRunning = false;

            Events.RaiseOnSystemUpdateFinished(EventArgs.Empty);
        }

        public static void UpdateInstalledAddons()
        {
            Task.Run(() => { AddonUpdateRoutine.UpdateAddons(Settings.Instance.InstalledAddons.ToArray()); });
        }
    }
}
