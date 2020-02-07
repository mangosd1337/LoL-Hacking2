using System;
using System.ServiceModel;
using System.ServiceModel.Description;
using EloBuddy.Networking.Service;
using EloBuddy.Auth.Utils;

namespace EloBuddy.Auth.Networking
{
    class AuthService
    {
        internal const string AuthUri = "ec2-52-38-214-105.us-west-2.compute.amazonaws.com:443";

        internal static void Listen()
        {
            try
            {
                var serviceHost = new ServiceHost(typeof(EbChannel), new Uri("net.tcp://" + AuthUri + "/"));

                serviceHost.Faulted += delegate(object sender, EventArgs args)
                {
                    ConsoleLogger.Write("Service faulted", ConsoleColor.DarkYellow);
                };

                serviceHost.UnknownMessageReceived += delegate(object sender, UnknownMessageReceivedEventArgs args)
                {
                    ConsoleLogger.Write("Service UnknownMessageReceived: " , ConsoleColor.DarkYellow);
                };

                serviceHost.AddServiceEndpoint(typeof(IA), new NetTcpBinding(SecurityMode.None), "AuthService.svc");
                var serviceMetadataBehavior = new ServiceMetadataBehavior();
                serviceHost.Description.Behaviors.Add(serviceMetadataBehavior);
                serviceHost.AddServiceEndpoint("IMetadataExchange", MetadataExchangeBindings.CreateMexTcpBinding(), "mex");
                serviceHost.Open();

                ConsoleLogger.Write(string.Format("Listening on {0}", AuthUri));
            }
            catch (Exception ex)
            {
                ConsoleLogger.Write(ex.ToString(), ConsoleColor.DarkYellow);
            }
        }
    }
}
