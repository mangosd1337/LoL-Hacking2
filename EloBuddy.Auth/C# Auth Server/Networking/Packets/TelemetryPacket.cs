using System;
using System.IO;
using System.Runtime.Serialization;
using EloBuddy.Auth.Services;
using EloBuddy.Auth.Utils;
using EloBuddy.Networking;
using EloBuddy.Networking.Objects;
using EloBuddy.Networking.Service;

// ReSharper disable MemberCanBePrivate.Global

namespace EloBuddy.Auth.Networking.Packets
{
    internal class TelemetryPacket : NetworkPacket
    {
        internal TelemetryRequest _telemetryRequest;

        public override byte Header
        {
            get { return (byte) Headers.Reserved1; }
        }

        public override void OnReceive(object[] packet)
        {
            if (packet == null || packet.Length == 0)
            {
                return;
            }

            _telemetryRequest = packet[0] as TelemetryRequest;
        }

        public override d0 GetResponse()
        {
            try
            {
                // checks
                if (_telemetryRequest == null || _telemetryRequest.Token == null)
                {
                    return new d0
                    {
                        Success = false,
                    };
                }

                var result = false;

                if (TokenService.IsValidToken(_telemetryRequest.Token))
                {
                    TelemetryService.UpdateData(_telemetryRequest);
                    result = true;
                }

                // create response packet
                d0 responsePacket;
                using (var stream = new MemoryStream())
                {
                    var dataContractSerializer = new DataContractSerializer(typeof (TelemetryResponse));
                    dataContractSerializer.WriteObject(stream, new TelemetryResponse());

                    responsePacket = new d0
                    {
                        Success = result
                    };
                }

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