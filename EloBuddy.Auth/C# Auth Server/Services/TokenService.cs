using System;
using System.Collections.Generic;
using System.IO;
using System.Timers;
using EloBuddy.Auth.Events;
using EloBuddy.Auth.Utils;

namespace EloBuddy.Auth.Services
{
    public static class TokenService
    {
        private const string TokendataFile = "Data\\token.dat";
        private static object _lock { get; set; }
        private static Timer _timer { get; set; }

        public static TokenData Tokens { get; private set; }

        static TokenService()
        {
            Initialize();
        }

        private static bool _init;

        public static void Initialize()
        {
            if (_init)
            {
                return;
            }

            _init = true;
            _lock = new object();
            _timer = new Timer(60000);
            _timer.Elapsed += delegate(object sender, ElapsedEventArgs args)
            {
                Save();
            };

            Tokens = File.Exists(TokendataFile) ? (TokenData) Serialization.Deserialize(File.ReadAllBytes(TokendataFile)) : new TokenData();
            ProcessExit.AddHandler(OnExit);
            _timer.Start();
        }

        public static void Save()
        {
            lock (_lock)
            {
                File.WriteAllBytes(TokendataFile, Serialization.Serialize(Tokens));
            }
        }

        public static TokenData.Token UpdateData(string username)
        {
            var token = new TokenData.Token(username);
            Tokens.Data[token.TokenHash] = token;
            return token;
        }

        public static string GetUsernameFromToken(byte[] token)
        {
            var tokenHash = MD5Hash.ComputeMD5Hash(token);

            if (Tokens.Data.ContainsKey(tokenHash))
            {
                return Tokens.Data[tokenHash].Username;
            }

            return null;
        }

        public static bool IsValidToken(byte[] token)
        {
            return GetUsernameFromToken(token) != null;
        }

        private static void OnExit(object sender, EventArgs args)
        {
            Save();
        }

        [Serializable]
        public class TokenData
        {
            [Serializable]
            public class Token
            {
                public readonly DateTime Time;
                public readonly string Username;
                public readonly byte[] BToken;
                public string TokenHash { get { return MD5Hash.ComputeMD5Hash(BToken); } }

                public Token(string username)
                {
                    Username = username;
                    Time = DateTime.Now;
                    BToken = new byte[40];

                    RandomHelper.Random.NextBytes(BToken);
                }
            }

            public Dictionary<string, Token> Data { get; set; }

            public TokenData()
            {
                Data = new Dictionary<string, Token>();
            }
        }
    }
}
