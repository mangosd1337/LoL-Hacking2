using System;
using System.ServiceModel;
using System.Windows;
using EloBuddy.Loader.Logger;
using EloBuddy.Loader.Services;
using EloBuddy.Sandbox.Shared;

namespace EloBuddy.Loader.Routines
{
    public static class ApiServiceRoutine
    {
        private static ServiceHost _loaderServiceHost;

        public static void StartService()
        {
            _loaderServiceHost = ServiceFactory.CreateService<IElobuddyApiService, ElobuddyApiService>(true, "ElobuddyApiService");
            _loaderServiceHost.Faulted += OnLoaderServiceFaulted;
        }

        private static void OnLoaderServiceFaulted(object sender, EventArgs eventArgs)
        {
            Log.Instance.DoLog("IElobuddyApiService faulted, trying restart", Log.LogType.Error);

            _loaderServiceHost.Faulted -= OnLoaderServiceFaulted;
            _loaderServiceHost.Abort();

            try
            {
                StartService();
            }
            catch (Exception ex)
            {
                Log.Instance.DoLog(string.Format("IElobuddyApiService failed to start, exception: {0}", ex), Log.LogType.Error);
                MessageBox.Show("IElobuddyApiService failed to start. Please restart the loader!", "Fatal Error", MessageBoxButton.OK, MessageBoxImage.Error);
            }
        }
    }
}
