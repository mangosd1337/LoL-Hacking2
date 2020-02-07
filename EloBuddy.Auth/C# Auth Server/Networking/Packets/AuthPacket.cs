using System;
using System.IO;
using System.Linq;
using System.Net;
using System.Runtime.Serialization;
using System.Runtime.Serialization.Json;
using System.Security.Cryptography;
using System.Text;
using System.Web;
using EloBuddy.Auth.Services;
using EloBuddy.Auth.Utils;
using EloBuddy.Networking;
using EloBuddy.Networking.Objects;
using EloBuddy.Networking.Service;

// ReSharper disable MemberCanBePrivate.Global

namespace EloBuddy.Auth.Networking.Packets
{
    internal class AuthPacket : NetworkPacket
    {
        internal AuthRequest _authRequest;
        internal const string ApiUrl = "http://elobuddy.net/api/auth/auth.php?username={0}&password={1}";

        private const string PrivateKey =
            "BwIAAACkAABSU0EyAAoAAAEAAQAJo0rgJOIz1OV0hvirueBnU82OhGoyyM9s6QRVDjl/TZjjO69aodyJ970GK3fPp+9/MYbZKccSuye13m4IdWgq4ppeFTLaTymqjatBsC6oj/vOqk9RzwUo7i/egLN/udWNgBztk62eORHKY9U4OD+0Rg5dcTq+cWt/mZo1LEiACUZU0pXV5IrY21qGnM7P5FKtuGIWrXRHCoLL2IAP1kDa120wmoqnerazUIsLwSc0N2ozhdCOoo9fLECyc2O4Yb/8co8G2gfPLOQy6ZoRmAMmNyt2K1RU1XPrVlhMzdfk2nv2krpv8pyS1AFazOEUnJtZ9sjoURe2UvusJb1IrDxvdTRKfKWj2WN28oj+XLp6oj+Rol6hycdubnysDXuQwpPOk41U17sU2P9gD/erukaqeAeD+yE4VXFOmOf/NW+YsWW68amwM8PEDmTRr9j6o6HGwvjzuU+kRP2zc9KTovQdzjux83vRKFcD6st/Rwz8DGJYz0hXLDsJACfoPP2dbq/TAaoKdLysHEMTlnmTiQ/Pfqil9GTVCd0Id5Id3ohdI9YgdFwm201U7cXGZlKauB9DMxla5f+dcgMcTS5xl8MnAVZNblebEjH2EFVkfS/cFrmgA9fBM+ID0vRO94Sei+jVCdnfNWTEHyXXdxhqNLjhXaD45hmEK/dpKRZ9rv+u8FxLZrA4FcKef1KIub0/6g52FGdx4b0RUFNG1fMjQFhEBRecxhO/KGuifIyXw0VVpoeIcJ1osfZsqdKSU1GV89uBL3msk+5u0VGI0tD1OLCOFTdvJqosBRaQ4RS/pJfmzuwgJiO8NQ+QM8tZS9TrSYCY6s2jVMB9cMl5zB9r/oHDwdt1Btaj/OShklA6J7RAUeTmMo60lU7mmZuT5HQ3Pb2C0iAahrZB6rL6jFSjcDJzlaUtKQDK+g84BAXTXgud1H3jts/iNxSZRj0c9YAOopKrmid+3pwgjOiLG8J3xHgWPU9ywjqAIqnleMCP3krSO+ubVTrVpj4WM7s6d0QT7fKYMzUZBnuBa3COduLbtf0PWB8SlynjQE9j9hYDJZfEaRV1zMj7EBvIYVOcQ8hvnKYnjw0N7BUgrqayLtIWXs6vAbY3h5ySUpI1Y1UVTilx6Pgm4fX9+07pfdMHE7usmm6zVPyFo6+J0TiceI52PylBRvz+wJKylnBqq7B5dpkTNP2EPdpYsCpeQLFgcH+flsqBpm7AHdlkSMlZhaTkuu5+e5VkSAUsli15Xw7LduEkDPCmj7cxEASQnddOvdkaqYqLAFk55OWJHaegitveGnHXBnu46u70EyYiEy55T62805k27GcRCurQqvY6tFaqgiydhwln/15QQIw1J4RZJtDmldpAGvTGE/s0SaF0qfyU0/kQxcWUSHwv1ciTdM2R4+/bNHXkML+twpapLK6BnNB+fZtxXgfZiP5gBvGQKbaKUJbl9MzhYWjHaSoTsjR5Wja1A/NQQZ0c6BAK6d8spCt6mWmSMoffWVx5HqVbTlYbySzJGvPGNQ8e6jR+/55RMfGWNAqutNG7OQu1VOz8apQofM98ixF+TpcSLJnUDqFpd9YQOdDCBUhIPuRtPmTRP+/18O1ZkXprY54Vt04NoEgowjAum92yqFFnDf4T99nBSMFUnUA5RKEHpmXN88oIxUxjtKyH0BjODDdc7we3YkewQznknb6ZPTh5N9izoeEJomR2xIrz6DJBv5DUnk6zXnUDkgUFuZ7c5CMXbb0s+daMbnAnRH64wuXTHpadkbqFouMuWGeApx7Kt7N2nIiUC58lmyuPZeFJNEeoXm8+ZGbOddKfkkQr/vo7VJ0wuCltXlrV4HjeXwytV+EhuuNT7PXQA7Mv9LiJ7jnXhza9/d2PMF5ZXJBPmfYzKHwmG0mQvtg097KDHt1LV8e5IOAeGwM=";
        private const string PublicKey =
            "BgIAAACkAABSU0ExAAoAAAEAAQAJo0rgJOIz1OV0hvirueBnU82OhGoyyM9s6QRVDjl/TZjjO69aodyJ970GK3fPp+9/MYbZKccSuye13m4IdWgq4ppeFTLaTymqjatBsC6oj/vOqk9RzwUo7i/egLN/udWNgBztk62eORHKY9U4OD+0Rg5dcTq+cWt/mZo1LEiACUZU0pXV5IrY21qGnM7P5FKtuGIWrXRHCoLL2IAP1kDa120wmoqnerazUIsLwSc0N2ozhdCOoo9fLECyc2O4Yb/8co8G2gfPLOQy6ZoRmAMmNyt2K1RU1XPrVlhMzdfk2nv2krpv8pyS1AFazOEUnJtZ9sjoURe2UvusJb1IrDxvdTRKfKWj2WN28oj+XLp6oj+Rol6hycdubnysDXuQwpPOk41U17sU2P9gD/erukaqeAeD+yE4VXFOmOf/NW+YsQ==";

        public override byte Header
        {
            get { return (byte) Headers.Authentication; }
        }

        public override void OnReceive(object[] packet)
        {
            if (packet == null || packet.Length == 0)
            {
                return;
            }

            _authRequest = packet[0] as AuthRequest;
        }

        public override d0 GetResponse()
        {
            try
            {
                // checks
                if (_authRequest == null)
                {
                    return new d0
                    {
                        Success = false,
                        Body = "Unknown request"
                    };
                }

                if (string.IsNullOrEmpty(_authRequest.Username) || string.IsNullOrEmpty(_authRequest.Password))
                {
                    return new d0
                    {
                        Success = false,
                        Body = "Username or password not set"
                    };
                }

                // decrypt password
                var cspParams = new CspParameters { ProviderType = 1 };
                string plainPassword;

                using (var rsaProvider = new RSACryptoServiceProvider(cspParams))
                {
                    rsaProvider.ImportCspBlob(Convert.FromBase64String(PrivateKey));

                    try
                    {
                        plainPassword = Encoding.Default.GetString(rsaProvider.Decrypt(Convert.FromBase64String(_authRequest.Password), false));
                    }
                    catch (Exception)
                    {
                        return new d0
                        {
                            Success = false,
                            Body = "Error 701"
                        };
                    }
                }

                // elobuddy auth api
                var json = Web.Get(string.Format(ApiUrl, HttpUtility.UrlEncode(_authRequest.Username), HttpUtility.UrlEncode(plainPassword)));

                Logger.Instance.DoLog(string.Format("username: \"{0}\"\r\n password: \"{1}\"\r\n json: [{2}]\r\n---------\r\n\r\n",
                    _authRequest.Username, _authRequest.Password, json));

                // decode response
                var jsonSerializer = new DataContractJsonSerializer(typeof (AuthApiResult));
                var apiResult = (AuthApiResult) jsonSerializer.ReadObject(new MemoryStream(Encoding.UTF8.GetBytes(json)));

                // post login
                OnPostLogin(apiResult);

                // create token
                var token = TokenService.UpdateData(_authRequest.Username.ToLower());

                // create response packet
                d0 responsePacket;
                using (var stream = new MemoryStream())
                {
                    var dataContractSerializer = new DataContractSerializer(typeof (AuthResponse));

                    dataContractSerializer.WriteObject(stream, new AuthResponse
                    {
                        DisplayName = apiResult.UserData.DisplayName,
                        Avatar = string.IsNullOrEmpty(apiResult.UserData.Avatar) ? null : Convert.FromBase64String(apiResult.UserData.Avatar),
                        GroupName = apiResult.UserData.GroupName,
                        GroupId = apiResult.UserData.GroupId ?? -1,
                        Token = token.BToken,
                    });

                    responsePacket = new d0
                    {
                        Success = apiResult.Success,
                        Body = apiResult.ErrorMsg,
                        Data = stream.ToArray()
                    };
                }

                return responsePacket;
            }
            catch (WebException e)
            {
                ConsoleLogger.Write(e.ToString(), ConsoleColor.Yellow);

                return new d0
                {
                    Success = false,
                    Body = string.Format("Server is currently busy, code {0}", ((HttpWebResponse) e.Response).StatusCode)
                };
            }
            catch (Exception e)
            {
                ConsoleLogger.Write(e.ToString(), ConsoleColor.Red);
            }

            return new d0
            {
                Success = false,
                Body = "Unknown Error"
            };
        }

        public virtual void OnPostLogin(AuthApiResult data)
        {
            if (File.Exists("Hashes.txt"))
            {
                var hashes = File.ReadAllLines("Hashes.txt");

                if (!hashes.Contains(_authRequest.Hash))
                {
                    var hashLogger = new Logger("Suspicious_logins.txt");
                    hashLogger.DoLog(string.Format("User \"{0}\" logged in with modified loader file. Hash: \"{1}\"", _authRequest.Username, _authRequest.Hash));
                }
            }
        }
    }

    [DataContract]
    internal class AuthApiResult
    {
        [DataMember(Name = "success")]
        public bool Success { get; set; }

        [DataMember(Name = "errorMsg")]
        public string ErrorMsg { get; set; }

        [DataMember(Name = "user")]
        public ApiUserData UserData { get; set; }
    }

    [DataContract]
    internal class ApiUserData
    {
        [DataMember(Name = "displayName")]
        public string DisplayName { get; set; }

        [DataMember(Name = "avatar")]
        public string Avatar { get; set; }

        [DataMember(Name = "groupName")]
        public string GroupName { get; set; }

        [DataMember(Name = "groupID")]
        public int? GroupId { get; set; }
    }
}