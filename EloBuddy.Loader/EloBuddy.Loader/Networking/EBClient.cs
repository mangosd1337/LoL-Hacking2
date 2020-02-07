using System.CodeDom.Compiler;
using System.Diagnostics;
using System.ServiceModel;
using System.ServiceModel.Channels;
using EloBuddy.Networking.Service;

namespace EloBuddy.Loader.Networking
{
    [DebuggerStepThrough]
    [GeneratedCode("System.ServiceModel", "3.0.0.0")]
    internal class EbClient : ClientBase<IA>, IA
    {
        public EbClient()
        {
        }

        public EbClient(string endpointConfigurationName)
            : base(endpointConfigurationName)
        {
        }

        public EbClient(string endpointConfigurationName, string remoteAddress)
            : base(endpointConfigurationName, remoteAddress)
        {
        }

        public EbClient(string endpointConfigurationName, EndpointAddress remoteAddress)
            : base(endpointConfigurationName, remoteAddress)
        {
        }

        public EbClient(Binding binding, EndpointAddress remoteAddress)
            : base(binding, remoteAddress)
        {
        }

        public d0 Do(byte b, object[] args)
        {
            return base.Channel.Do(b, args);
        }
    }
}
