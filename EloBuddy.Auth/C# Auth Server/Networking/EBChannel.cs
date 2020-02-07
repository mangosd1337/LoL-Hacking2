using System;
using System.Linq;
using System.Reflection;
using EloBuddy.Auth.Networking.Packets;
using EloBuddy.Auth.Utils;
using EloBuddy.Networking.Service;

namespace EloBuddy.Auth.Networking
{
    class EbChannel : IA
    {
        public NetworkPacket[] Packets { get; private set; }

        public d0 Do(byte header, object[] data)
        {
            try
            {
                if (Packets == null)
                {
                    Packets =
                        Assembly.GetExecutingAssembly()
                            .GetTypes()
                            .Where(p => typeof (NetworkPacket).IsAssignableFrom(p) && !p.IsAbstract)
                            .Select(Activator.CreateInstance)
                            .OfType<NetworkPacket>()
                            .ToArray();
                }

                foreach (var packet in Packets)
                {
                    if (packet.Header == header)
                    {
                        packet.OnReceive(data);
                        return packet.GetResponse();
                    }
                }
            }
            catch (Exception ex)
            {
                ConsoleLogger.Write(ex.ToString(), ConsoleColor.Red);
            }

            return new d0()
            {
                Success = false,
                Body = "Server internal exception"
            };
        }
    }
}
