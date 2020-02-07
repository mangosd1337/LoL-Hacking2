using System;
using System.IO;
using EloBuddy.Loader.Logger;
using EloBuddy.Loader.Utils;
using LibGit2Sharp;

namespace EloBuddy.Loader.Git
{
    public static class GitDownloader
    {
        public static bool Download(string url, string directory)
        {
            if (Directory.Exists(directory))
            {
                DirectoryHelper.DeleteDirectory(directory);
            }

            Log.Instance.DoLog(string.Format("Cloning repository: \"{0}\" to directory: \"{1}\".", url,
                directory));

            try
            {
                Repository.Clone(url, directory, new CloneOptions { Checkout = true });

                Log.Instance.DoLog(string.Format("Successfully cloned repository: \"{0}\" to directory: \"{1}\".", url,
                    directory));
            }
            catch (Exception ex)
            {
                Log.Instance.DoLog(
                    string.Format(
                        "Exception while cloning repository: \"{0}\". Clone directory: \"{1}\"\r\nException: {2}",
                        url, directory, ex), Log.LogType.Error);

                try
                {
                    DirectoryHelper.DeleteDirectory(directory);
                }
                catch (Exception)
                {
                    // ignored
                }

                return false;
            }

            return true;
        }

        public static bool UpdateRepository(string directory)
        {
            if (Repository.IsValid(directory))
            {
                try
                {
                    using (var repo = new Repository(directory))
                    {
                        repo.Fetch("origin");
                        repo.Checkout("origin/master", new CheckoutOptions { CheckoutModifiers = CheckoutModifiers.Force });
                    }
                    return true;
                }
                catch (Exception)
                {
                    // ignored
                }
            }
            return false;
        }
    }
}
