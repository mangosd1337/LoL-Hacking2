using System;
using System.Diagnostics;
using System.IO;
using System.Linq;
using EloBuddy.Loader.Data;
using NLog;

namespace EloBuddy.Loader.Utils
{
    public static class Riot
    {
        private static readonly NLog.Logger NLog = LogManager.GetCurrentClassLogger();

        private static string LocateLatestLoLExePath(string releasesFolderPath)
        {
            try
            {
                var folders = Directory.GetDirectories(releasesFolderPath);
                var latestFolder = (string)
                    folders.Select(p => new object[] { int.Parse(new DirectoryInfo(p).Name.Replace(".", "")), p })
                        .OrderByDescending(a => (int) a[0])
                        .First()[1];
                var path = Path.Combine(releasesFolderPath, latestFolder);
                var file = Path.Combine(path, @"deploy\League of Legends.exe");
                return File.Exists(file) ? file : string.Empty;
            }
            catch (Exception)
            {
            }

            return string.Empty;
        }

        public static string GetLatestLolExePath()
        {
            var processes = Process.GetProcessesByName("LoLClient");
            var newPath = string.Empty;
            var isGarena = false;

            if (processes.Length > 0)
            {
                var path = processes.First().MainModule.FileName;
                newPath = path.Split(new[] { @"\RADS\" }, StringSplitOptions.RemoveEmptyEntries)[0] +
                          @"\RADS\solutions\lol_game_client_sln\releases";

                if (!Directory.Exists(newPath)) // Garena
                {
                    newPath = path.Split(new[] { @"\Air\" }, StringSplitOptions.RemoveEmptyEntries)[0] + @"\Game";
                    isGarena = true;
                }

                NLog.Info(isGarena ? "LoLClient Garena" : "LoLClient Riot Games");

                Settings.Instance.LastLoLReleasesFolderPath = newPath;

                return isGarena
                    ? Path.Combine(newPath, "League of Legends.exe")
                    : LocateLatestLoLExePath(newPath);
            }

            var savedPath = Settings.Instance.LastLoLReleasesFolderPath;
            if (!string.IsNullOrEmpty(savedPath))
            {
                var file = LocateLatestLoLExePath(Settings.Instance.LastLoLReleasesFolderPath);

                if (File.Exists(file))
                {
                    return file;
                }

                file = Path.Combine(savedPath, "League of Legends.exe");
                if (File.Exists(file))
                {
                    return file;
                }
            }

            var defaultDirectory = @"C:\Riot Games\League of Legends\RADS\solutions\lol_game_client_sln\releases";
            if (Directory.Exists(defaultDirectory))
            {
                return LocateLatestLoLExePath(defaultDirectory);
            }

            return string.Empty;
        }

        public static string GetCurrentPatchHash()
        {
            return Md5Hash.ComputeFromFile(GetLatestLolExePath());
        }

        public static FileVersionInfo GetCurrentPatchVersionInfo()
        {
            var file = GetLatestLolExePath();
            return File.Exists(file) ? FileVersionInfo.GetVersionInfo(file) : null;
        }
    }
}
