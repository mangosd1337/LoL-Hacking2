using System;
using System.Collections.Generic;
using System.Linq;
using System.Net;
using System.Text;
using Newtonsoft.Json;

#pragma warning disable 1591

namespace EloBuddy.Loader.Models
{
    public class CoreNews
    {
        public CoreNews()
        {
            // Initialize properties
            AllNews = new List<XmlNewsItem>();

            // Download news json
            string jsonString;
            using (var webClient = new WebClient())
            {
                webClient.Encoding = Encoding.UTF8;
                try
                {
                    jsonString =
                        webClient.DownloadString(
                            "https://raw.githubusercontent.com/EloBuddy/EloBuddy.Dependencies/master/core.json?_=1");
                }
                catch (Exception)
                {
                    AllNews.Add(new XmlNewsItem
                    {
                        PostDate = DateTime.Now.ToString("MM/dd/yy H:mm:ss"),
                        Header = "Could not retrieve news from the server!",
                        Content =
                            "It seems like the update server is either down or this program is blocked by your firewall!"
                    });
                    return;
                }
            }

            // Convert classes and add them to the list
            var jsonNews = JsonConvert.DeserializeObject<CoreNewsList>(jsonString);
            foreach (var news in jsonNews.News.OrderByDescending(o => Convert.ToInt32(o.Build)))
            {
                var buildChanges = news.Changes.Aggregate("", (current, change) => current + string.Format("- {0}\r\n", change));

                AllNews.Add(new XmlNewsItem
                {
                    Header = "Core #" + news.Build,
                    Content = buildChanges
                });
            }
        }

        public List<XmlNewsItem> AllNews { get; set; }
    }

    public class CoreNewsItem
    {
        [JsonProperty("build")]
        public string Build { get; set; }
        [JsonProperty("changes")]
        public string[] Changes { get; set; }
        [JsonProperty("md5")]
        public string[] Hash { get; set; }
    }

    public class CoreNewsList
    {
        [JsonProperty("core")]
        public List<CoreNewsItem> News;
    }
}
