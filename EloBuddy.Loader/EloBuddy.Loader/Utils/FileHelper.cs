using System;
using System.IO;
using EloBuddy.Loader.Data;

namespace EloBuddy.Loader.Utils
{
    internal static class FileHelper
    {
        internal static void CopyFile(string file, string directory, string name = "", bool overrideFile = true)
        {
            if (string.IsNullOrEmpty(name))
            {
                name = Path.GetFileName(file);
            }

            var newFilePath = Path.Combine(directory, name);

            if (!overrideFile && File.Exists(newFilePath))
            {
                throw new Exception(String.Format("The file {0} already exists.", newFilePath));
            }

            if (!Directory.Exists(directory))
            {
                Directory.CreateDirectory(directory);
            }

            File.WriteAllBytes(newFilePath, File.ReadAllBytes(file));
        }

        internal static void SafeCopyFile(string file, string directory, string name = "", bool overrideFile = true)
        {
            if (string.IsNullOrEmpty(name))
            {
                name = Path.GetFileName(file);
            }

            var newFilePath = Path.Combine(directory, name);

            if (!overrideFile && File.Exists(newFilePath))
            {
                throw new Exception(string.Format("The file {0} already exists.", newFilePath));
            }

            if (!Directory.Exists(directory))
            {
                Directory.CreateDirectory(directory);
            }

            SafeWriteAllBytes(newFilePath, File.ReadAllBytes(file));
        }

        internal static void SafeWriteAllBytes(string path, byte[] bytes)
        {
            if (File.Exists(path))
            {
                var temp = Path.Combine(Settings.Instance.Directories.TempDirectory, RandomHelper.RandomString() + Path.GetExtension(path));
                File.Move(path, temp);

                try
                {
                    File.Delete(temp);
                }
                catch (Exception)
                {
                    // ignored
                }
            }

            File.WriteAllBytes(path, bytes);
        }
    }
}
