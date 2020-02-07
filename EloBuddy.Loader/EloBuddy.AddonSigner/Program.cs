using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.IO;
using System.Linq;
using System.Numerics;
using System.Runtime.InteropServices;
using System.Security.Cryptography;

namespace EloBuddy.AddonSigner
{
    class Program
    {
        #region Crypto Keys
       
        private const string PublicKey = "BgIAAACkAABSU0ExAAoAAAEAAQCX5QHlhrEBDk9l+7kHvKLcMeKxslu890/H/zJp2uuM5tOBeUz+ebfqD8aczVrIfgjPPYJNUbiJerPG7RhYtc08D9/08GLQozz/YhxfaHq1xQu+K/zsk9Vp/Zvg3sx/h8ThMeOO7Q8y/gH+OivrIP5jh9fCGQvQBVScmOk+rWv6GCgVFoi9Bcu4NPQhjf/Rkk0euLbhSfqs3G090/pz+9gfaW4WTyRhw4J80nuUdCiT/vCAzydIWJH+RINTxDGBNZajiGnGW82H5OwhW6XY+eRD0zfSNF9fUf5r+mmUl8BZ9d3L8flHc8Cw9Oqi6PGAYAyCY7+jJFjRxvavWNVFLrny7JBUpvJhkaWnfRD2tssUlbqlCzDSBpYGTxCBICm9YivO8NtGEagG305EgHwcHqLAkyFBlp3DbnmjCIiXTtUg5Q==";
        private const string PrivateKey = "BwIAAACkAABSU0EyAAoAAAEAAQCX5QHlhrEBDk9l+7kHvKLcMeKxslu890/H/zJp2uuM5tOBeUz+ebfqD8aczVrIfgjPPYJNUbiJerPG7RhYtc08D9/08GLQozz/YhxfaHq1xQu+K/zsk9Vp/Zvg3sx/h8ThMeOO7Q8y/gH+OivrIP5jh9fCGQvQBVScmOk+rWv6GCgVFoi9Bcu4NPQhjf/Rkk0euLbhSfqs3G090/pz+9gfaW4WTyRhw4J80nuUdCiT/vCAzydIWJH+RINTxDGBNZajiGnGW82H5OwhW6XY+eRD0zfSNF9fUf5r+mmUl8BZ9d3L8flHc8Cw9Oqi6PGAYAyCY7+jJFjRxvavWNVFLrny7JBUpvJhkaWnfRD2tssUlbqlCzDSBpYGTxCBICm9YivO8NtGEagG305EgHwcHqLAkyFBlp3DbnmjCIiXTtUg5bO7Pph6G8NV2sPNgFK8SdwzWMs7IOxX+1rpbSNaZ0yl5VdbD8JVoBJKMgUlUW/qaszR5MTz4vTqel43ykBX89qPHtTqhZWAkTH0K7E+EqM7UMe2iGQsLpdiNRkqbHBNbLqwd5uJYwtPtT/5h8AIb069Xbnca3A47XP7FJrkHH5XDhFlJNjnl6WU/yXfGlEZDNeokpHh52Ra0qzzzjdsP/mNbCeZzu8m74ddwPn9tedbfTgiqPsuN4Vt66UBIYgEn6pJl02+oKasJ3qRi9PFgE+A1e+MVz83gCSl1ewaTvywodxDkBpVdCB3wrjNFkMz9d5ni2mdv/pd2ydQah4bWUtuB0574vJXlaquNJbLf4h14DVxdL9VJa4pQQJB03WLV88OJrRADvFKTBb9aPi6UPGLODwwsSROrW7yVZjW4FXr0+Yr+3SbHhl2a+93UvYCGdb/HPbz8aHr5bZ2HdgRWuwHYi8glJAl/9AZP2QBnGaZv1ywYT3X4f7kWk+249jLbd310x+OidWwaYsRzbokxspTK+X5OF3ZZyemPJG6rlKH+c0teJFyndX/CUVzPaWcIPF8Lb7PNtg2qclTxR6bPmB3psSs1xvYC3Ltq7NJt/acCe1D0bwZmafwmakzhwyF8SFQsgvmr2P96SD65zOkcc6ZSmidKl1LBfaqydthcD196yTrK0s8W22bZnpQBnYnQbRKfRITepY4Eczxb7rfDR4Fkp/zo5XmvEasd2w7Cu/gTk6MrVXlqsi6mcAF3IFU8kHBR6U5FnM53BuV3YDbUMub9D6tyYbrAHs84Dw/5JrucNQLIEUguCn6pSv8tn2F0z2VuxtLGeqLyCD6NfVf+3WdhlX/mEapTSD+njDJaAY4OgTwI24GnOc41ZQuE068WOCPoeOf9YIHt4FMcWBsG5L5rMKXaoYRqzdi6Q/U271HrbDSI17jWLwEOYh0uz0Qqva5wCn7VWvI7xfrxgUTKroXJSsFUxjU5sQ0ivXp+haMon9TAf7vyhny2B7Ze8KCe6eyYNzkfSl+0rih08Vqgc4BgMa6xoOZi/3Tqa35daG5Zc1CNs3+GNxMAoKd7vqxq5P3/cRlx61z8zdfYa3S1u6mSH2TNY+z03Hlo4YzKA+wXMlok8xrJlUNoc7S87840zHEj0o1i3ar6/R2tiwaA5pr7QICJiNPoCT7RgEAW9VP4rIpqDBkPqw7LGDj8Z6iuHP5RkA8GnqjzhDM3DkKowIEmrBAuKQHOedj51lcs6Yz8MnBQPjWgfHLf9X3QedSxwKSeWekabz9xokxA+wsWp8F9OKTIy5HO+ZGVziGJqH3XEt+77cDa87SWlsZAOSbUGJcBAdGSkf/rt9nYPKH+9gEyZMPLj1/h2OcIZIXs24cf7bMDNkk6rE2H6dXxASQnjPwd6OnH1Jih4GTSj2JE5grsNzS4RHLgs4p8ILdzgalo+vZ7BpdUVjb6XARraQ3gveqFO1KUzvdoR+hl9E4zDA=";
        
        private static readonly byte[] RsaModulus = { 0x71, 0xdc, 0x0, 0x8b, 0xf5, 0xea, 0x58, 0x4d, 0xec, 0x21, 0x78, 0x7e, 0x89, 0x7d, 0x1d, 0x92, 0x5f, 0x2c, 0x72, 0xf6, 0x10, 0xcf, 0xa3, 0x21, 0xf0, 0x36, 0xf5, 0x3b, 0x7, 0xc8, 0xb8, 0x3d, 0x73, 0x4c, 0xf8, 0x8e, 0x9, 0x2c, 0x77, 0xf8, 0x59, 0x35, 0xf, 0x1d, 0xbc, 0xc6, 0x4, 0x48, 0xd5, 0xda, 0x13, 0xe0, 0x12, 0xc3, 0x63, 0x91, 0x6d, 0x20, 0xfb, 0x11, 0x42, 0xbd, 0xe5, 0x32, 0x96, 0x5f, 0xc2, 0xe8, 0xa5, 0x50, 0x7b, 0x7e, 0x76, 0xd2, 0x14, 0x12, 0x6b, 0x7 };
        private static readonly byte[] RsaExponentPublic = { 0x23, 0xb1, 0xc3, 0x39, 0x66, 0xd1, 0x3e, 0xc0, 0xdf, 0xc6, 0xd5, 0x91, 0x6b, 0x25, 0x36, 0x92, 0x1d, 0x78, 0x48, 0xe3, 0x4b, 0x79, 0x7e, 0x81, 0x4a, 0x11, 0xb9, 0x7d, 0xa1, 0xa, };
        private static readonly byte[] RsaExponentPrivate = { 0x73, 0xfd, 0x22, 0x78, 0xa7, 0xdc, 0x10, 0x2, 0x64, 0x34, 0x81, 0x8d, 0xa8, 0x63, 0x7, 0x61, 0x29, 0x66, 0x8d, 0xdf, 0x71, 0x16, 0x43, 0x67, 0x6e, 0x71, 0x2, 0x3b, 0x35, 0xea, 0x9a, 0x8, 0x1b, 0xa9, 0x56, 0x2c, 0xa6, 0xb, 0xe3, 0x84, 0x62, 0x94, 0x27, 0x60, 0xc9, 0x38, 0xa5, 0xc9, 0x86, 0xc6, 0x5c, 0x80, 0xf3, 0x6d, 0x34, 0x42, 0x4c, 0xeb, 0x15, 0x14, 0xa6, 0xd0, 0x3c, 0xb0, 0x8d, 0xf3, 0xb5, 0xa6, 0xf3, 0x73, 0xc1, 0x10, 0x5, 0x5b, 0x6f, 0x61, 0xa8, 0x0 };
       
        #endregion

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

        private static int Main(string[] args)
        {
            const string signVersion = "2";

            if (args.Length == 1)
            {
                Console.WriteLine("Select an assembly to sign...");
                return 1;
            }

            // collect addon data
            var filePath = args[0];
            var devName = args.Length > 1 ? args[1] : FileVersionInfo.GetVersionInfo(filePath).CompanyName;
            var isLibrary = GetArg("--library", args) || filePath.EndsWith(".dll");
            var nobuddy = GetArg("--nobuddy", args);

            // init
            var assembly = File.ReadAllBytes(filePath);
            var random = new Random(Environment.TickCount + assembly.Length);

            using (var stream = new MemoryStream())
            {
                using (var writer = new BinaryWriter(stream))
                {
                    using (var rsaProvider = new RSACryptoServiceProvider(new CspParameters { ProviderType = 1 }))
                    {
                        using (var sha1 = new SHA1CryptoServiceProvider())
                        {
                            rsaProvider.ImportCspBlob(Convert.FromBase64String(PrivateKey));

                            // header
                            var header = new SignedAddonHeader
                            {
                                CryptoData = new SignedAddonCryptoData(),
                                Data = new SignedAddonData()
                                {
                                    CData = new byte[100]
                                }
                            };

                            // set addon data
                            header.Data.SignatureVersion = signVersion;
                            header.Data.Author = devName;
                            header.Data.IsLibrary = isLibrary;
                            header.Data.Version = FileVersionInfo.GetVersionInfo(filePath).FileVersion;
                            header.Data.CData[0] = (byte) (nobuddy ? 1 : 0);
                            header.Signature = new byte[320];

                            // encrypt
                            var blockSize = random.Next(30, 50);
                            var key = new byte[random.Next(400, 700)];
                            var salt = new byte[40];
                            var iterations = random.Next(1, 7);

                            random.NextBytes(key);
                            random.NextBytes(salt);
                            VerifyKey(key, blockSize);

                            assembly = RijndaelHelper.Encrypt(assembly, key, salt, iterations);

                            var keybuffer = new byte[16400];
                            key = CustomRsa.EncodeBlock(key, new BigInteger(RsaExponentPrivate), new BigInteger(RsaModulus), blockSize);
                            Array.Copy(key, 0, keybuffer, 0, key.Length);

                            // set crypto data
                            header.CryptoData.Salt = salt;
                            header.CryptoData.Iterations = iterations;
                            header.CryptoData.Key = keybuffer;

                            // sign
                            const int signatureSize = 320;

                            var headerSize0 = Marshal.SizeOf(typeof (SignedAddonHeader)) - signatureSize;
                            var signBuffer = new byte[headerSize0 + assembly.Length];
                            var buffer = new List<byte>();
                            buffer.AddRange(SerializeStructure(header));
                            buffer.AddRange(assembly);

                            Array.Copy(buffer.ToArray(), signatureSize, signBuffer, 0, signBuffer.Length);
                            header.Signature = rsaProvider.SignData(signBuffer, sha1);

                            // write to stream
                            // todo optimize
                            writer.Write(SerializeStructure(header));
                            writer.Write(assembly);
                        }
                    }
                }

                // save
                var path = Path.Combine(Path.GetDirectoryName(filePath), Path.GetFileNameWithoutExtension(filePath) + ".ebaddon");
                File.WriteAllBytes(path, stream.ToArray());
            }

            return 0;
        }

        private static byte[] SerializeStructure<T>(T structure) where T : struct
        {
            var buffer = new byte[Marshal.SizeOf(typeof (T))];
            var ptr = Marshal.AllocHGlobal(buffer.Length);
            Marshal.StructureToPtr(structure, ptr, true);
            Marshal.Copy(ptr, buffer, 0, buffer.Length);
            Marshal.FreeHGlobal(ptr);

            return buffer;
        }

        private static bool GetArg(string arg, IEnumerable<string> args, bool defautValue = false)
        {
            return args.Any(s => string.Equals(s, arg, StringComparison.CurrentCultureIgnoreCase)) || defautValue;
        }

        private static void VerifyKey(byte[] key, int blockSize)
        {
            for (var i = blockSize - 1; i < key.Length; i += blockSize)
            {
                if (key[i] == 0x0 || key[i] == 0xFF)
                {
                    key[i] = 0x1;
                }
            }

            var last = key[key.Length - 1];

            if (last == 0xFF || last == 0x0)
            {
                key[key.Length - 1] = 0x1;
            }
        }

        public static Tuple<string, string> CreateKeyPair()
        {
            var cspParams = new CspParameters { ProviderType = 1 };
            var rsaProvider = new RSACryptoServiceProvider((int) (1024 * 2.5f), cspParams);
            var publicKey = Convert.ToBase64String(rsaProvider.ExportCspBlob(false));
            var privateKey = Convert.ToBase64String(rsaProvider.ExportCspBlob(true));

            return new Tuple<string, string>(privateKey, publicKey);
        }
    }
}