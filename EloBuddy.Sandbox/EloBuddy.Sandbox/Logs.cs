using System;
using System.Security.Permissions;

namespace EloBuddy.Sandbox
{
    internal static class Logs
    {
        internal static void Log(string text, params object[] args)
        {
            Console.WriteLine(text, args);
        }

        [PermissionSet(SecurityAction.Assert, Unrestricted = true)]
        public static void PrintException(object exceptionObject)
        {
            Log("");
            Log("===================================================");
            Log("An exception ocurred! EloBuddy might crash!");
            Log("");
            var exception = exceptionObject as Exception;
            if (exception != null)
            {
                Log("Type: {0}", exception.GetType().FullName);
                Log("Message: {0}", exception.Message);
                Log("");
                Log("Stracktrace:");
                Log(exception.StackTrace);
                exception = exception.InnerException;
                if (exception != null)
                {
                    Log("");
                    Log("InnerException(s):");
                    do
                    {
                        Log("---------------------------------------------------");
                        Log("Type: {0}", exception.GetType().FullName);
                        Log("Message: {0}", exception.Message);
                        Log("");
                        Log("Stracktrace:");
                        Log(exception.StackTrace);
                        exception = exception.InnerException;
                    } while (exception != null);
                    Log("---------------------------------------------------");
                }
            }

            Log("===================================================");
            Log("");
        }
    }
}
