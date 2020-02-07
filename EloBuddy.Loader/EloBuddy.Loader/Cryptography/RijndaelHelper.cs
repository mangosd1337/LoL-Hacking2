using System.IO;
using System.Security.Cryptography;

namespace EloBuddy.Loader.Cryptography
{
    internal static class RijndaelHelper
    {

#if DEBUG
        public static byte[] Encrypt(byte[] buffer, byte[] password, byte[] salt, int iterations)
        {
            using (var rijndael = Rijndael.Create())
            {
                var rfc = new Rfc2898DeriveBytes(password, salt, iterations);
                rijndael.Key = rfc.GetBytes(32);
                rijndael.IV = rfc.GetBytes(16);

                using (var memoryStream = new MemoryStream())
                {
                    using (var cryptoStream = new CryptoStream(memoryStream, rijndael.CreateEncryptor(), CryptoStreamMode.Write))
                    {
                        cryptoStream.Write(buffer, 0, buffer.Length);
                        cryptoStream.Close();
                        return memoryStream.ToArray();
                    }
                }
            }
        }
#endif

        public static byte[] Decrypt(byte[] buffer, byte[] password, byte[] salt, int iterations)
        {
            using (var rijndael = Rijndael.Create())
            {
                var rfc = new Rfc2898DeriveBytes(password, salt, iterations);
                rijndael.Key = rfc.GetBytes(32);
                rijndael.IV = rfc.GetBytes(16);

                using (var memoryStream = new MemoryStream())
                {
                    using (var cryptoStream = new CryptoStream(memoryStream, rijndael.CreateDecryptor(), CryptoStreamMode.Write))
                    {
                        cryptoStream.Write(buffer, 0, buffer.Length);
                        cryptoStream.Close();
                        return memoryStream.ToArray();
                    }
                }
            }
        }
    }
}
