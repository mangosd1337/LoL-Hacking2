using System;
using System.Diagnostics;
using System.IO;
using EloBuddy.Loader.Compilers;
using EloBuddy.Loader.Data;
using EloBuddy.Loader.Logger;
using EloBuddy.Loader.Utils;

namespace EloBuddy.Loader.AddonHandlers
{
    internal class ProjectAddonHandler : AddonHandler
    {
        internal override void Compile(ElobuddyAddon addon)
        {
            var logFileName = string.Format("compile_log_{0}.txt", addon.GetUniqueName());
            var logFile = Path.Combine(Settings.Instance.Directories.TempDirectory, logFileName);
            var compileResult  = ProjectCompiler.Compile(addon.ProjectFilePath, logFile);

            File.Delete(logFile);

            if (!compileResult.BuildSuccessful)
            {
                addon.SetState(AddonState.CompilingError);

                var logFileSavePath = Path.Combine(Settings.Instance.Directories.LogsDirectory, logFileName);
                File.WriteAllBytes(logFileSavePath, compileResult.LogFile);
                Log.Instance.DoLog(string.Format("Failed to compile project: \"{0}\". Build log file saved to \"{1}\".", addon.ProjectFilePath, logFileSavePath), Log.LogType.Error);
            }
            else
            {
                addon.SetState(AddonState.Ready);
                addon.Type = compileResult.Type;

                if (!addon.IsLocal)
                {
                    var split = addon.Url.Split('/');
                    addon.Author = split.Length > 3 ? split[3] : "";
                }

                var exePath = addon.GetOutputFilePath();
                var pdpPath = Path.Combine(Path.GetDirectoryName(exePath), compileResult.PdbFileName ?? "");

                FileHelper.SafeWriteAllBytes(exePath, compileResult.OutputFile);

                if (Settings.Instance.DeveloperMode && compileResult.PdbFile != null)
                {
                    FileHelper.SafeWriteAllBytes(pdpPath, compileResult.PdbFile);
                }
                else if (File.Exists(pdpPath))
                {
                    try
                    {
                        File.Delete(pdpPath);
                    }
                    catch
                    {
                        // ignored
                    }
                }

                addon.Version = FileVersionInfo.GetVersionInfo(exePath).FileVersion;

                Log.Instance.DoLog(string.Format("Successfully compiled project: \"{0}\".", addon.ProjectFilePath));
            }
        }
    }
}
