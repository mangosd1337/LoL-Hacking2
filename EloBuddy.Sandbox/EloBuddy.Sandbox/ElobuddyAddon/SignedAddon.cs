using System;
using System.IO;
using System.Numerics;
using System.Runtime.InteropServices;
using System.Security.Cryptography;
using EloBuddy.Sandbox.Cryptography;

namespace EloBuddy.Sandbox.ElobuddyAddon
{
    internal static class SignedAddon
    {
        private const string PublicKey =
            "BgIAAACkAABSU0ExAAoAAAEAAQCX5QHlhrEBDk9l+7kHvKLcMeKxslu890/H/zJp2uuM5tOBeUz+ebfqD8aczVrIfgjPPYJNUbiJerPG7RhYtc08D9/08GLQozz/YhxfaHq1xQu+K/zsk9Vp/Zvg3sx/h8ThMeOO7Q8y/gH+OivrIP5jh9fCGQvQBVScmOk+rWv6GCgVFoi9Bcu4NPQhjf/Rkk0euLbhSfqs3G090/pz+9gfaW4WTyRhw4J80nuUdCiT/vCAzydIWJH+RINTxDGBNZajiGnGW82H5OwhW6XY+eRD0zfSNF9fUf5r+mmUl8BZ9d3L8flHc8Cw9Oqi6PGAYAyCY7+jJFjRxvavWNVFLrny7JBUpvJhkaWnfRD2tssUlbqlCzDSBpYGTxCBICm9YivO8NtGEagG305EgHwcHqLAkyFBlp3DbnmjCIiXTtUg5Q==";
        private static byte[] Modulus =
        {
            0x71, 0xdc, 0x0, 0x8b, 0xf5, 0xea, 0x58, 0x4d, 0xec, 0x21, 0x78, 0x7e, 0x89, 0x7d, 0x1d, 0x92, 0x5f, 0x2c, 0x72, 0xf6, 0x10, 0xcf, 0xa3, 0x21, 0xf0, 0x36,
            0xf5, 0x3b, 0x7, 0xc8, 0xb8, 0x3d, 0x73, 0x4c, 0xf8, 0x8e, 0x9, 0x2c, 0x77, 0xf8, 0x59, 0x35, 0xf, 0x1d, 0xbc, 0xc6, 0x4, 0x48, 0xd5, 0xda, 0x13, 0xe0, 0x12, 0xc3, 0x63, 0x91, 0x6d, 0x20,
            0xfb, 0x11, 0x42, 0xbd, 0xe5, 0x32, 0x96, 0x5f, 0xc2, 0xe8, 0xa5, 0x50, 0x7b, 0x7e, 0x76, 0xd2, 0x14, 0x12, 0x6b, 0x7
        };
        private static byte[] Exponent =
        {
            0x23, 0xb1, 0xc3, 0x39, 0x66, 0xd1, 0x3e, 0xc0, 0xdf, 0xc6, 0xd5, 0x91, 0x6b, 0x25, 0x36, 0x92, 0x1d, 0x78, 0x48, 0xe3, 0x4b, 0x79, 0x7e, 0x81, 0x4a, 0x11,
            0xb9, 0x7d, 0xa1, 0xa,
        };

        [StructLayout(LayoutKind.Sequential, Pack = 1, CharSet = CharSet.Ansi)]
        internal struct SignedAddonData
        {
            internal bool IsLibrary;

            [MarshalAs(UnmanagedType.ByValTStr, SizeConst = 20)]
            internal string Author;

            [MarshalAs(UnmanagedType.ByValTStr, SizeConst = 20)]
            internal string Version;

            [MarshalAs(UnmanagedType.ByValTStr, SizeConst = 20)]
            internal string Udefined0;

            [MarshalAs(UnmanagedType.ByValTStr, SizeConst = 20)]
            internal string Udefined1;

            [MarshalAs(UnmanagedType.ByValTStr, SizeConst = 20)]
            internal string Udefined2;

            [MarshalAs(UnmanagedType.ByValArray, SizeConst = 100)]
            internal byte[] Udefined3;
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

        internal static byte[] VerifyAndDecrypt(byte[] signedaddon)
        {
            SignedAddonHeader header;
            byte[] encryptedAssembly = null;

            using (var stream = new MemoryStream(signedaddon))
            {
                using (var reader = new BinaryReader(stream))
                {
                    var headerBuffer = reader.ReadBytes(Marshal.SizeOf(typeof (SignedAddonHeader)));
                    var assemblyBuffer = reader.ReadBytes(signedaddon.Length - headerBuffer.Length);

                    var gcHandle = GCHandle.Alloc(headerBuffer, GCHandleType.Pinned);
                    header = (SignedAddonHeader) Marshal.PtrToStructure(gcHandle.AddrOfPinnedObject(), typeof (SignedAddonHeader));
                    gcHandle.Free();

                    var cspParams = new CspParameters { ProviderType = 1 };

                    using (var rsaProvider = new RSACryptoServiceProvider(cspParams))
                    {
                        rsaProvider.ImportCspBlob(Convert.FromBase64String(PublicKey));

                        using (var sha1 = new SHA1CryptoServiceProvider())
                        {
                            if (rsaProvider.VerifyData(assemblyBuffer, sha1, header.Signature))
                            {
                                encryptedAssembly = assemblyBuffer;
                            }
                        }
                    }
                }
            }

            if (encryptedAssembly != null)
            {
                var key = CustomRsa.DecodeBlock(header.CryptoData.Key, new BigInteger(Exponent), new BigInteger(Modulus));
                return RijndaelHelper.Decrypt(encryptedAssembly, key, header.CryptoData.Salt, header.CryptoData.Iterations);
            }

            return null;
        }
    }
}
