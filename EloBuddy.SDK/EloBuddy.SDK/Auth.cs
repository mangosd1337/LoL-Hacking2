using EloBuddy.SDK.Events;
using Newtonsoft.Json;
using System;
using System.Collections.Specialized;
using System.Net;
using System.Runtime.Serialization;
using System.Security.Permissions;

namespace EloBuddy.SDK
{
    internal static class Auth
    {
        struct MessageAuthInfo
        {
            [DataMember] public string Username;
            [DataMember] public string PasswordHash;
            [DataMember] public ulong GameId;
            [DataMember] public bool IsCustomGame;
            [DataMember] public string GameVersion;
            [DataMember] public string Region;
            [DataMember] public string SummonerName;
            [DataMember] public string Champion;
            [DataMember] public string HWID;
        }

        internal static void Initialize() { }

        static Auth()
        {
            Loading.OnLoadingComplete += Loading_OnLoadingComplete;
        }

        static void Loading_OnLoadingComplete(EventArgs args)
        {
            try
            {
                SendToServer<MessageAuthInfo>(new MessageAuthInfo
                {
                    Username = Sandbox.SandboxConfig.Username,
                    PasswordHash = Sandbox.SandboxConfig.PasswordHash,
                    GameId = Game.GameId,
                    IsCustomGame = Game.IsCustomGame,
                    GameVersion = Game.Version,
                    Region = Game.Region,
                    SummonerName = ObjectManager.Player.Name,
                    Champion = ObjectManager.Player.ChampionName,
                    HWID = Sandbox.SandboxConfig.Hwid
                });
            }
            catch (Exception)
            {
                // ignored
            }
        }

        [PermissionSet(SecurityAction.Assert, Unrestricted = true)]
        internal static void SendToServer<T>(T message) where T : struct
        {
            var serialized = JsonConvert.SerializeObject(message);

            using(var client = new WebClient())
            {
               client.Proxy = null;

                var content = new NameValueCollection();
                content[typeof(T).Name] = serialized;

                client.UploadValuesAsync(new Uri("https://edge.elobuddy.net/api.php?action=clientMessage"), content);
            }
        }
    }
}
