using System;
using System.Diagnostics;

namespace EloBuddy.SDK.Utils
{
    public class TimeMeasure : IDisposable
    {
        public string Name { get; set; }
        public bool OutputConsole { get; set; }
        public bool OutputChat { get; set; }

        public Stopwatch Timer { get; set; }

        public TimeMeasure(string name = "TimeMeasure", bool outputConsole = true, bool outputChat = false)
        {
            // Apply properties
            Name = name;
            OutputConsole = outputConsole;
            OutputChat = outputChat;

            // Create a new stopwatch and start it
            Timer = new Stopwatch();
            Timer.Start();
        }

        public void Dispose()
        {
            // Stop the timer
            Timer.Stop();

            if (OutputChat)
            {
                Chat.Print("{0}: {1}", Name, Timer.Elapsed);
            }
            if (OutputConsole)
            {
                Logger.Info("{0}: Action took {1}", Name, Timer.Elapsed);
            }
        }
    }
}
