using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Net;
using System.Reflection;
using System.Security;
using System.Security.Permissions;
using System.Security.Policy;
using System.Text.RegularExpressions;
using EloBuddy.Sandbox.ElobuddyAddon;

namespace EloBuddy.Sandbox
{
    internal class SandboxDomain : MarshalByRefObject
    {
        internal static readonly Dictionary<string, Assembly> LoadedLibraries = new Dictionary<string, Assembly>();
        internal static readonly List<string> LoadedAddons = new List<string>();

        static SandboxDomain()
        {
            // Listen to requried events
            AppDomain.CurrentDomain.AssemblyResolve += DomainOnAssemblyResolve;
        }

        [PermissionSet(SecurityAction.Assert, Unrestricted = true)]
        internal static SandboxDomain CreateDomain(string domainName)
        {
            SandboxDomain domain = null;

            try
            {
                if (string.IsNullOrEmpty(domainName))
                {
                    domainName = "Sandbox" + Guid.NewGuid().ToString("N") + "Domain";
                }

                // Initialize app AppDomainSetup
                var appDomainSetup = new AppDomainSetup
                {
                    ApplicationName = domainName,
                    ApplicationBase = Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location) + "\\"
                };

                // Initialize all permissions
                var permissionSet = new PermissionSet(PermissionState.None);
                permissionSet.AddPermission(new EnvironmentPermission(EnvironmentPermissionAccess.Read, "USERNAME"));
                permissionSet.AddPermission(new FileIOPermission(FileIOPermissionAccess.AllAccess, Assembly.GetExecutingAssembly().Location));
                permissionSet.AddPermission(new FileIOPermission(FileIOPermissionAccess.AllAccess, SandboxConfig.DataDirectory));
                permissionSet.AddPermission(new FileIOPermission(FileIOPermissionAccess.PathDiscovery, Path.GetFullPath(Path.Combine(Directory.GetCurrentDirectory(), "..\\..\\..\\..\\..\\..\\"))));
                permissionSet.AddPermission(new FileIOPermission(FileIOPermissionAccess.Read, Path.GetFullPath(Path.Combine(Directory.GetCurrentDirectory(), "..\\..\\..\\..\\..\\..\\"))));
                permissionSet.AddPermission(new ReflectionPermission(PermissionState.Unrestricted));
                permissionSet.AddPermission(new SecurityPermission(SecurityPermissionFlag.Execution));
                permissionSet.AddPermission(new SecurityPermission(SecurityPermissionFlag.Infrastructure));
                permissionSet.AddPermission(new SecurityPermission(SecurityPermissionFlag.RemotingConfiguration));
                permissionSet.AddPermission(new SecurityPermission(SecurityPermissionFlag.SerializationFormatter));
                permissionSet.AddPermission(new SecurityPermission(SecurityPermissionFlag.UnmanagedCode));
                permissionSet.AddPermission(new UIPermission(PermissionState.Unrestricted));
                permissionSet.AddPermission(new WebPermission(NetworkAccess.Connect, new Regex("https?:\\/\\/(\\w+)\\.lolnexus\\.com\\/.*")));
                permissionSet.AddPermission(new WebPermission(NetworkAccess.Connect, new Regex("https?:\\/\\/(\\w+)\\.riotgames\\.com\\/.*")));
                permissionSet.AddPermission(new WebPermission(NetworkAccess.Connect, new Regex("https?:\\/\\/(www\\.)?champion\\.gg\\/.*")));
                permissionSet.AddPermission(new WebPermission(NetworkAccess.Connect, new Regex("https?:\\/\\/(www\\.)?elobuddy\\.net\\/.*")));
                permissionSet.AddPermission(new WebPermission(NetworkAccess.Connect, new Regex("https?:\\/\\/edge\\.elobuddy\\.net\\/.*")));
                permissionSet.AddPermission(new WebPermission(NetworkAccess.Connect, new Regex("https?:\\/\\/(www\\.)?leaguecraft\\.com\\/.*")));
                permissionSet.AddPermission(new WebPermission(NetworkAccess.Connect, new Regex("https?:\\/\\/(www\\.)?lolbuilder\\.net\\/.*")));
                permissionSet.AddPermission(new WebPermission(NetworkAccess.Connect, new Regex("https?:\\/\\/(www\\.|raw.)?github(usercontent)?\\.com\\/.*")));
                permissionSet.AddPermission(new WebPermission(NetworkAccess.Connect, new Regex("https?:\\/\\/(www|oce|las|ru|br|lan|tr|euw|na|eune|sk2)\\.op\\.gg\\/.*")));
                permissionSet.AddPermission(new WebPermission(NetworkAccess.Connect, new Regex("https?:\\/\\/ddragon\\.leagueoflegends\\.com\\/.*")));
                permissionSet.AddPermission(new WebPermission(NetworkAccess.Connect, new Regex("http?:\\/\\/strefainformatyka\\.hekko24\\.pl\\/.*")));
                permissionSet.AddPermission(new WebPermission(NetworkAccess.Connect, new Regex("https?:\\/\\/strefainformatyka\\.hekko24\\.pl\\/.*")));

                // Load extra permissions if existing
                if (SandboxConfig.Permissions != null)
                {
                    foreach (IPermission permission in SandboxConfig.Permissions)
                    {
                        // disabled due to security concerns
                        //permissionSet.SetPermission(permission);
                    }
                }

#if DEBUG
    // TODO: Remove once protected domain works
                var appDomain = AppDomain.CreateDomain(domainName);
#else
                // Create the AppDomain
                var appDomain = AppDomain.CreateDomain(domainName, null, appDomainSetup, permissionSet,
                    PublicKeys.AllKeys.Concat(new[] { Assembly.GetExecutingAssembly().Evidence.GetHostEvidence<StrongName>() }).ToArray());
#endif

                // Create a new Domain instance
                domain = (SandboxDomain) Activator.CreateInstanceFrom(appDomain, Assembly.GetExecutingAssembly().Location, typeof (SandboxDomain).FullName).Unwrap();
                if (domain != null)
                {
                    domain.DomainHandle = appDomain;
                    domain.Initialize();
                }
            }
            catch (Exception e)
            {
                Logs.Log("Sandbox: An exception occurred creating the AppDomain!");
                Logs.Log(e.ToString());
            }

            return domain;
        }

        [PermissionSet(SecurityAction.Assert, Unrestricted = true)]
        internal static bool FindAddon(AssemblyName assemblyName, out string resolvedPath)
        {
            resolvedPath = "";

            foreach (var candidate in new[] { SandboxConfig.LibrariesDirectory, Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location) }
                .Where(directory => directory != null && Directory.Exists(directory)).SelectMany(Directory.EnumerateFiles))
            {
                try
                {
                    if (AssemblyName.GetAssemblyName(candidate).Name.Equals(assemblyName.Name))
                    {
                        resolvedPath = candidate;
                        return true;
                    }
                }
                catch (Exception)
                {
                    // ignored
                }
            }

            Logs.Log("Sandbox: Could not find addon '{0}'", assemblyName.Name);
            return false;
        }

        internal static bool IsSystemAssembly(string path)
        {
            return path.EndsWith(".dll") || Path.GetDirectoryName(path).EndsWith("Libraries");
        }

        internal static Assembly AddonLoadFrom(string path)
        {
            if (IsSystemAssembly(path))
            {
                return Assembly.LoadFrom(path);
            }

            var buffer = File.ReadAllBytes(path);

            if (!buffer.IsDosExecutable())
            {
                try
                {
                    buffer = SignedAddon.VerifyAndDecrypt(buffer);
                }
                catch (Exception e)
                {
                    Logs.Log("Sandbox: Unexpected exception when loading signed addon: {0}, Exception:", path);
                    Logs.Log(e.ToString());
                }
            }

            return buffer == null ? null : Assembly.Load(buffer);
        }

        [PermissionSet(SecurityAction.Assert, Unrestricted = true)]
        // ReSharper disable once InconsistentNaming
        private static void InitSDKBootstrap(Assembly sdk)
        {
            // Call bootstrap
            sdk.GetType("EloBuddy.SDK.Bootstrap")
                .GetMethod("Init", BindingFlags.Public | BindingFlags.Static)
                .Invoke(null, new object[] { null });
        }

        [PermissionSet(SecurityAction.Assert, Unrestricted = true)]
        internal static void UnloadDomain(SandboxDomain domain)
        {
            AppDomain.Unload(domain.DomainHandle);
        }

        [PermissionSet(SecurityAction.Assert, Unrestricted = true)]
        internal static Assembly DomainOnAssemblyResolve(object sender, ResolveEventArgs args)
        {
#if DEBUG
            Logs.Log("Sandbox: Resolving '{0}'", args.Name);
#endif
            Assembly resolvedAssembly = null;

            try
            {
                // Don't handle resources
                if (args.Name.Contains(".resources"))
                {
                    return null;
                }

                // Get AssemblyName and special token
                var assemblyName = new AssemblyName(args.Name);
                var assemblyToken = assemblyName.GenerateToken();

                if (Assembly.GetExecutingAssembly().FullName.Equals(args.Name))
                {
                    // Executing assembly
                    resolvedAssembly = Assembly.GetExecutingAssembly();
                }
                else if (Sandbox.EqualsPublicToken(assemblyName, "7339047cb10f6e86"))
                {
                    // EloBuddy.dll
                    resolvedAssembly = Assembly.LoadFrom(SandboxConfig.EloBuddyDllPath);
                }
                else
                {
                    string resolvedPath;
                    if (FindAddon(assemblyName, out resolvedPath))
                    {
#if DEBUG
                        Logs.Log("Sandbox: Successfully resolved '{0}'", assemblyName.Name);
#endif
                        if (LoadedLibraries.ContainsKey(assemblyToken))
                        {
                            resolvedAssembly = LoadedLibraries[assemblyToken];
                        }
                        else
                        {
#if DEBUG
                            Logs.Log("Sandbox: Creating new instance '{0}'", assemblyName.Name);
#endif
                            // Load the addon into the app domain
                            resolvedAssembly = Assembly.LoadFrom(resolvedPath); //AddonLoadFrom(resolvedPath);

                            // Add the addon to the loaded addons dictionary
                            LoadedLibraries.Add(assemblyToken, resolvedAssembly);

                            if (resolvedAssembly.IsFullyTrusted)
                            {
                                // Check if the DLL is the SDK
                                if (Sandbox.EqualsPublicToken(assemblyName, "6b574a82b1ea937e"))
                                {
                                    // Call bootstrap
                                    InitSDKBootstrap(resolvedAssembly);
                                }
                            }
                        }
                    }
                }
            }
            catch (Exception e)
            {
                Logs.Log("Sandbox: Failed to resolve addon:");
                Logs.Log(e.ToString());
            }

            if (resolvedAssembly != null && resolvedAssembly.IsFullyTrusted)
            {
#if DEBUG
                Logs.Log("Sandbox: Resolved assembly '{0}' is fully trusted!", resolvedAssembly.GetName().Name);
#endif
            }

            return resolvedAssembly;
        }

        [PermissionSet(SecurityAction.Assert, Unrestricted = true)]
        private static void OnUnhandledException(object sender, UnhandledExceptionEventArgs unhandledExceptionEventArgs)
        {
            Logs.Log("Sandbox: Unhandled addon exception:");
#if DEBUG
            var securityException = unhandledExceptionEventArgs.ExceptionObject as SecurityException;
            if (securityException != null)
            {
                Logs.Log(unhandledExceptionEventArgs.ExceptionObject.ToString());
            }
#endif
            Logs.PrintException(unhandledExceptionEventArgs.ExceptionObject);
        }

        // ==========================================================================================================
        // SandboxDomain Instance
        // ==========================================================================================================

        internal static SandboxDomain Instance { get; set; }

        internal AppDomain DomainHandle { get; private set; }

        internal void Initialize()
        {
            // Listen to unhandled exceptions
            DomainHandle.UnhandledException += OnUnhandledException;
        }

        internal bool LoadAddon(string path, string[] args)
        {
            AssemblyName assemblyName = null;
            try
            {
                if (File.Exists(path))
                {
                    // Get the AssemblyName of the addon by the path
                    assemblyName = AssemblyName.GetAssemblyName(path);

                    // Try to execute the addon
                    DomainHandle.ExecuteAssemblyByName(assemblyName, args);
                    if (!LoadedAddons.Contains(assemblyName.Name))
                    {
                        LoadedAddons.Add(assemblyName.Name);
                    }
                    return true;
                }
            }
            catch (MissingMethodException)
            {
                // The addon is a dll
                if (assemblyName != null && !LoadedLibraries.ContainsKey(assemblyName.GenerateToken()))
                {
                    try
                    {
                        // Load the DLL
                        var assembly = DomainHandle.Load(assemblyName);

                        // Store the DLL into loaded addons
                        LoadedLibraries[assemblyName.GenerateToken()] = assembly;

                        if (assembly.IsFullyTrusted)
                        {
                            // Verify that the DLL is the SDK
                            if (Sandbox.EqualsPublicToken(assemblyName, "6b574a82b1ea937e"))
                            {
                                // Call bootstrap
                                InitSDKBootstrap(assembly);
                            }
                        }
                        return true;
                    }
                    catch (Exception e)
                    {
                        Logs.Log("Sandbox: Failed to call Bootstrap for EloBuddy.SDK");
                        Logs.Log(e.ToString());
                    }
                }
            }
            catch (Exception e)
            {
                Logs.Log("Sandbox: Failed to load addon");
                Logs.Log(e.ToString());
            }

            return false;
        }

        [PermissionSet(SecurityAction.Assert, Unrestricted = true)]
        public override object InitializeLifetimeService()
        {
            return null;
        }
    }
}
