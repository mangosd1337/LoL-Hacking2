namespace EloBuddy.Loader.Networking
{
    internal class AuthService
    {
        internal static EbClient EbClient;

        static AuthService()
        {
            EbClient = new EbClient();
        }
    }
}
