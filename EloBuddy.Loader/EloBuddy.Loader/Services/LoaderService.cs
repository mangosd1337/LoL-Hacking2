using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;
using EloBuddy.Loader.Data;
using EloBuddy.Loader.Logger;
using EloBuddy.Loader.Networking;
using EloBuddy.Networking;
using EloBuddy.Networking.Objects;
using EloBuddy.Sandbox.Shared;
using NLog;

namespace EloBuddy.Loader.Services
{
    public class LoaderService : ILoaderService
    {
        private static readonly NLog.Logger NLog = LogManager.GetCurrentClassLogger();
        private static readonly List<int> LoggedGames = new List<int>();

        private static void SendTelemetry(int gameid)
        {
            Task.Run(() =>
            {
                try
                {
                    var client = new EbClient();
                    var result = client.Do((byte) Headers.Reserved1,
                        new object[]
                        {
                            new TelemetryRequest
                            {
                                Token = Authenticator.Token,
                                GameId = gameid,
                                Assemblies = Settings.Instance.InstalledAddons.Where(addon => addon.Enabled).Select(a => new AddonData
                                {
                                    AddonState = (int) a.State,
                                    AddonType = (int) a.Type,
                                    Author = a.Author,
                                    IsBuddyAddon = a.IsBuddyAddon,
                                    IsLocal = a.IsLocal,
                                    Name = a.GetUniqueName(),
                                    Repository = a.Url
                                }).ToArray(),
                                Data = new object[] { }
                            }
                        });
                    client.Close();
                    client.InnerChannel.Dispose();
                }
                catch (Exception e)
                {
                    Log.Instance.DoLog(string.Format("Exception while trying to send telemetry data. Exception: {0}", e.Message), Log.LogType.Error);
                }
            });
        }

        public List<SharedAddon> GetAssemblyList(int gameid)
        {
            var logList = string.Join(";",
                Settings.Instance.InstalledAddons.Where(addon => addon.Enabled && addon.Type == AddonType.Executable)
                    .Select(a => a.ToString())
                    .Concat(new[] { string.Empty }));

            if (LoggedGames.Contains(gameid))
            {
                LoggedGames.Add(gameid);
                NLog.Info("[GetAssemblyList] [UniqueGame] User: {0}, HWID: {1}, GameID: {2}, List: {3}", Authenticator.Credentials.Username, Authenticator.Hwid, gameid,
                    logList);
            }

            NLog.Info("[GetAssemblyList] User: {0}, HWID: {1}, GameID: {2}, List: {3}", Authenticator.Credentials.Username, Authenticator.Hwid, gameid, logList);

            if (!Settings.Instance.DisableTelemetry && Authenticator.Token != null)
            {
                SendTelemetry(gameid);
            }

            return
                Settings.Instance.InstalledAddons.Where(addon => addon.Enabled && addon.Type == AddonType.Executable && addon.State == AddonState.Ready)
                    .Select(addon => new SharedAddon { PathToBinary = addon.GetOutputFilePath() })
                    .ToList();
        }

        public Configuration GetConfiguration(int pid)
        {
            return new Configuration
            {
                Username = Authenticator.Credentials.Username,
                PasswordHash = Authenticator.Credentials.Password,
                IsBuddy = Authenticator.IsBuddy,
                Hwid = Authenticator.Hwid,
                AntiAfk = Settings.Instance.Configuration.AntiAfk,
                Console = Settings.Instance.Configuration.Console,
                DataDirectory = Settings.Instance.Directories.AppDataDirectory,
                ExtendedZoom = Settings.Instance.Configuration.ExtendedZoom,
                EloBuddyDllPath = PathRandomizer.EloBuddyDllPath,
                LibrariesDirectory = Settings.Instance.Directories.LibrariesDirectory,
                MenuKey = Settings.Instance.Configuration.MenuKey,
                MenuToggleKey = Settings.Instance.Configuration.MenuToggleKey,
                Permissions = null, //possible security issue
                ReloadAndRecompileKey = Settings.Instance.Configuration.ReloadAndRecompileKey,
                ReloadKey = Settings.Instance.Configuration.ReloadKey,
                TowerRange = Settings.Instance.Configuration.TowerRange,
                UnloadKey = Settings.Instance.Configuration.UnloadKey,
                MovementHack = Settings.Instance.Configuration.MovementHack,
                StreamingMode = Settings.Instance.Configuration.StreamingMode,
                DrawWatermark = Settings.Instance.Configuration.DrawWaterMark,
                DisableChatFunction = Settings.Instance.Configuration.DisableChatFunction,
                DisableRangeIndicator = Settings.Instance.Configuration.DisableRangeIndicator
            };
        }

        public void Recompile(int pid)
        {
            Settings.Instance.InstalledAddons.RecompileSelectedAddons();
        }
    }
}
