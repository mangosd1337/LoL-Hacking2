using System;
using System.Collections.Generic;
using System.IO;
using System.Windows;
using EloBuddy.Loader.Data;
using EloBuddy.Loader.Globals;
using NLog;

namespace EloBuddy.Loader.Logger
{
    public class Log
    {
        private static readonly NLog.Logger NLog = LogManager.GetCurrentClassLogger();

        private static object _syncLock;
        private static Log _instance;

        private static string UserFolder
        {
            get { return Environment.GetFolderPath(Environment.SpecialFolder.UserProfile); }
        }

        private static string CensorLog(string log)
        {
            //hide username
            return log.Replace(UserFolder, Path.Combine(Directory.GetParent(UserFolder).FullName, "****"));
        }

        public List<Tuple<LogType, string>> Logs { get; private set; }
        public string LogFilePath { get; private set; }

        public static Log Instance
        {
            get
            {
                if (_instance == null)
                {
                    _instance = new Log(Path.Combine(Settings.Instance.Directories.LogsDirectory, Constants.LoaderMainLogFileName), true);
                }

                return _instance;
            }
        }

        public Log(string logfile, bool overwriteFile = false)
        {
            if (overwriteFile && File.Exists(logfile))
            {
                File.Delete(logfile);
            }

            _syncLock = new object();
            LogFilePath = logfile;
            Logs = new List<Tuple<LogType, string>>();
        }

        public void DoLog(string text, LogType logType = LogType.Info)
        {
            try
            {
                lock (_syncLock)
                {
                    var logString = string.Format("[{0}] <{1}>\r {2}\r\n", DateTime.Now, logType, text);

                    Logs.Add(new Tuple<LogType, string>(logType, logString));
                    File.AppendAllText(LogFilePath, CensorLog(logString));
                }

                switch (logType)
                {
                    case LogType.Info:
                        NLog.Info(text);
                        break;
                    case LogType.Error:
                        NLog.Error(text);
                        break;
                    default:
                        throw new ArgumentOutOfRangeException("logType", logType, null);
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show(ex.ToString(), "Log Error", MessageBoxButton.OK, MessageBoxImage.Error);
            }
        }

        public enum LogType
        {
            Info,
            Error
        }
    }
}
