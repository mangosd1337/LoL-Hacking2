using System.Net;
// ReSharper disable MemberCanBePrivate.Global

namespace EloBuddy.Auth.Utils
{
    public static class Web
    {
        static Web()
        {
        }

        public static WebClient GetWebClient()
        {
            var w = new WebClient();
            w.Headers["User-Agent"] = "EloBuddy.Auth Service";
            return w;
        }

        public static string Get(string url)
        {
            using (var w = GetWebClient())
            {
                return w.DownloadString(url);
            }
        }
    }
}