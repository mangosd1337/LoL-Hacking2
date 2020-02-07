using System;
using System.Text;

namespace EloBuddy.Loader.Utils
{
    public static class RandomHelper
    {
        public static Random Random { get; private set; }

        static RandomHelper()
        {
            Random = new Random(Environment.TickCount);
        }

        public static string RandomString(int length = 15, char[] chars = null)
        {
            var str = new StringBuilder();
            chars = chars ?? "abcdefghijklmnopqrstvuwxyzABCDEFGHIJKLMNOPQRSTVUWXYZ0123456789".ToCharArray();

            for (var i = 0; i < length; i++)
            {
                str.Append(chars[Random.Next(chars.Length)]);
            }

            return str.ToString();
        }
    }
}
