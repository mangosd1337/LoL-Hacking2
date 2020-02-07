using System;
using System.IO;
using EloBuddy.Loader.Logger;
using EloBuddy.Loader.Utils;

namespace EloBuddy.Loader.Data
{
    internal static class PathRandomizer
    {
        internal static string SandboxDllPath
        {
            get { return Settings.Instance.Directories.TempSandboxDllPath; }
        }

        internal static string SandboxDllName
        {
            get { return Path.GetFileName(SandboxDllPath); }
        }

        internal static string EloBuddyDllPath
        {
            get { return Settings.Instance.Directories.TempEloBuddyDllPath; }
        }

        internal static string EloBuddyDllName
        {
            get { return Path.GetFileName(EloBuddyDllPath); }
        }

        internal static string CoreDllPath
        {
            get { return Settings.Instance.Directories.TempCoreDllPath; }
        }

        internal static string CoreDllName
        {
            get { return Path.GetFileName(CoreDllPath); }
        }

        internal static void Randomize(bool newDirectory = false)
        {
            if (newDirectory && Directory.Exists(Settings.Instance.Directories.TempCoreDirectory))
            {
                DirectoryHelper.DeleteDirectory(Settings.Instance.Directories.TempCoreDirectory);
            }

            // Set new directory
            if (!Directory.Exists(Settings.Instance.Directories.TempCoreDirectory))
            {
                // Create a random directory to store the core files
                Settings.Instance.Directories.TempCoreDirectory = Path.Combine(Path.GetTempPath(),
                    RandomHelper.RandomString());
                Log.Instance.DoLog(string.Format("Created temporary core directory: \"{0}\"",
                    Settings.Instance.Directories.TempCoreDirectory));
            }

            // Copy Elobuddy.Core.dll file
            try
            {
                if (!File.Exists(Settings.Instance.Directories.TempCoreDllPath))
                {
                    Settings.Instance.Directories.TempCoreDllPath =
                        Path.Combine(Settings.Instance.Directories.TempCoreDirectory, "Elobuddy.Core.dll");
                }

                if (
                    !Md5Hash.Compare(Md5Hash.ComputeFromFile(Settings.Instance.Directories.TempCoreDllPath),
                        Md5Hash.ComputeFromFile(Settings.Instance.Directories.CoreDllPath)))
                {
                    FileHelper.SafeCopyFile(Settings.Instance.Directories.CoreDllPath,
                        Settings.Instance.Directories.TempCoreDirectory,
                        Path.GetFileName(Settings.Instance.Directories.TempCoreDllPath));
                    Log.Instance.DoLog(string.Format("Copied EloBuddy.Core.dll to: \"{0}\"",
                        Settings.Instance.Directories.TempCoreDllPath));
                }
            }
            catch (Exception)
            {
                // ignored
            }

            // Copy EloBuddy.Sandbox.dll file
            try
            {
                if (!File.Exists(Settings.Instance.Directories.TempSandboxDllPath))
                {
                    Settings.Instance.Directories.TempSandboxDllPath =
                        Path.Combine(Settings.Instance.Directories.TempCoreDirectory,
                            RandomHelper.RandomString() + ".dll");
                }

                if (
                    !Md5Hash.Compare(
                        Md5Hash.ComputeFromFile(Settings.Instance.Directories.TempSandboxDllPath),
                        Md5Hash.ComputeFromFile(Settings.Instance.Directories.SandboxDllPath)))
                {
                    FileHelper.SafeCopyFile(Settings.Instance.Directories.SandboxDllPath,
                        Settings.Instance.Directories.TempCoreDirectory,
                        Path.GetFileName(Settings.Instance.Directories.TempSandboxDllPath));

                    // as requested by finn
                    FileHelper.SafeCopyFile(Settings.Instance.Directories.SandboxDllPath,
                        Settings.Instance.Directories.TempCoreDirectory);

                    Log.Instance.DoLog(string.Format("Copied EloBuddy.Sandbox.dll to: \"{0}\"",
                        Settings.Instance.Directories.TempSandboxDllPath));
                }
            }
            catch (Exception)
            {
                // ignored
            }

            // Copy EloBuddy.dll file
            try
            {
                if (!File.Exists(Settings.Instance.Directories.TempEloBuddyDllPath))
                {
                    Settings.Instance.Directories.TempEloBuddyDllPath =
                        Path.Combine(Settings.Instance.Directories.TempCoreDirectory,
                            RandomHelper.RandomString() + ".dll");
                }

                if (
                    !Md5Hash.Compare(
                        Md5Hash.ComputeFromFile(Settings.Instance.Directories.TempCoreDirectory),
                        Md5Hash.ComputeFromFile(Settings.Instance.Directories.EloBuddyDllPath)))
                {
                    FileHelper.SafeCopyFile(Settings.Instance.Directories.EloBuddyDllPath,
                        Settings.Instance.Directories.TempCoreDirectory,
                        Path.GetFileName(Settings.Instance.Directories.TempEloBuddyDllPath));

                    // as requested by finn
                    FileHelper.SafeCopyFile(Settings.Instance.Directories.EloBuddyDllPath,
                        Settings.Instance.Directories.TempCoreDirectory);

                    Log.Instance.DoLog(string.Format("Copied EloBuddy.dll to: \"{0}\"",
                        Settings.Instance.Directories.TempEloBuddyDllPath));
                }
            }
            catch (Exception)
            {
                // ignored
            }
        }
    }
}
