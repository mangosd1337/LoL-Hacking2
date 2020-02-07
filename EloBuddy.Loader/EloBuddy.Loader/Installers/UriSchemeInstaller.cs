using Microsoft.Win32;

namespace EloBuddy.Loader.Installers
{
    internal static class UriSchemeInstaller
    {
        internal static void InstallUriScheme(string name, string exePath, string openCommand = "\"{0}\" \"%1\" \"%2\"")
        {
            var root = string.Format(@"HKEY_CLASSES_ROOT\{0}", name);
            var defaultIcon = root + @"\DefaultIcon";
            var shell = root + @"\shell";
            var shellOpen = shell + @"\open";
            var shellOpenCommand = shellOpen + @"\command";

            Registry.SetValue(root, "", string.Format("URL:{0} Protocol", name), RegistryValueKind.String);
            Registry.SetValue(root, "URL Protocol", "", RegistryValueKind.String);
            Registry.SetValue(defaultIcon, "", exePath, RegistryValueKind.String);
            Registry.SetValue(shellOpenCommand, "", string.Format(openCommand, exePath), RegistryValueKind.String);
        }
    }
}