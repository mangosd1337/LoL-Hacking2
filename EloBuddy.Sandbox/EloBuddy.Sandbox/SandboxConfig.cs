using System;
using System.Security;
using System.Security.Permissions;
using EloBuddy.Sandbox.Shared;

namespace EloBuddy.Sandbox
{
    /// <summary>
    ///     Sandbox Configuration Placeholder.
    /// </summary>
    public class SandboxConfig
    {
        /// <summary>
        ///     Data Directory, normally the full path towards the installation.
        /// </summary>
        public static string DataDirectory;

        /// <summary>
        ///     Libraries Directory, full path towards the libraries directory.
        /// </summary>
        public static string LibrariesDirectory;

        /// <summary>
        ///     A full path towards the EloBuddy.dll
        /// </summary>
        public static string EloBuddyDllPath;

        /// <summary>
        ///     Permissions for the secure application domain.
        /// </summary>
        public static PermissionSet Permissions;

        /// <summary>
        ///     Placeholder for the Anti-AFK feature.
        /// </summary>
        public static bool AntiAfk;

        /// <summary>
        ///     Placeholder for the MovementHack feature.
        /// </summary>
        public static bool MovementHack;

        /// <summary>
        ///     Placeholder for the DrawWaterMark feature.
        /// </summary>
        public static bool DrawWaterMark;

        /// <summary>
        ///     Placeholder for the DisableRangeIndicators feature.
        /// </summary>
        public static bool DisableRangeIndicator;

        /// <summary>
        ///     Placeholder for the Developer Console feature.
        /// </summary>
        public static bool Console;

        /// <summary>
        ///     Placeholder for the Extended Zoom (or Zoomhack) feature.
        /// </summary>
        public static bool ExtendedZoom;

        /// <summary>
        ///     Placeholder for the Disable Chat feature.
        /// </summary>
        public static bool DisableChatFunction;

        /// <summary>
        ///     Placeholder for the Tower Range feature.
        /// </summary>
        public static bool TowerRange;

        /// <summary>
        ///     Data Placeholder for the Menu Key.
        /// </summary>
        public static int MenuKey = 0x10;

        /// <summary>
        ///     Data placeholder for the Menu Toggle Key.
        /// </summary>
        public static int MenuToggleKey = 0x78;

        /// <summary>
        ///     Data placeholder for the Reload Assemblies Key.
        /// </summary>
        public static int ReloadKey = 0x74;

        /// <summary>
        ///     Data placeholder for the Unload Key.
        /// </summary>
        public static int UnloadKey = 0x75;

        /// <summary>
        ///     Data placeholder for the Reload and Recompile Assemblies Key.
        /// </summary>
        public static int ReloadAndRecompileKey = 0x77;

        /// <summary>
        ///     Placeholder for the Streaming Mode feature.
        /// </summary>
        public static bool StreamingMode;

        /// <summary>
        ///     Buddy.
        /// </summary>
        public static bool IsBuddy;

        /// <summary>
        /// Username
        /// </summary>
        public static string Username { get; set; }
        
        /// <summary>
        /// Password, , todo: increase protection/access (SDK only)
        /// </summary>
        public static string PasswordHash { get; set; }
        /// <summary>
        /// Hwid
        /// </summary>
        public static string Hwid { get; set; }

        static SandboxConfig()
        {
            Reload();
        }

        [PermissionSet(SecurityAction.Assert, Unrestricted = true)]
        public static void Reload()
        {
            Configuration config = null;

            try
            {
                config = ServiceFactory.CreateProxy<ILoaderService>().GetConfiguration(Sandbox.Pid);
            }
            catch (Exception e)
            {
                Logs.Log("Sandbox: Reload, getting configuration failed");
                Logs.Log(e.ToString());
            }

            if (config != null)
            {
                DataDirectory = config.DataDirectory;
                EloBuddyDllPath = config.EloBuddyDllPath;
                LibrariesDirectory = config.LibrariesDirectory;
                Permissions = config.Permissions;
                MenuKey = config.MenuKey;
                MenuToggleKey = config.MenuToggleKey;
                ReloadKey = config.ReloadKey;
                ReloadAndRecompileKey = config.ReloadAndRecompileKey;
                UnloadKey = config.UnloadKey;
                AntiAfk = config.AntiAfk;
                DisableRangeIndicator = config.DisableRangeIndicator;
                Console = config.Console;
                TowerRange = config.TowerRange;
                ExtendedZoom = config.ExtendedZoom;
                MovementHack = config.MovementHack;
                DrawWaterMark = config.DrawWatermark;
                DisableChatFunction = config.DisableChatFunction;
                StreamingMode = config.StreamingMode;
                IsBuddy = config.IsBuddy;

                Username = config.Username;
                PasswordHash = config.PasswordHash;
                Hwid = config.Hwid;
            }
            else
            {
                Logs.Log("Sandbox: Reload, config is null");
            }
        }
    }
}
