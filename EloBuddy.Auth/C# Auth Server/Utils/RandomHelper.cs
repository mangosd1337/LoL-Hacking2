using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;

namespace EloBuddy.Auth.Utils
{
    public static class RandomHelper
    {
        private static Random _random;

        public static Random Random
        {
            get
            {
                if (_random == null)
                {
                    _random = new Random(Environment.TickCount);
                }

                return _random;
            }
        }
    }
}
