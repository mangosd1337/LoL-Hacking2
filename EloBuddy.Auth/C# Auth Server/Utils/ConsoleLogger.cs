using System;

namespace EloBuddy.Auth.Utils
{
    internal static class ConsoleLogger
    {
        internal static void Write(string Message, ConsoleColor color = ConsoleColor.White)
        {
            Console.ForegroundColor = color;

            Console.WriteLine(string.Format("[Auth {0}] {1}",
             DateTime.Now.ToLongTimeString(),
             Message
            ));

            Console.ResetColor();
        }
    }
}
