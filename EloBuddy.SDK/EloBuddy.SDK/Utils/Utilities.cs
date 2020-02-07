using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;

namespace EloBuddy.SDK.Utils
{
    public static class Utilities
    {
        private static readonly Random random = new Random(Environment.TickCount);

        public static int GetRandomNumber(int min, int max)
        {
            return random.Next(min, max);
        }
    }
}
