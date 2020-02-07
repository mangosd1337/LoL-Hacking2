using System;
using System.Reflection;
using System.Runtime.InteropServices;
using System.Windows;
using EloBuddy.Loader.Utils;
using NLog;

namespace EloBuddy.Loader.Injection
{
    [Obfuscation(Exclude = false, Feature = "ctrl flow")]
    internal class Injector
    {
        private static readonly NLog.Logger NLog = LogManager.GetCurrentClassLogger();

        [UnmanagedFunctionPointer(CallingConvention.Cdecl, CharSet = CharSet.Unicode)]
        private delegate bool InjectDelegate(int processId, string path);

        private static InjectDelegate InjectDLL;
        private static IntPtr UnmanagedModule;

        private const string UnmanagedDllName = "Dependencies\\EloBuddy.Unmanaged.dll";

        internal static void LoadDll()
        {
            try
            {
                // Get handle to Unmanaged DLL
                UnmanagedModule = NativeImports.LoadLibrary(UnmanagedDllName);

                if (UnmanagedModule != IntPtr.Zero)
                {
                    // We have a valid handle
                    // Get address to our unmanaged function
                    var pProcAddress = NativeImports.GetProcAddress(UnmanagedModule, "Inject");

                    if (pProcAddress != IntPtr.Zero)
                    {
                        InjectDLL =
                            Marshal.GetDelegateForFunctionPointer(pProcAddress, typeof (InjectDelegate)) as
                                InjectDelegate;

                        if (InjectDLL == null)
                        {
                            throw new Exception("Failed to get delegate for function pointer");
                        }
                    }
                    else
                    {
                        throw new Exception("Failed to load export.");
                    }
                }
                else
                {
                    throw new Exception(string.Format("Failed to load: {0}, error code: {1}", UnmanagedDllName,
                        Marshal.GetLastWin32Error()));
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show(ex.ToString());
            }
        }

        internal static void FreeDll()
        {
            if (UnmanagedModule != IntPtr.Zero)
            {
                NativeImports.FreeLibrary(UnmanagedModule);
            }
        }

        internal static bool InjectBuddy(int procId, string path)
        {
            LoadDll();

            if (InjectDLL != null)
            {
                var result = InjectDLL(procId, path);
                FreeDll();

                NLog.Info("Injected EloBuddy");

                return result;
            }

            return false;
        }
    }
}
