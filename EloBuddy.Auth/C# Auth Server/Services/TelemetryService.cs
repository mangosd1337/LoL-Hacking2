using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.IO;
using System.Timers;
using EloBuddy.Auth.Events;
using EloBuddy.Auth.Utils;
using EloBuddy.Networking.Objects;

namespace EloBuddy.Auth.Services
{
    public static class TelemetryService
    {
        private const string TelemetrydataFile = "Data\\telemetry.dat";
        private static object _lock { get; set; }
        private static Timer _timer { get; set; }

        public static TelemetryData Telemetry { get; private set; }

        static TelemetryService()
        {
            Initialize();
        }

        private static bool _init;

        public static void Initialize()
        {
            if (_init)
            {
                return;
            }

            _init = true;
            _lock = new object();
            _timer = new Timer(60000);
            _timer.Elapsed += delegate(object sender, ElapsedEventArgs args)
            {
                Save();
            };
 
            Telemetry = File.Exists(TelemetrydataFile) ? (TelemetryData) Serialization.Deserialize(File.ReadAllBytes(TelemetrydataFile)) : new TelemetryData();
            ProcessExit.AddHandler(OnExit);
            _timer.Start();
        }

        public static void Save()
        {
            lock (_lock)
            {
                File.WriteAllBytes(TelemetrydataFile, Serialization.Serialize(Telemetry));
            }
        }

        public static void UpdateData(TelemetryRequest r)
        {
            Telemetry.Data.Add(new Tuple<DateTime, TelemetryRequest>(DateTime.Now, r));
        }

        private static void OnExit(object sender, EventArgs args)
        {
            Save();
        }

        [Serializable]
        public class TelemetryData
        {
            public List<Tuple<DateTime, TelemetryRequest>> Data { get; set; }

            public TelemetryData()
            {
                Data = new List<Tuple<DateTime, TelemetryRequest>>();
            }
        }
    }
}
