using System;
using System.Net;
using EloBuddy.Auth.Networking;
using EloBuddy.Auth.Utils;

namespace EloBuddy.Auth
{
    class Program
    {
        static void Main(string[] args)
        {
            ConsoleLogger.Write("Launching...");
            ConsoleLogger.Write("EloBuddy.Auth");

            ServicePointManager.DefaultConnectionLimit = int.MaxValue;
            ServicePointManager.MaxServicePoints = int.MaxValue;
            ServicePointManager.ReusePort = true;
            AuthService.Listen();

            ConsoleKeyInfo cki;
            do
            {
                cki = Console.ReadKey(true);

                switch (cki.Key)
                {
                    case ConsoleKey.C:
                        ConsoleLogger.Write(string.Format("Connected clients: {0}", ConsoleColor.DarkMagenta));
                        break;
                }

            } while (cki.Key != ConsoleKey.Escape);
        }
    }
}
