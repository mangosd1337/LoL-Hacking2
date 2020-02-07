using System;
using EloBuddy.Auth.Services;
using EloBuddy.Auth.Utils;
using EloBuddy.Networking;
using EloBuddy.Networking.Objects;
using EloBuddy.Networking.Service;

// ReSharper disable MemberCanBePrivate.Global

namespace EloBuddy.Auth.Networking.Packets
{
    internal class StatisticsPacket : NetworkPacket
    {
        internal StatisticsRequest _statisticsRequest;

        public override byte Header
        {
            get { return (byte) Headers.Reserved2; }
        }

        public override void OnReceive(object[] packet)
        {
            if (packet == null || packet.Length == 0)
            {
                return;
            }

            _statisticsRequest = packet[0] as StatisticsRequest;
        }

        public override d0 GetResponse()
        {
            try
            {
                // checks
                if (_statisticsRequest == null || _statisticsRequest.Username != "statistics_user900_z" || _statisticsRequest.Password != "SECRET_PASSWORD_#$2992")
                {
                    return new d0
                    {
                        Success = false,
                    };
                }

                // create response packet
                var responsePacket = new d0
                {
                    Success = true,
                    Data = Serialization.Serialize(TelemetryService.Telemetry)
                };

                return responsePacket;
            }
            catch (Exception e)
            {
                ConsoleLogger.Write(e.ToString(), ConsoleColor.Red);
            }

            return new d0
            {
                Success = false
            };
        }
    }
}