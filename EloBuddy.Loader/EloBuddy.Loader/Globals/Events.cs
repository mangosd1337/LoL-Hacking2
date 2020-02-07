using System;
using System.Windows;
using Elobuddy.Loader.Views;

namespace EloBuddy.Loader.Globals
{
    public static class Events
    {
        public delegate void OnStartUpDelegate(StartupEventArgs e);

        public static event OnStartUpDelegate OnStartUp;

        public delegate void OnExitDelegate(ExitEventArgs e);

        public static event OnExitDelegate OnExit;

        public delegate void OnSuccessLoginDelegate(EventArgs args);

        public static event OnSuccessLoginDelegate OnSuccessLogin;

        public delegate void OnMainWindowLoadedDelegate(MainWindow window, RoutedEventArgs args);

        public static event OnMainWindowLoadedDelegate OnMainWindowLoaded;

        public delegate void OnMainWindowInitializedDelegate(MainWindow window, EventArgs args);

        public static event OnMainWindowInitializedDelegate OnMainWindowInitialized;

        public delegate void OnLoginWindowInitializedDelegate(LoginWindow window, EventArgs args);

        public static event OnLoginWindowInitializedDelegate OnLoginWindowInitialized;

        public delegate void OnSystemUpdateFinishedDelegate(EventArgs args);

        public static event OnSystemUpdateFinishedDelegate OnSystemUpdateFinished;

        public delegate void OnAddonUpdatePrepareDelegate(EventArgs args);

        public static event OnAddonUpdatePrepareDelegate OnAddonUpdatePrepare;

        public delegate void OnAddonUpdateFinishedDelegate(EventArgs args);

        public static event OnAddonUpdateFinishedDelegate OnAddonUpdateFinished;

        public delegate void OnInjectDelegate(int pId, bool success);

        public static event OnInjectDelegate OnInject;

        static Events()
        {
            EventHandlers.Initialize();
        }

        public static void RaiseOnExit(ExitEventArgs e)
        {
            if (OnExit != null)
            {
                OnExit(e);
            }
        }

        public static void RaiseOnStartUp(StartupEventArgs e)
        {
            if (OnStartUp != null)
            {
                OnStartUp(e);
            }
        }

        public static void RaiseOnSuccessLogin(EventArgs args)
        {
            if (OnSuccessLogin != null)
            {
                OnSuccessLogin(args);
            }
        }

        public static void RaiseOnMainWindowLoaded(MainWindow window, RoutedEventArgs args)
        {
            if (OnMainWindowLoaded != null)
            {
                OnMainWindowLoaded(window, args);
            }
        }

        public static void RaiseOnMainWindowInitialized(MainWindow window, EventArgs args)
        {
            if (OnMainWindowInitialized != null)
            {
                OnMainWindowInitialized(window, args);
            }
        }

        public static void RaiseOnLoginWindowInitialized(LoginWindow window, EventArgs args)
        {
            if (OnLoginWindowInitialized != null)
            {
                OnLoginWindowInitialized(window, args);
            }
        }

        public static void RaiseOnSystemUpdateFinished(EventArgs args)
        {
            if (OnSystemUpdateFinished != null)
            {
                OnSystemUpdateFinished(args);
            }
        }

        public static void RaiseOnAddonUpdatePrepare(EventArgs args)
        {
            if (OnAddonUpdatePrepare != null)
            {
                OnAddonUpdatePrepare(args);
            }
        }

        public static void RaiseOnAddonUpdateFinished(EventArgs args)
        {
            if (OnAddonUpdateFinished != null)
            {
                OnAddonUpdateFinished(args);
            }
        }

        public static void RaiseOnInject(int pId, bool success)
        {
            if (OnInject != null)
            {
                OnInject(pId, success);
            }
        }
    }
}
