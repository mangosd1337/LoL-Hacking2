using System;
using System.IO;
using System.Security.Cryptography;
using System.Text;
using System.Text.RegularExpressions;

namespace EloBuddy.Loader.Utils
{
    internal static class Md5Hash
    {
        public static string Compute(byte[] inputBytes)
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

        public static string Compute(string input)
        {
            return Compute(Encoding.UTF8.GetBytes(input));
        }

        public static string Compute(string input, string salt)
        {
            if (string.IsNullOrEmpty(salt))
            {
                return Compute(input);
            }

            return Compute(Compute(input) + Compute(salt));
        }

        public static string ComputeFromFile(string path)
        {
            if (!File.Exists(path))
            {
                return string.Empty;
            }

            return Compute(File.ReadAllBytes(path));
        }

        public static bool IsValid(string hash)
        {
            return new Regex("[0-9a-f]{32}").Match(hash).Success;
        }

        public static bool Compare(string hash1, string hash2, bool skipInvalidHash = false)
        {
            if (!IsValid(hash1) || !IsValid(hash2))
            {
                return skipInvalidHash;
            }

            return string.Equals(hash1, hash2, StringComparison.CurrentCultureIgnoreCase);
        }
    }
}
