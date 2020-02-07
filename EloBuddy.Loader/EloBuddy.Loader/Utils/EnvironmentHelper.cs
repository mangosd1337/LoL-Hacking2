using System;
using System.Diagnostics;
using System.Globalization;
using System.IO;
using System.Reflection;
using System.Threading;
using EloBuddy.Loader.Data;
using EloBuddy.Loader.Globals;
using Microsoft.Win32;

namespace EloBuddy.Loader.Utils
{
    public static class EnvironmentHelper
    {
        public static string FileName
        {
            get { return Process.GetCurrentProcess().MainModule.FileName; }
        }

        public static void Restart(bool triggerEvent = false)
        {
            if (triggerEvent)
            {
                Events.RaiseOnExit(null);
            }

            Process.Start(FileName);
            Environment.Exit(0);
        }

        public static void ShutDown(bool triggerEvent = false)
        {
            if (triggerEvent)
            {
                Events.RaiseOnExit(null);
            }

            Environment.Exit(0);
        }

        public static void SetValidEnvironment()
        {
            Environment.CurrentDirectory = Path.GetDirectoryName(FileName);
            CultureInfo.DefaultThreadCurrentCulture = CultureInfo.InvariantCulture;
            CultureInfo.DefaultThreadCurrentUICulture = CultureInfo.InvariantCulture;
            Thread.CurrentThread.CurrentCulture = CultureInfo.InvariantCulture;
            Thread.CurrentThread.CurrentUICulture = CultureInfo.InvariantCulture;
        }

        public static Version GetAssemblyVersion()
        {
            return Assembly.GetExecutingAssembly().GetName().Version;
        }

        public static bool IsWow64(Process p)
        {
            //todo: doesnt seem to work properly
            bool isWow64;
            var handle = NativeImports.OpenProcess(ProcessAccessFlags.All, false, p.Id);
            NativeImports.IsWow64Process(handle, out isWow64);
            NativeImports.CloseHandle(handle);
            return isWow64;
        }

        public static Language GetDefaultLanguage()
        {
            var ci = CultureInfo.InstalledUICulture;
            var languge = ci.Name.Split('-')[0];

            switch (languge)
            {
                case "ar":
                    return Language.Arabic;
                case "de":
                    return Language.German;
                case "es":
                    return Language.Spanish;
                case "fr":
                    return Language.French;
                case "it":
                    return Language.Italian;
                case "pl":
                    return Language.Polish;
                case "hu":
                    return Language.Hungarian;
                case "nl":
                    return Language.Dutch;
                case "sv":
                    return Language.Swedish;
                case "pt":
                    return Language.Portuguese;
                case "sl":
                    return Language.Slovenian;
                case "ro":
                    return Language.Romanian;
                case "vi":
                    return Language.Vietnamese;
                case "tr":
                    return Language.Turkish;
                case "zh":
                    return Language.Chinese;
                case "ko":
                    return Language.Korean;
                case "bs":
                    return Language.Balkan;
                case "gr":
                    return Language.Greek;
            }

            return Language.English;
        }

        public static string GetMachineGuid()
        {
            const string location = @"SOFTWARE\Microsoft\Cryptography";
            const string name = "MachineGuid";

            using (var localMachineX64View = RegistryKey.OpenBaseKey(RegistryHive.LocalMachine, RegistryView.Registry64))
            {
                using (var rk = localMachineX64View.OpenSubKey(location))
                {
                    if (rk == null)
                    {
                        return "failed";
                    }

                    var machineGuid = rk.GetValue(name);

                    if (machineGuid == null)
                    {
                        return "failed2";
                    }

                    return machineGuid.ToString();
                }
            }
        }
    }
}
