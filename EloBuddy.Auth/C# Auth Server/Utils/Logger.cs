using System;
using System.Collections.Generic;
using System.IO;

namespace EloBuddy.Auth.Utils
{
    internal class Logger
    {
        public List<Tuple<LogType, string>> Logs { get; private set; }
        public string LogFilePath { get; private set; }

        private static object _syncLock;
        private static Logger _instance;

        public static Logger Instance
        {
            get
            {
                if (_instance == null)
                {
                    _instance = new Logger("EloBuddy.Auth.Logs.txt");
                }

                return _instance;
            }
        }

        public Logger(string logfile, bool overwriteFile = false)
        {
            if (overwriteFile && File.Exists(logfile))
            {
                File.Delete(logfile);
            }

            _syncLock = new object();
            LogFilePath = logfile;
            Logs = new List<Tuple<LogType, string>>();
        }

        public void DoLog(string text, LogType logtype = LogType.Info)
        {
            try
            {
                lock (_syncLock)
                {
                    var logString = string.Format("[{0}] <{1}>\r {2}\r\n", DateTime.Now, logtype, text);

                    Logs.Add(new Tuple<LogType, string>(logtype, logString));
                    File.AppendAllText(LogFilePath, logString);
                }
            }
            catch (Exception ex)
            {
                ConsoleLogger.Write(ex.ToString());
            }
        }

        public enum LogType
        {
            Info,
            Error
        }
    }
}