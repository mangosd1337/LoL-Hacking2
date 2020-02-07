using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.Globalization;
using System.Threading;
using EloBuddy.Sandbox;
using EloBuddy.SDK.Enumerations;
using EloBuddy.SDK.Events;
using EloBuddy.SDK.Menu;
using EloBuddy.SDK.Spells;
using EloBuddy.SDK.Utils;

namespace EloBuddy.SDK
{
    public static class Bootstrap
    {
        public static bool IsSpectatorMode { get; internal set; }
        public static bool IsStreamingMode { get; internal set; }

        internal static readonly Process CurrentProcess = Process.GetCurrentProcess();

        internal static bool _initialized;

        internal static readonly List<Type> SkipInitialization = new List<Type>();

        public static void Init(string[] args)
        {
            if (_initialized)
            {
                return;
            }
            _initialized = true;

            // Set thread culture
            CultureInfo.DefaultThreadCurrentCulture = CultureInfo.InvariantCulture;
            CultureInfo.DefaultThreadCurrentUICulture = CultureInfo.InvariantCulture;
            Thread.CurrentThread.CurrentCulture = CultureInfo.InvariantCulture;
            Thread.CurrentThread.CurrentUICulture = CultureInfo.InvariantCulture;

            // Check if we are on streaming mode
            if (SandboxConfig.StreamingMode)
            {
                IsStreamingMode = true;
                StreamingMode.Initialize();
            }

            // Run Bootstrap Init
            BootstrapRun.Initialize();
        }
    }

    internal static class BootstrapRun
    {
        internal static readonly Dictionary<Action, string> AlwaysLoadAction = new Dictionary<Action, string>
        {
            { MainMenu.Initialize, null },
            { EntityManager.Initialize, null },
            { Item.Initialize, null },
            { SpellDatabase.Initialize, "SpellDatabase loaded." }
        };

        internal static readonly Dictionary<Action, string> ToLoadActions = new Dictionary<Action, string>
        {
#if !DEBUG
            { Core.Initialize, null },
            { Auth.Initialize, null },
            { TargetSelector.Initialize, "TargetSelector loaded." },
            { Orbwalker.Initialize, "Orbwalker loaded." },
            { Prediction.Initialize, "Prediction loaded." },
            { DamageLibrary.Initialize, "DamageLibrary loaded." },
            { SummonerSpells.Initialize, "SummonerSpells loaded." },
#endif
        };

        internal static void Initialize()
        {
            // Initialize loading event
            Loading.Initialize();

            Loading.AsyncLockedActions.Add(delegate
            {
                // Spectator mode check
                try
                {
                    if (Player.Instance != null)
                    {
                        Logger.Info("----------------------------------");
                        Logger.Info("Loading SDK Bootstrap");
                        Logger.Info("----------------------------------");

                        // Unlock Loading event
                        Loading.Locked = false;
                        return;
                    }
                }
                catch (Exception)
                {
                    // ignored
                }

                // Define as spectator mode
                Bootstrap.IsSpectatorMode = true;

                Logger.Info("-----------------------------------");
                Logger.Info("Spectating game, have fun watching!");
                Logger.Info("-----------------------------------");

                // Unlock Loading event
                Loading.Locked = false;
            });

            // Load the menu
            Loading.AlwaysLoadActions.Add(delegate
            {
                // Default ticks per second
                Game.TicksPerSecond = 25;

                // Invoke all actions
                foreach (var entry in AlwaysLoadAction)
                {
                    TryLoad(entry.Key, entry.Value);
                }
                AlwaysLoadAction.Clear();
            });

            // Add the SDK loading to a special locked action list
            Loading.OnLoadingComplete += delegate
            {
                // Invoke all actions
                foreach (var entry in ToLoadActions)
                {
                    TryLoad(entry.Key, entry.Value);
                }
                ToLoadActions.Clear();
                if (SandboxConfig.IsBuddy)
                {
                    Chat.Print("<font color=\"#0080ff\" >>> Welcome back, Buddy</font>");
                }

                Logger.Info("----------------------------------");
                Logger.Info("SDK Bootstrap fully loaded!");
                Logger.Info("----------------------------------");
            };
        }

        internal static void TryLoad(Action action, string message)
        {
            try
            {
                if (Bootstrap.SkipInitialization.Contains(action.GetType()))
                {
                    Logger.Debug("Skipping initialization for " + action.GetType().Name);
                    return;
                }
                action();

                if (!string.IsNullOrWhiteSpace(message))
                {
                    Logger.Log(LogLevel.Info, message);
                }
            }
            catch (Exception e)
            {
                Logger.Log(LogLevel.Error, "SDK Bootstrap error:\n{0}", e);
            }
        }
    }
}
