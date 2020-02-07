using System.CodeDom.Compiler;
using System.ServiceModel;
using EloBuddy.Networking.Objects;

namespace EloBuddy.Networking.Service
{
    [GeneratedCode("System.ServiceModel", "3.0.0.0")]
    [ServiceContract(ConfigurationName = "SR.IA")]
    public interface IA
    {
        [OperationContract(Action = "http://tempuri.org/IA/Do", ReplyAction = "http://tempuri.org/IA/DoResponse")]
        [ServiceKnownType(typeof(d0))]
        [ServiceKnownType(typeof(r0))]
        [ServiceKnownType(typeof(object[]))]
        [ServiceKnownType(typeof(AuthRequest))]
        [ServiceKnownType(typeof(TelemetryRequest))]
        [ServiceKnownType(typeof(AddonData))]
        [ServiceKnownType(typeof(StatisticsRequest))]
        d0 Do(byte header, object[] args);
    }
}
