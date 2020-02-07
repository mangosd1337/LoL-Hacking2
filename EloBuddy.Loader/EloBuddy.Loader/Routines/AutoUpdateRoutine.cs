using System;
using System.Net;
using System.Threading;
using EloBuddy.Loader.Data;
using EloBuddy.Loader.Globals;
using EloBuddy.Loader.Update;
using EloBuddy.Loader.Utils;

namespace EloBuddy.Loader.Routines
{
    internal static class AutoUpdateRoutine
    {
        private static bool _execute;

        internal static bool Pause { get; set; }
        internal static Thread RoutineThread { get; private set; }
        internal static int Interval { get; set; }
        internal static WebClient WebClient { get; private set; }

        static AutoUpdateRoutine()
        {
            WebClient = new WebClient();
            Interval = 60000;
        }

        internal static bool IsRunning
        {
            get { return RoutineThread != null && RoutineThread.IsAlive; }
        }

        internal static void Start()
        {
            if (IsRunning)
            {
                return;
            }

            _execute = true;
            RoutineThread = new Thread(AutoUpdateThread) { IsBackground = true };
            RoutineThread.Start();
        }

        internal static void Stop(int timeout = 1500)
        {
            if (!IsRunning)
            {
                return;
            }

            _execute = false;
            Thread.Sleep(timeout);

            if (IsRunning)
            {
                RoutineThread.Abort();
            }
        }

        internal static void AutoUpdateThread(object args)
        {
            while (_execute)
            {
                if (!Settings.Instance.DisableAutomaticUpdates && !LoaderUpdate.IsRunning &&
                    !AddonUpdateRoutine.IsRunning && !InjectionRoutine.IsCurrentCoreFileInjected() && !Pause)
                {
                    CheckForUpdate();
                }

                Thread.Sleep(Interval);
            }
        }

        internal static bool CheckForUpdate()
        {
            var json = string.Empty;

            try
            {
                json = WebClient.DownloadString(Constants.DependenciesJsonUrl + "?_=" + RandomHelper.RandomString(10));
            }
            catch (Exception)
            {
                // ignored
            }

            if (!string.IsNullOrEmpty(json) &&
                !string.Equals(json, LoaderUpdate.LatestUpdateJson, StringComparison.CurrentCultureIgnoreCase))
            {
                if (Windows.MainWindow != null)
                {
                    Windows.MainWindow.Dispatcher.Invoke(LoaderUpdate.UpdateSystem);

                    return true;
                }
            }

            return false;
        }
    }
}