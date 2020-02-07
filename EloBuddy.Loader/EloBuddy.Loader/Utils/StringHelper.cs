using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;

namespace EloBuddy.Loader.Utils
{
    internal static class StringHelper
    {
        internal static string EncodePassword(string password)
        {
            return
                password.Replace("&", "&amp;")
                    .Replace("\\", "&#092;")
                    .Replace("!", "&#33;")
                    .Replace("$", "&#036;")
                    .Replace("\"", "&quot;")
                    .Replace("<", "&lt;")
                    .Replace(">", "&gt;")
                    .Replace("'", "&#39;");
        }
    }
}
