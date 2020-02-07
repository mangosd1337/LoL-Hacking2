using System;
using EloBuddy.SDK.Enumerations;

namespace EloBuddy.SDK.Utils
{
    public static class Logger
    {
        public static void Log(LogLevel logLevel, string message, params object[] args)
        {
            var consoleColor = Console.ForegroundColor;

            switch (logLevel)
            {
                case LogLevel.Debug:
                    consoleColor = ConsoleColor.Cyan;
                    break;
                case LogLevel.Error:
                    consoleColor = ConsoleColor.Red;
                    break;
                case LogLevel.Warn:
                    consoleColor = ConsoleColor.Magenta;
                    break;
                default:
                    // Default color
                    break;
            }

            Console.ForegroundColor = consoleColor;
            Console.WriteLine("[{0:H:mm:ss} - {1}] {2}", DateTime.Now, logLevel, string.Format(message, args));
            Console.ResetColor();
        }

        public static void Info(string message, params object[] args)
        {
            Log(LogLevel.Info, message, args);
        }

        public static void Debug(string message, params object[] args)
        {
            Log(LogLevel.Debug, message, args);
        }

        public static void Warn(string message, params object[] args)
        {
            Log(LogLevel.Warn, message, args);
        }

        public static void Error(string message, params object[] args)
        {
            Log(LogLevel.Error, message, args);
        }

        public static void Exception(LogLevel logLevel, string headerMessage, object exceptionObject, params object[] args)
        {
            Log(logLevel, "");
            Log(logLevel, "===================================================");
            Log(logLevel, headerMessage, args);
            Log(logLevel, "");
            Log(logLevel, "Stacktrace of the Exception:");
            Log(logLevel, "");
            var exception = exceptionObject as Exception;
            if (exception != null)
            {
                Log(logLevel, "Type: {0}", exception.GetType().FullName);
                Log(logLevel, "Message: {0}", exception.Message);
                Log(logLevel, "");
                Log(logLevel, "Stracktrace:");
                Log(logLevel, exception.StackTrace);
                exception = exception.InnerException;
                if (exception != null)
                {
                    Log(logLevel, "");
                    Log(logLevel, "InnerException(s):");
                    do
                    {
                        Log(logLevel, "---------------------------------------------------");
                        Log(logLevel, "Type: {0}", exception.GetType().FullName);
                        Log(logLevel, "Message: {0}", exception.Message);
                        Log(logLevel, "");
                        Log(logLevel, "Stracktrace:");
                        Log(logLevel, exception.StackTrace);
                        exception = exception.InnerException;
                    } while (exception != null);
                    Log(logLevel, "---------------------------------------------------");
                }
            }

            Log(logLevel, "===================================================");
            Log(logLevel, "");
        }

        public static void Exception(string headerMessage, object exceptionObject, params object[] args)
        {
            Exception(LogLevel.Error, headerMessage, exceptionObject, args);
        }
    }
}
