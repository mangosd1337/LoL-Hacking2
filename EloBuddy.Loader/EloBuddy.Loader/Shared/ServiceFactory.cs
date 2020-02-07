using System;
using System.ServiceModel;

namespace EloBuddy.Sandbox.Shared
{
    public static class ServiceFactory
    {
        private const string PipeName = "EloBuddy";

        public static TInterfaceType CreateProxy<TInterfaceType>(string name = "") where TInterfaceType : class
        {
            try
            {
                return
                    new ChannelFactory<TInterfaceType>(new NetNamedPipeBinding(),
                        new EndpointAddress("net.pipe://localhost/" + PipeName + name)).CreateChannel();
            }
            catch (Exception e)
            {
                throw new Exception(
                    "Failed to connect to pipe for communication. The targetted pipe may not be loaded yet. Desired interface: " +
                    typeof (TInterfaceType).Name, e);
            }
        }

        public static ServiceHost CreateService<TInterfaceType, TImplementationType>(bool open = true, string name = "")
            where TImplementationType : class
        {
            if (!typeof (TInterfaceType).IsAssignableFrom(typeof (TImplementationType)))
            {
                throw new NotImplementedException(typeof (TImplementationType).FullName + " does not implement " +
                                                  typeof (TInterfaceType).FullName);
            }

            var endpoint = new Uri("net.pipe://localhost/" + PipeName + name);
            var host = new ServiceHost(typeof (TImplementationType));

            host.AddServiceEndpoint(typeof (TInterfaceType), new NetNamedPipeBinding(), endpoint);
            host.Opened += (sender, args) => { Console.WriteLine("Opened: " + endpoint); };
            host.Faulted += (sender, args) => { Console.WriteLine("Faulted: " + endpoint); };
            host.UnknownMessageReceived += (sender, args) => { Console.WriteLine("UnknownMessage: " + endpoint); };

            if (open)
            {
                host.Open();
            }

            return host;
        }
    }
}
