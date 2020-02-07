using System;
using System.IO;
using System.Numerics;

namespace EloBuddy.Sandbox.Cryptography
{
    internal static class CustomRsa
    {
        internal static byte[] Decode(byte[] array, BigInteger exponent, BigInteger modulus)
        {
            var c = new BigInteger(array);
            var m = BigInteger.ModPow(c, exponent, modulus);

            return m.ToByteArray();
        }

        internal static byte[] DecodeBlock(byte[] array, BigInteger exponent, BigInteger modulus)
        {
            using (var writeStream = new MemoryStream())
            {
                using (var writer = new BinaryWriter(writeStream))
                {
                    using (var readStream = new MemoryStream(array))
                    {
                        using (var reader = new BinaryReader(readStream))
                        {
                            while (reader.PeekChar() != -1)
                            {
                                var blockSize = BitConverter.ToUInt16(reader.ReadBytes(2), 0);

                                if (blockSize == 0)
                                {
                                    break;
                                }

                                var block = reader.ReadBytes(blockSize);
                                writer.Write(Decode(block, exponent, modulus));
                            }

                            return writeStream.ToArray();
                        }
                    }
                }
            }
        }
    }
}
