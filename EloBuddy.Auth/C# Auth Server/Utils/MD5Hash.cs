using System;
using System.IO;
using System.Security.Cryptography;
using System.Text;
using System.Text.RegularExpressions;

namespace EloBuddy.Auth.Utils
{
    internal static class MD5Hash
    {
        public static string ComputeMD5Hash(byte[] inputBytes)
        {
            byte[] hash;

            using (var md5 = MD5.Create())
            {
                hash = md5.ComputeHash(inputBytes);
            }
          
            var sb = new StringBuilder();
            foreach (var b in hash)
            {
                sb.Append(b.ToString("x2"));
            }

            return sb.ToString();
        }

        public static string ComputeMD5Hash(string input)
        {
            return ComputeMD5Hash(Encoding.UTF8.GetBytes(input));
        }

        public static string ComputeMD5Hash(string input, string salt)
        {
            if (string.IsNullOrEmpty(salt))
            {
                return ComputeMD5Hash(input);
            }

            return ComputeMD5Hash(ComputeMD5Hash(input) + ComputeMD5Hash(salt));
        }

        public static string ComputeMD5HashFromFile(string path)
        {
            if (!File.Exists(path))
            {
                return string.Empty;
            }

            return ComputeMD5Hash(File.ReadAllBytes(path));
        }

        public static bool IsValidMD5Hash(string hash)
        {
            return new Regex("[0-9a-f]{32}").Match(hash).Success;
        }

        public static bool CompareMD5Hashes(string hash1, string hash2, bool skipInvalidHash = false)
        {
            if (!IsValidMD5Hash(hash1) || !IsValidMD5Hash(hash2))
            {
                return skipInvalidHash;
            }

            return string.Equals(hash1, hash2, StringComparison.CurrentCultureIgnoreCase);
        }
    }
}