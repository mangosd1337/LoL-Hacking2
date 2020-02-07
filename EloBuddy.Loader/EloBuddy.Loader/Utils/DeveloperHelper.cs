using System.IO;

namespace EloBuddy.Loader.Utils
{
    internal static class DeveloperHelper
    {
        internal static bool IsDeveloper
        {
            get { return Directory.Exists("Developer"); }
        }
    }
}