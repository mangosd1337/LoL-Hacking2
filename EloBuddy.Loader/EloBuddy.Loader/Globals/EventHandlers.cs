using System;
using System.Diagnostics;
using System.IO;
using System.Linq;
using System.Reflection;
using System.Windows;
using Elobuddy.Loader.Views;
using EloBuddy.Loader.Compilers;
using EloBuddy.Loader.Data;
using EloBuddy.Loader.Injection;
using EloBuddy.Loader.Logger;
using EloBuddy.Loader.Routines;
using EloBuddy.Loader.Services;
using EloBuddy.Loader.Utils;
using EloBuddy.Loader.Views;

namespace EloBuddy.Loader.Globals
{
    public static class EventHandlers
    {
        static EventHandlers()
        {
            AppDomain.CurrentDomain.UnhandledException += OnUnhandledException;
            AppDomain.CurrentDomain.AssemblyResolve += OnAssemblyResolve;

            Events.OnStartUp += OnStartUp;
            Events.OnExit += OnExit;
            Events.OnSuccessLogin += OnSuccessLogin;
            Events.OnMainWindowLoaded += OnMainWindowLoaded;
            Events.OnMainWindowInitialized += OnMainWindowInitialized;
            Events.OnLoginWindowInitialized += OnLoginWindowInitialized;
            Events.OnSystemUpdateFinished += OnSystemUpdateFinished;
            Events.OnAddonUpdatePrepare += OnAddonUpdatePrepare;
            Events.OnAddonUpdateFinished += OnAddonUpdateFinished;
            Events.OnInject += OnInject;
        }

        public static void Initialize()
        {
        }

        private static void OnUnhandledException(object sender, UnhandledExceptionEventArgs args)
        {
            var exception = (Exception) args.ExceptionObject;

            if (args.IsTerminating)
            {
                Settings.Save();
            }

            Log.Instance.DoLog(string.Format("Unhandled Exception.\r\nException: {0}\r\n", exception), Log.LogType.Error);
            MessageBox.Show(string.Format("Unhandled Exception!\n{0}\n\n", exception),
                "EloBuddy.Loader Unhandled Exception",
                MessageBoxButton.OK, MessageBoxImage.Error);
        }

        private static Assembly OnAssemblyResolve(object sender, ResolveEventArgs args)
        {
            foreach (var file in Directory.GetFiles(Settings.Instance.Directories.DependenciesDirectory))
            {
                if (Path.GetFileNameWithoutExtension(file) == (new AssemblyName(args.Name).Name))
                {
                    return Assembly.LoadFrom(file);
                }
            }

            return null;
        }

        private static void OnStartUp(StartupEventArgs startupEventArgs)
        {
            Log.Instance.DoLog(string.Format("Initializing Elobuddy.Loader [{0}]",
                Assembly.GetExecutingAssembly().GetName().Version));

            // Load Settings
            Settings.Load();

            // Cleanup logs folder
            foreach (var file in Directory.GetFiles(Settings.Instance.Directories.LogsDirectory, "*.txt", SearchOption.AllDirectories))
            {
                if (Path.GetFileName(file) != Path.GetFileName(Log.Instance.LogFilePath))
                {
                    try
                    {
                        File.Delete(file);
                    }
                    catch
                    {
                        // ignored
                    }
                }
            }

            // Cleanup temp folder
            try
            {
                DirectoryHelper.DeleteDirectory(Settings.Instance.Directories.TempDirectory, false);
            }
            catch (Exception)
            {
                // ignored
            }

            // Delete invalid files from the Assemblies directory
            foreach (var file in Directory.GetFiles(Settings.Instance.Directories.AssembliesDirectory, "*", SearchOption.AllDirectories))
            {
                if (!Settings.Instance.InstalledAddons.Any(addon => addon.IsValid() && File.Exists(addon.GetOutputFilePath())))
                {
                    try
                    {
                        File.Delete(file);
                    }
                    catch
                    {
                        // ignored
                    }
                }
            }

            // Delete unused repositories
            var directories = Directory.GetDirectories(Settings.Instance.Directories.RepositoryDirectory);
            foreach (var dir in directories)
            {
                if (
                    !Settings.Instance.InstalledAddons.Any(
                        addon =>
                            !addon.IsLocal &&
                            string.Equals(addon.GetRemoteAddonRepositoryDirectory(), dir,
                                StringComparison.CurrentCultureIgnoreCase)))
                {
                    try
                    {
                        DirectoryHelper.DeleteDirectory(dir);
                    }
                    catch (Exception)
                    {
                        // ignored
                    }
                }
            }

            // Load language
            MultiLanguage.Text = LocalizedText.Load(Settings.Instance.SelectedLanguage ?? Language.Default);

            Log.Instance.DoLog("Elobuddy.Loader initialized successfully.");
        }

        private static void OnExit(ExitEventArgs exitEventArgs)
        {
            Log.Instance.DoLog("Elobuddy.Loader is exiting.");

            // Cleanup
            try
            {
                DirectoryHelper.DeleteDirectory(Settings.Instance.Directories.TempCoreDirectory);
                Log.Instance.DoLog(string.Format("Deleted temporary core directory: \"{0}\"",
                    Settings.Instance.Directories.TempCoreDirectory));
            }
            catch (Exception ex)
            {
                Log.Instance.DoLog(
                    string.Format("Failed to delete temporary core directory: \"{0}\", exception: {1}",
                        Settings.Instance.Directories.TempCoreDirectory, ex), Log.LogType.Error);
            }

            // Save settings
            Settings.Save();

            Log.Instance.DoLog("Elobuddy.Loader has exited.\r\n");
        }

        private static void OnSuccessLogin(EventArgs args)
        {
            // Update UI
            if (!Settings.Instance.Ui.FirstTimeWizardRan)
            {
                new FirstTimeWizardWindow().ShowDialog();
                Settings.Instance.Ui.FirstTimeWizardRan = true;
            }
        }

        private static void OnSystemUpdateFinished(EventArgs args)
        {
            if (Windows.MainWindow != null)
            {
                Windows.MainWindow.RefreshEloBuddyStatus();
            }
        }

        private static void OnAddonUpdatePrepare(EventArgs args)
        {
            var window = Windows.MainWindow;
            if (window != null)
            {
                window.Dispatcher.Invoke(() =>
                {
                    window.InstalledAddonsGrid.ItemsContextMenu.Visibility = Visibility.Hidden;
                    window.InstalledAddonsGrid.InstallAssemblyButton.IsEnabled = false;
                    window.InstalledAddonsGrid.DeleteAddonsButton.IsEnabled = false;
                    window.InstalledAddonsGrid.UpdateAssembliesButton.IsEnabled = false;
                });
            }
        }

        private static void OnAddonUpdateFinished(EventArgs args)
        {
            var window = Windows.MainWindow;
            if (window != null)
            {
                window.Dispatcher.Invoke(() =>
                {
                    window.InstalledAddonsGrid.ItemsContextMenu.Visibility = Visibility.Visible;
                    window.InstalledAddonsGrid.InstallAssemblyButton.IsEnabled = true;
                    window.InstalledAddonsGrid.DeleteAddonsButton.IsEnabled = true;
                    window.InstalledAddonsGrid.UpdateAssembliesButton.IsEnabled = true;
                });
            }
        }

        private static void OnMainWindowLoaded(MainWindow window, RoutedEventArgs args)
        {
            ApiService.Init();
            LoaderServiceRoutine.StartService();
            InjectionRoutine.StartRoutine();
        }

        private static void OnMainWindowInitialized(MainWindow window, EventArgs args)
        {
            // Set memory layout
            Bootstrap.SetMemoryLayout();

            // Start auto update routine
            AutoUpdateRoutine.Start();

            // Remove invalid addons
            Settings.Instance.InstalledAddons.RemoveInvalidAddons();
        }

        private static void OnLoginWindowInitialized(LoginWindow window, EventArgs args)
        {
        }

        private static void OnInject(int pId, bool success)
        {
            if (!success)
            {
                MessageBox.Show(string.Format("Injection failed! ProcessID: {0}", pId), "Injection", MessageBoxButton.OK,
                    MessageBoxImage.Exclamation);
            }
        }
    }
}
