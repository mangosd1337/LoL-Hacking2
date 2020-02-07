using EloBuddy.Networking.Service;

namespace EloBuddy.Auth.Networking.Packets
{
    abstract class NetworkPacket
    {
        public abstract byte Header { get; }
        public abstract void OnReceive(object[] packet);
        public abstract d0 GetResponse();
    }
}
