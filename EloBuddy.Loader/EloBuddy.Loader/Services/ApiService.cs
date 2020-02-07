using System;
using System.Diagnostics;
using System.ServiceModel;
using EloBuddy.Loader.Globals;
using EloBuddy.Loader.Installers;
using EloBuddy.Loader.Logger;
using EloBuddy.Loader.Routines;
using EloBuddy.Loader.UriScheme;
using EloBuddy.Loader.Utils;
using EloBuddy.Loader.Views;

namespace EloBuddy.Loader.Services
{
    public static class ApiService
    {
        public static void Init()
        {
            SetupUriScheme();
            ApiServiceRoutine.StartService();
        }

        public static void SetupUriScheme()
        {
            try
            {
                UriSchemeInstaller.InstallUriScheme(Constants.UriSchemePrefix, EnvironmentHelper.FileName);
            }
            catch (Exception ex)
            {
                Log.Instance.DoLog(String.Format("Failed to setup elobuddy uri scheme! Exception: {0}", ex), Log.LogType.Error);
            }
        }

        public static void StartUp(string[] args)
        {
            if (args == null || args.Length == 0)
            {
                return;
            }

            if (args.Length > 1)
            {
                var urischeme = Constants.UriSchemePrefix + "://";

                if (args[1].StartsWith(urischeme))
                {
                    UriHandler.Process(args[1]);
                }
            }
        }
    }

    [ServiceContract]
    public interface IElobuddyApiService
    {
        [OperationContract]
        void StartUp(string[] args);

        [OperationContract]
        void ShowMainWindow();
    }

    public class ElobuddyApiService : IElobuddyApiService
    {
        public void StartUp(string[] args)
        {
            ApiService.StartUp(args);
        }

        public void ShowMainWindow()
        {
            var handle = Process.GetCurrentProcess().MainWindowHandle;
            NativeImports.ShowWindow(handle, 5);
            NativeImports.SetForegroundWindow(handle);
        }
    }
}
