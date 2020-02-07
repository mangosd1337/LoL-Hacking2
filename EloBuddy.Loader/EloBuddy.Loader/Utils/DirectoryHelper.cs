using System.IO;
using EloBuddy.Loader.Data;

namespace EloBuddy.Loader.Utils
{
    public static class DirectoryHelper
    {
        public static void CopyDirectory(string sourceDirName, string destDirName, bool copySubDirs)
        {
            // Get the subdirectories for the specified directory.
            DirectoryInfo dir = new DirectoryInfo(sourceDirName);
            DirectoryInfo[] dirs = dir.GetDirectories();

            if (!dir.Exists)
            {
                throw new DirectoryNotFoundException(
                    "Source directory does not exist or could not be found: "
                    + sourceDirName);
            }

            // If the destination directory doesn't exist, create it. 
            if (!Directory.Exists(destDirName))
            {
                Directory.CreateDirectory(destDirName);
            }

            // Get the files in the directory and copy them to the new location.
            FileInfo[] files = dir.GetFiles();
            foreach (FileInfo file in files)
            {
                string temppath = Path.Combine(destDirName, file.Name);
                file.CopyTo(temppath, false);
            }

            // If copying subdirectories, copy them and their contents to new location. 
            if (copySubDirs)
            {
                foreach (DirectoryInfo subdir in dirs)
                {
                    string temppath = Path.Combine(destDirName, subdir.Name);
                    CopyDirectory(subdir.FullName, temppath, copySubDirs);
                }
            }
        }

        public static void DeleteDirectory(string directory, bool deleteMainDirectory = true)
        {
            if (!Directory.Exists(directory))
            {
                return;
            }

            foreach (var file in Directory.GetFiles(directory))
            {
                File.SetAttributes(file, FileAttributes.Normal);
                File.Delete(file);
            }

            foreach (var subDirectory in Directory.GetDirectories(directory))
            {
                DeleteDirectory(subDirectory);
            }

            if (deleteMainDirectory)
            {
                Directory.Delete(directory);
            }
        }

        public static string GetTempDirectoryForDownload(string url)
        {
            return Path.Combine(Settings.Instance.Directories.TempDirectory, url.GetHashCode().ToString("X") + "\\");
        }
    }
}
