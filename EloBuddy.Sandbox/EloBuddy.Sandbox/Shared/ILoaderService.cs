using System.Collections.Generic;
using System.ServiceModel;

namespace EloBuddy.Sandbox.Shared
{
    [ServiceContract]
    public interface ILoaderService
    {
        [OperationContract]
        List<SharedAddon> GetAssemblyList(int gameid);

        [OperationContract]
        Configuration GetConfiguration(int pid);

        [OperationContract]
        void Recompile(int pid);
    }
}
