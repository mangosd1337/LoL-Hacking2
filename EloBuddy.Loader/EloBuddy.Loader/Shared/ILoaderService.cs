using System.Collections.Generic;
using System.ServiceModel;

namespace EloBuddy.Sandbox.Shared
{
    [ServiceContract]
    public interface ILoaderService
    {
        [OperationContract]
        List<SharedAddon> GetAssemblyList(int pid);

        [OperationContract]
        Configuration GetConfiguration(int pid);

        [OperationContract]
        void Recompile(int pid);
    }
}
