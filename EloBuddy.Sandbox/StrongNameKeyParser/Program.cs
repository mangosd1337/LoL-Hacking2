using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.IO;
using System.Linq;
using System.Reflection;
using System.Text;
using System.Windows.Forms;

namespace StrongNameKeyParser
{
    internal class Program
    {
        [STAThread]
        private static void Main(string[] args)
        {
            var assemblyName = AssemblyName.GetAssemblyName(@"C:\Users\Hellsing\OneDrive\Development\EloBuddy\Loader\System\EloBuddy.SDK.dll");
            Clipboard.SetText(assemblyName.GetPublicKey().ToHex());

            return;

            //using (var stream = File.OpenRead(@"C:\Users\Hellsing\OneDrive\Development\EloBuddy\EloBuddy.SDK\EloBuddy.SDK\EloBuddy.SDK.snk"))
            using (var stream = File.OpenRead(@"C:\Users\Hellsing\OneDrive\Development\EloBuddy\Loader\System\SharpDX.dll"))
            {
                var keyBytes = new byte[stream.Length];
                stream.Read(keyBytes, 0, (int) stream.Length);

                var kp = new StrongNameKeyPair(keyBytes);
                Clipboard.SetText(kp.PublicKey.ToHex());
            }
        }
    }

    internal static class Helper
    {
        public static string[] HexTbl = Enumerable.Range(0, 256).Select(v => v.ToString("X2")).ToArray();
        public static string ToHex(this byte[] array)
        {
            var s = new StringBuilder(array.Length * 2);
            foreach (var v in array)
            {
                s.Append("0x");
                s.Append(HexTbl[v].ToLower());
                s.Append(", ");
            }
            return s.ToString();
        }
    }
}
