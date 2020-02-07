using System;
using System.IO;
using System.Numerics;
using System.Runtime.InteropServices;
using System.Security.Cryptography;
using EloBuddy.Loader.Cryptography;
using EloBuddy.Loader.Data;
using EloBuddy.Loader.Logger;
using EloBuddy.Loader.Utils;

namespace EloBuddy.Loader.AddonHandlers
{
    internal class SignedAddonHandler : AddonHandler
    {
        private const string PublicKey = "BgIAAACkAABSU0ExAAoAAAEAAQCX5QHlhrEBDk9l+7kHvKLcMeKxslu890/H/zJp2uuM5tOBeUz+ebfqD8aczVrIfgjPPYJNUbiJerPG7RhYtc08D9/08GLQozz/YhxfaHq1xQu+K/zsk9Vp/Zvg3sx/h8ThMeOO7Q8y/gH+OivrIP5jh9fCGQvQBVScmOk+rWv6GCgVFoi9Bcu4NPQhjf/Rkk0euLbhSfqs3G090/pz+9gfaW4WTyRhw4J80nuUdCiT/vCAzydIWJH+RINTxDGBNZajiGnGW82H5OwhW6XY+eRD0zfSNF9fUf5r+mmUl8BZ9d3L8flHc8Cw9Oqi6PGAYAyCY7+jJFjRxvavWNVFLrny7JBUpvJhkaWnfRD2tssUlbqlCzDSBpYGTxCBICm9YivO8NtGEagG305EgHwcHqLAkyFBlp3DbnmjCIiXTtUg5Q==";
        private static byte[] Modulus = { 0x71, 0xdc, 0x0, 0x8b, 0xf5, 0xea, 0x58, 0x4d, 0xec, 0x21, 0x78, 0x7e, 0x89, 0x7d, 0x1d, 0x92, 0x5f, 0x2c, 0x72, 0xf6, 0x10, 0xcf, 0xa3, 0x21, 0xf0, 0x36, 0xf5, 0x3b, 0x7, 0xc8, 0xb8, 0x3d, 0x73, 0x4c, 0xf8, 0x8e, 0x9, 0x2c, 0x77, 0xf8, 0x59, 0x35, 0xf, 0x1d, 0xbc, 0xc6, 0x4, 0x48, 0xd5, 0xda, 0x13, 0xe0, 0x12, 0xc3, 0x63, 0x91, 0x6d, 0x20, 0xfb, 0x11, 0x42, 0xbd, 0xe5, 0x32, 0x96, 0x5f, 0xc2, 0xe8, 0xa5, 0x50, 0x7b, 0x7e, 0x76, 0xd2, 0x14, 0x12, 0x6b, 0x7 };
        private static byte[] Exponent = { 0x23, 0xb1, 0xc3, 0x39, 0x66, 0xd1, 0x3e, 0xc0, 0xdf, 0xc6, 0xd5, 0x91, 0x6b, 0x25, 0x36, 0x92, 0x1d, 0x78, 0x48, 0xe3, 0x4b, 0x79, 0x7e, 0x81, 0x4a, 0x11, 0xb9, 0x7d, 0xa1, 0xa, };

        [StructLayout(LayoutKind.Sequential, Pack = 1, CharSet = CharSet.Ansi)]
        internal struct SignedAddonData
        {
            internal bool IsLibrary;

            [MarshalAs(UnmanagedType.ByValTStr, SizeConst = 20)]
            internal string Author;

            [MarshalAs(UnmanagedType.ByValTStr, SizeConst = 20)]
            internal string Version;

            [MarshalAs(UnmanagedType.ByValTStr, SizeConst = 20)]
            internal string SignatureVersion;

            [MarshalAs(UnmanagedType.ByValTStr, SizeConst = 20)]
            internal string Undefined1;

            [MarshalAs(UnmanagedType.ByValTStr, SizeConst = 20)]
            internal string Undefined2;

            [MarshalAs(UnmanagedType.ByValArray, SizeConst = 100)]
            internal byte[] CData;
        }

        [StructLayout(LayoutKind.Sequential, Pack = 1, CharSet = CharSet.Ansi)]
        internal struct SignedAddonCryptoData
        {
            [MarshalAs(UnmanagedType.ByValArray, SizeConst = 16400)] //256-bit RSA key x 200
            internal byte[] Key;

            [MarshalAs(UnmanagedType.ByValArray, SizeConst = 40)]
            internal byte[] Salt;

            internal int Iterations;
        }

        [StructLayout(LayoutKind.Sequential, Pack = 1, CharSet = CharSet.Ansi)]
        internal struct SignedAddonHeader
        {
            [MarshalAs(UnmanagedType.ByValArray, SizeConst = 320)]
            internal byte[] Signature;

            internal SignedAddonCryptoData CryptoData;

            internal SignedAddonData Data;
        }

        private static T DeserializeStructure<T>(byte[] buffer)
        {
            var gcHandle = GCHandle.Alloc(buffer, GCHandleType.Pinned);
            var structure = (T) Marshal.PtrToStructure(gcHandle.AddrOfPinnedObject(), typeof (T));
            gcHandle.Free();

            return structure;
        }

        private static bool Verify(byte[] signedaddon, out SignedAddonHeader header, out byte[] assembly)
        {
            using (var stream = new MemoryStream(signedaddon))
            {
                using (var reader = new BinaryReader(stream))
                {
                    using (var rsaProvider = new RSACryptoServiceProvider(new CspParameters { ProviderType = 1 }))
                    {
                        using (var sha1 = new SHA1CryptoServiceProvider())
                        {
                            rsaProvider.ImportCspBlob(Convert.FromBase64String(PublicKey));

                            var headerBuffer = reader.ReadBytes(Marshal.SizeOf(typeof (SignedAddonHeader)));
                            var assemblyBuffer = reader.ReadBytes(signedaddon.Length - headerBuffer.Length);
                            header = DeserializeStructure<SignedAddonHeader>(headerBuffer);

                            bool result;

                            switch (header.Data.SignatureVersion)
                            {
                                case "2":
                                    const int signatureSize = 320;
                                    var verifyBuffer = new byte[signedaddon.Length - signatureSize];
                                    Array.Copy(signedaddon, signatureSize, verifyBuffer, 0, verifyBuffer.Length);

                                    result = rsaProvider.VerifyData(verifyBuffer, sha1, header.Signature);
                                    break;

                                default:
                                    Log.Instance.DoLog("You are using an older version of the addon, support for older addons will be removed soon.");
                                    result = rsaProvider.VerifyData(assemblyBuffer, sha1, header.Signature);
                                    break;
                            }

                            if (result)
                            {
                                var key = CustomRsa.DecodeBlock(header.CryptoData.Key, new BigInteger(Exponent), new BigInteger(Modulus));
                                assembly = RijndaelHelper.Decrypt(assemblyBuffer, key, header.CryptoData.Salt, header.CryptoData.Iterations);

                                return true;
                            }
                        }
                    }
                }
            }

            assembly = null;
            return false;
        }

        internal override void Compile(ElobuddyAddon addon)
        {
            SignedAddonHeader header;
            byte[] assembly;

            if (Verify(File.ReadAllBytes(addon.ProjectFilePath), out header, out assembly))
            {
                // buddy check
                addon.IsBuddyAddon = header.Data.CData[0] != 0;

                addon.Type = header.Data.IsLibrary ? AddonType.Library : AddonType.Executable;
                addon.Author = header.Data.Author;
                addon.Version = header.Data.Version;

                FileHelper.SafeWriteAllBytes(addon.GetOutputFilePath(), assembly);
                addon.SetState(AddonState.Ready);
            }
            else
            {
                Log.Instance.DoLog(string.Format("Failed to install signed addon: \"{0}\". The addon is not properly signed!", addon.ProjectFilePath), Log.LogType.Error);
                addon.SetState(AddonState.CompilingError);
            }
        }
    }
}