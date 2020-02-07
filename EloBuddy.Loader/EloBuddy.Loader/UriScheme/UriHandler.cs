using System;
using System.Web;
using EloBuddy.Loader.Globals;
using EloBuddy.Loader.Installers;

namespace EloBuddy.Loader.UriScheme
{
    internal static class UriHandler
    {
        internal static void Process(string url)
        {
            var uri = new Uri(url);
            switch (uri.Authority)
            {
                case "install":
                    var host = HttpUtility.ParseQueryString(uri.Query).Get("host");
                    var projects = HttpUtility.ParseQueryString(uri.Query).Get("project") ?? "";
                    AddonInstaller.InstallAddonsFromRepo(host, projects.Split(';'));
                    break;

                default: //legacy
                    var urischeme = Constants.UriSchemePrefix + "://";
                    url = url.Replace(urischeme, "https://github.com/");
                    AddonInstaller.InstallAddonsFromRepo(url);
                    break;
            }
        }
    }
}