using System;
using System.Runtime.InteropServices;

namespace EloBuddy.Auth.Events
{
    public static class ProcessExit
    {
        [DllImport("Kernel32")]
        public static extern bool SetConsoleCtrlHandler(HandlerRoutine Handler, bool Add);

        public delegate bool HandlerRoutine(CtrlTypes CtrlType);

        public enum CtrlTypes
        {
            CTRL_C_EVENT = 0,
            CTRL_BREAK_EVENT,
            CTRL_CLOSE_EVENT,
            CTRL_LOGOFF_EVENT = 5,
            CTRL_SHUTDOWN_EVENT
        }

        public static void AddHandler(EventHandler routine)
        {
            AppDomain.CurrentDomain.ProcessExit += new EventHandler(routine); 
            //SetConsoleCtrlHandler(routine, true);
        }
    }
}
