using System;
using System.Collections.Generic;
using System.Linq;
using System.Net;
using System.Text;
using Newtonsoft.Json;

#pragma warning disable 1591

namespace EloBuddy.Loader.Models
{
    public class NewsItems
    {
        public NewsItems()
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
                            "https://raw.githubusercontent.com/EloBuddy/EloBuddy.Dependencies/master/news.json");
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
            var jsonNews = JsonConvert.DeserializeObject<EloBuddyNews>(jsonString);
            var news = jsonNews.News.OrderByDescending(o => o.PostDate);
            foreach (var singleNews in news)
            {
                AllNews.Add(new XmlNewsItem
                {
                    PostDate = singleNews.PostDate.ToLocalTime().ToString("MM/dd/yy H:mm:ss"),
                    Header = singleNews.Header,
                    Content = singleNews.Content
                });
            }
        }

        public List<XmlNewsItem> AllNews { get; set; }
    }

    public class XmlNewsItem
    {
        public string PostDate { get; set; }
        public string Header { get; set; }
        public string Content { get; set; }
    }

    public class NewsItem
    {
        public string Header { get; set; }
        public string Content { get; set; }
        public DateTime PostDate { get; set; }
    }

    public class EloBuddyNews
    {
        [JsonProperty("news")]
        public List<NewsItem> News;
    }
}
