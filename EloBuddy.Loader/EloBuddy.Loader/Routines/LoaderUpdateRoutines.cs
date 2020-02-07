using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.IO;
using System.Linq;
using System.Net;
using System.Threading;
using System.Windows;
using EloBuddy.Loader.Data;
using EloBuddy.Loader.Globals;
using EloBuddy.Loader.Logger;
using EloBuddy.Loader.Models;
using EloBuddy.Loader.Update;
using EloBuddy.Loader.Utils;
using EloBuddy.Loader.Views;
using Newtonsoft.Json;

namespace EloBuddy.Loader.Routines
{
    internal static class LoaderUpdateRoutines
    {
        private static WebClient _webClient;

        private static void WaitForClientToFinish()
        {
            while (_webClient.IsBusy)
            {
                Thread.Sleep(10);
            }

            Thread.Sleep(50);
        }

        private static string DownloadString(string url)
        {
            var result = string.Empty;
            url += "?_=" + RandomHelper.RandomString(10);

            DownloadStringCompletedEventHandler handler = (sender, args) =>
            {
                try
                {
                    result = args.Result;
                }
                catch (Exception)
                {
                }
            };

            _webClient.DownloadStringCompleted += handler;
            _webClient.DownloadStringAsync(new Uri(url));
            WaitForClientToFinish();
            _webClient.DownloadStringCompleted -= handler;

            return result;
        }

        private static bool DownloadFile(string url, string path, int maxAttempts = 5)
        {
            url += "?_=" + RandomHelper.RandomString(10);

            var downloadPath = path + ".temp";
            _webClient.DownloadFileAsync(new Uri(url), downloadPath);
            WaitForClientToFinish();

            if (!File.Exists(downloadPath) || File.ReadAllBytes(downloadPath).Length == 0)
            {
                if (maxAttempts <= 0)
                {
                    if (File.Exists(downloadPath))
                    {
                        File.Delete(downloadPath);
                    }

                    return false;
                }

                return DownloadFile(url, path, maxAttempts - 1);
            }

            if (File.Exists(path))
            {
                File.Move(path, string.Format("{0}_{1}.old", path, Environment.TickCount));
            }

            File.Move(downloadPath, path);

            return true;
        }

        private static void ExitDownloadError(string file)
        {
            MessageBox.Show(string.Format(
                MultiLanguage.Text.ErrorUpdateFailedToDownloadFile, 
                Path.GetFileName(file)), MultiLanguage.Text.TitleMsgBoxUpdateFailedToDownloadFile, 
                MessageBoxButton.OK, MessageBoxImage.Error);

            EnvironmentHelper.ShutDown(true);
        }

        internal static void InitializeUpdateRoutine(UpdateWindow ui, Dictionary<string, object> args)
        {
            if (_webClient != null)
            {
                _webClient.Dispose();
            }

            _webClient = new WebClient();
            _webClient.DownloadProgressChanged += delegate(object sender, DownloadProgressChangedEventArgs eventArgs)
            {
                ui.MaxProgress = 100;
                ui.CurrentProgress = eventArgs.ProgressPercentage;
            };

            // Cleanup after update
            if (File.Exists(Constants.LoaderTempFileName))
            {
                File.Delete(Constants.LoaderTempFileName);
            }

            ui.OveralMaxProgress = 100;
            ui.OveralCurrentProgress = 100;
            ui.MaxProgress = 0;
            ui.Status = MultiLanguage.Text.UpdateStatusLoader;
            ui.Details = MultiLanguage.Text.UpdateDetailsLoader;
            args["updateDataJson"] = DownloadString(Constants.DependenciesJsonUrl);
            args["coreJson"] = DownloadString(Constants.CoreJsonUrl);

            try
            {
                args["updateData"] = JsonConvert.DeserializeObject<UpdateData>((string) args["updateDataJson"]);
                args["coreData"] = JsonConvert.DeserializeObject<CoreNewsList>((string) args["coreJson"]);
            }
            catch (Exception ex)
            {
                args["updateData"] = null;
                args["coreData"] = null;

                Log.Instance.DoLog(string.Format("Unexpected error while deserializing update data during InitializeUpdateRoutine. Exception: {0} \r\n", ex), Log.LogType.Error);

                MessageBox.Show(string.Format(MultiLanguage.Text.ErrorUpdateFailedToDeserialize, ex), 
                    MultiLanguage.Text.TitleMsgBoxUpdateFailedToDeserialize, MessageBoxButton.OK, 
                    MessageBoxImage.Error);
            }

            if (args["updateData"] == null)
            {
                Log.Instance.DoLog("No update data could be retrieved.", Log.LogType.Error);
            }

            if (args["coreData"] == null)
            {
                Log.Instance.DoLog("No core update data could be retrieved.", Log.LogType.Error);
            }
        }

        internal static void LoaderUpdateRoutine(UpdateWindow ui, Dictionary<string, object> args)
        {
#if DEBUG
            return;
#endif
            var updateData = (UpdateData) args["updateData"];

            if (updateData != null)
            {
                ui.CurrentProgress = 0;
                ui.OveralMaxProgress = 100;
                var currentFilePath = Process.GetCurrentProcess().MainModule.FileName;

                if (!Md5Hash.Compare(Md5Hash.ComputeFromFile(currentFilePath), updateData.Loader.MD5, true))
                {
                    ui.Details = MultiLanguage.Text.UpdateDetailsLoaderDownloading;
                    if (!DownloadFile(updateData.Loader.Download, currentFilePath))
                    {
                        ExitDownloadError(currentFilePath);
                    }

                    args["restartRequired"] = true;
                }
            }
        }

        internal static void SystemFilesUpdateRoutine(UpdateWindow ui, Dictionary<string, object> args)
        {
            var updateData = (UpdateData) args["updateData"];

            if (updateData == null || DeveloperHelper.IsDeveloper)
            {
                return;
            }

            //-----------------------------
            // Update static files
            //-----------------------------
            var currentProgress = 0;
            ui.Status = MultiLanguage.Text.UpdateStatusSystemFiles;
            ui.Details = "";

            foreach (var keyPair in updateData.StaticFiles)
            {
                var path = keyPair.Key;
                var filename = Path.GetFileName(path);
                currentProgress += 1;

                ui.CurrentProgress = 0;
                ui.OveralCurrentProgress = currentProgress;
                ui.OveralMaxProgress = updateData.StaticFiles.Count + 1;
                ui.Details = string.Format(MultiLanguage.Text.UpdateDetailsCheckingFile, filename);

                if (!Md5Hash.Compare(Md5Hash.ComputeFromFile(path), keyPair.Value.MD5))
                {
                    if (!string.IsNullOrEmpty(keyPair.Value.Download))
                    {
                        ui.Details = string.Format(MultiLanguage.Text.UpdateDetailsDownloadingFile, filename);

                        if (!DownloadFile(keyPair.Value.Download, path))
                        {
                            ExitDownloadError(filename);
                        }
                    }
                    else if (File.Exists(path))
                    {
                        File.Delete(path);
                    }
                }
            }

            ui.OveralCurrentProgress = ui.OveralMaxProgress;
        }

        internal static void PatchFilesUpdateRoutine(UpdateWindow ui, Dictionary<string, object> args)
        {
            var updateData = (UpdateData) args["updateData"];

            if (updateData != null)
            {
                //-----------------------------
                // Update patch files
                //-----------------------------
                var currentPatchHash = Riot.GetCurrentPatchHash().ToLower();
                PatchData patch;
                updateData.Patches.TryGetValue(currentPatchHash, out patch);

                if (patch != null && !DeveloperHelper.IsDeveloper)
                {
                    var currentProgress = 0;
                    ui.Status = MultiLanguage.Text.UpdateStatusPatchFiles;
                    ui.Details = "";

                    foreach (var keyPair in patch.Files)
                    {
                        var path = keyPair.Key;
                        var filename = Path.GetFileName(path);
                        currentProgress += 1;

                        ui.CurrentProgress = 0;
                        ui.OveralCurrentProgress = currentProgress;
                        ui.OveralMaxProgress = patch.Files.Count + 1;
                        ui.Details = string.Format(MultiLanguage.Text.UpdateDetailsCheckingFile, filename);

                        if (!Md5Hash.Compare(Md5Hash.ComputeFromFile(path), keyPair.Value.MD5))
                        {
                            if (!string.IsNullOrEmpty(keyPair.Value.Download))
                            {
                                ui.Details = string.Format(MultiLanguage.Text.UpdateDetailsDownloadingFile, filename);

                                if (!DownloadFile(keyPair.Value.Download, path))
                                {
                                    ExitDownloadError(filename);
                                }
                            }
                            else if (File.Exists(path))
                            {
                                File.Delete(path);
                            }
                        }
                    }
                    ui.OveralCurrentProgress = ui.OveralMaxProgress;
                }

                // Set patch update result
                LoaderUpdate.LeagueHash = currentPatchHash;
                LoaderUpdate.LeagueVersion = string.IsNullOrEmpty(currentPatchHash)
                    ? string.Empty
                    : Riot.GetCurrentPatchVersionInfo().FileVersion;
                LoaderUpdate.UpToDate = DeveloperHelper.IsDeveloper || patch != null;

                if (patch != null && LoaderUpdate.UpToDate)
                {
                    LoaderUpdate.CoreHash = Md5Hash.ComputeFromFile(Settings.Instance.Directories.CoreDllPath);

                    if (args.ContainsKey("coreData"))
                    {
                        var jsonNews = (CoreNewsList)args["coreData"];
                        var coreItem = jsonNews.News.FirstOrDefault(n => n.Hash != null && n.Hash.Any(h => Md5Hash.Compare(h, LoaderUpdate.CoreHash)));
                        LoaderUpdate.CoreBuild = coreItem != null ? coreItem.Build : "Unknown";
                    }
                    else
                    {
                        LoaderUpdate.CoreBuild = "Unknown";
                    }
                }

                Log.Instance.DoLog(string.Format("League hash detected: \"{0}\"", currentPatchHash));
                Log.Instance.DoLog(string.Format("EloBuddy updated for current patch: {0}", patch != null));
                Log.Instance.DoLog(string.Format("Update status: \"{0}\"", LoaderUpdate.StatusString));
            }
        }

        internal static void InstallFilesRoutine(UpdateWindow ui, Dictionary<string, object> args)
        {
            ui.CurrentProgress = ui.MaxProgress;
            ui.OveralCurrentProgress = ui.OveralMaxProgress;
            ui.Status = MultiLanguage.Text.UpdateStatusInstallingFiles;
            ui.Details = MultiLanguage.Text.UpdateDetailsInstallingFiles;

            foreach (
                var file in
                    Directory.GetFiles(Settings.Instance.Directories.SystemDirectory, "*.dll",
                        SearchOption.AllDirectories)
                        .Where(
                            file =>
                                !Md5Hash.Compare(Md5Hash.ComputeFromFile(file),
                                    Md5Hash.ComputeFromFile(
                                        Path.Combine(Settings.Instance.Directories.LibrariesDirectory,
                                            Path.GetFileName(file))))))
            {
                try
                {
                    FileHelper.CopyFile(file, Settings.Instance.Directories.LibrariesDirectory);
                }
                catch (Exception)
                {
                    MessageBox.Show(
                        string.Format(MultiLanguage.Text.ErrorUpdateFailedToCopyFile, Path.GetFileName(file)),
                        MultiLanguage.Text.UpdateStatusInstallingFiles, MessageBoxButton.OK, MessageBoxImage.Information);
                }
            }

            foreach (var file in Directory.GetFiles(Environment.CurrentDirectory, "*.old", SearchOption.AllDirectories))
            {
                try
                {
                    File.Delete(file);
                }
                catch (Exception)
                {
                    // ignored
                }
            }

            if (args.ContainsKey("restartRequired") && (bool) args["restartRequired"])
            {
                EnvironmentHelper.Restart(true);
            }

            PathRandomizer.Randomize();
        }
    }
}
