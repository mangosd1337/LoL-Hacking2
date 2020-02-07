using System.Linq;
using System.Reflection;

namespace EloBuddy.Sandbox
{
    internal static class Extensions
    {
        internal static string GenerateToken(this AssemblyName assemblyName)
        {
            return assemblyName.Name + assemblyName.GetPublicKeyToken().Select(o => o.ToString("x2")).Concat(new[] { string.Empty }).Aggregate(string.Concat);
        }

        internal static bool IsDosExecutable(this byte[] buffer)
        {
            if (buffer.Length < 5)
            {
                return false;
            }

            return buffer[0] == 0x4D && buffer[1] == 0x5A && buffer[2] == 0x90 && buffer[3] == 0x00 && buffer[4] == 0x03 && buffer[4] == 0x00;
        }
    }
}
