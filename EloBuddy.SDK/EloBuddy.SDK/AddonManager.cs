using System;
using System.Collections.Generic;
using System.Linq;
using EloBuddy.SDK.Events;
using EloBuddy.SDK.Utils;

namespace EloBuddy.SDK
{
    public static class AddonManager
    {
        public delegate void AllAddonsLoadedHandler(object sender, EventArgs args);

        public static event AllAddonsLoadedHandler OnAllAddonsLoaded;
        internal static readonly List<Delegate> NotifiedListeners = new List<Delegate>();

        internal static IEnumerable<string> LoadedAddons
        {
            get { return Sandbox.SandboxDomain.LoadedLibraries.Values.Where(o => o != null).Select(o => o.GetName().Name).Concat(Sandbox.SandboxDomain.LoadedAddons); }
        }

        static AddonManager()
        {
            Game.OnTick += OnTick;
        }

        internal static void OnTick(EventArgs args)
        {
            if (!Sandbox.Sandbox.AllAddonsLoaded && !Loading._allAddonsLoaded)
            {
                return;
            }

            if (OnAllAddonsLoaded != null)
            {
                foreach (var handler in OnAllAddonsLoaded.GetInvocationList().Where(o => !NotifiedListeners.Contains(o)).ToArray())
                {
                    // Add handler to notified list
                    NotifiedListeners.Add(handler);

                    // Notify the handler
                    try
                    {
                        handler.DynamicInvoke(null, EventArgs.Empty);
                    }
                    catch (Exception e)
                    {
                        Logger.Warn("Failed to notify OnAllAddonsLoaded listener!\n{0}", e);
                    }
                }
            }
        }

        public static bool IsAddonLoaded(string name)
        {
            name = name.ToLower();
            return LoadedAddons.Any(addon => addon.ToLower().Equals(name));
        }
    }
}
