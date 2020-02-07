using System;
using System.Collections.Generic;
using EloBuddy.Networking.Objects;

namespace EloBuddy.Auth.Services
{
    public static class TelemetryService
    {
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
