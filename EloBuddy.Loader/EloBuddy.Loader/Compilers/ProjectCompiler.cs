using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using EloBuddy.Loader.Data;
using EloBuddy.Loader.Logger;
using Microsoft.Build.Evaluation;
using Microsoft.Build.Logging;
using Microsoft.Win32;

namespace EloBuddy.Loader.Compilers
{
    internal static class ProjectCompiler
    {
        internal static string ReferencesDirectory { get; set; }
        internal static string OutputPath { get; set; }
        internal static string Configuration { get; set; }
        internal static string PlatformTarget { get; set; }

        private static string _msBuildTools14Path;
        internal static string MsBuildTools14Path
        {
            get
            {
                if (string.IsNullOrEmpty(_msBuildTools14Path))
                {
                    _msBuildTools14Path = "not found";
                    var key = Registry.LocalMachine.OpenSubKey(@"SOFTWARE\Microsoft\MSBuild\ToolsVersions\14.0");

                    if (key != null)
                    {
                        var path = (string)key.GetValue("MSBuildToolsPath");

                        if (!string.IsNullOrEmpty(path))
                        {
                            _msBuildTools14Path = path;
                        }

                        key.Close();
                    }
                }

                return Directory.Exists(_msBuildTools14Path) ? _msBuildTools14Path : string.Empty;
            }
        }

        static ProjectCompiler()
        {
            PlatformTarget = "x86";
            ReferencesDirectory = Settings.Instance.Directories.LibrariesDirectory;
        }

        internal static CompileResult Compile(string projectFile, string logFile)
        {
            return Compile(new Project(projectFile), logFile);
        }
        private static readonly List<string> CrackedAddonsList = new List<string> { "EvadeIC", "BeastyRiven", "Beasty_Riven", "MasterTheEnemy" };

        internal static CompileResult Compile(Project project, string logFile)
        {
            ProjectCollection.GlobalProjectCollection.UnregisterAllLoggers();
            ProjectCollection.GlobalProjectCollection.UnloadAllProjects();

            Configuration = Settings.Instance.DeveloperMode ? "Debug" : "Release";
            OutputPath = "bin\\" + Configuration;

            project.SetProperty("Configuration", Configuration);
            project.SetProperty("PlatformTarget", PlatformTarget);
            project.SetProperty("OutputPath", OutputPath);

            //C# 6.0 support
            SetLatestBuildTools(project);

            foreach (var item in project.GetItems("Reference"))
            {
                if (item == null)
                {
                    continue;
                }

                var path = item.GetMetadata("HintPath");

                if (path != null && !string.IsNullOrWhiteSpace(path.EvaluatedValue))
                {
                    var fileName = Path.GetFileName(path.EvaluatedValue);

                    if (fileName.EndsWith(".dll"))
                    {
                        if (CrackedAddonsList.Any(name => fileName.IndexOf(name, StringComparison.CurrentCultureIgnoreCase) > -1))
                        {
                            return Compile(project, logFile);
                        }
                        var files = Directory.GetFiles(ReferencesDirectory, "*", SearchOption.AllDirectories);
                        var refPath =
                            Path.GetDirectoryName(
                                files.FirstOrDefault(
                                    f => string.Equals(Path.GetFileName(f), fileName, StringComparison.CurrentCultureIgnoreCase)) ??
                                ReferencesDirectory);

                        item.SetMetadataValue("HintPath", Path.Combine(refPath, fileName));
                    }
                }
            }

            var targetFramework = project.GetProperty("TargetFrameworkVersion").EvaluatedValue;
            switch (targetFramework)
            {
                case "v4.5.1":
                    project.SetProperty("TargetFrameworkVersion", "v4.5");
                    break;
                case "v4.5.2":
                    project.SetProperty("TargetFrameworkVersion", "v4.5");
                    break;
                case "v4.6":
                    break;
            }

            project.SetGlobalProperty("PreBuildEvent", string.Empty);
            project.SetGlobalProperty("PostBuildEvent", string.Empty);
            project.SetGlobalProperty("PreLinkEvent", string.Empty);

            var fileLogger = new FileLogger { Parameters = @"logfile=" + logFile, ShowSummary = true };
            ProjectCollection.GlobalProjectCollection.RegisterLogger(fileLogger);
            ProjectCollection.GlobalProjectCollection.LoadedProjects.Add(project);

            var result = project.Build();

            ProjectCollection.GlobalProjectCollection.UnregisterAllLoggers();
            var compileResult = new CompileResult(project, result, logFile);
            ProjectCollection.GlobalProjectCollection.UnloadAllProjects();

            return compileResult;
        }

        internal static void SetLatestBuildTools(Project project)
        {
            if (!string.IsNullOrEmpty(MsBuildTools14Path))
            {
                project.SetProperty("CscToolPath", MsBuildTools14Path);
                return;
            }

            Log.Instance.DoLog("Failed to locate MSBuild 14.0, C# 6.0 is not supported in this machine. " +
                               "Please follow the guide here: https://www.elobuddy.net/topic/21274-installing-ms-build-tools-140/");
        }

        internal class CompileResult
        {
            internal Project Project { get; private set; }
            internal bool BuildSuccessful { get; private set; }
            internal byte[] OutputFile { get; private set; }
            internal byte[] LogFile { get; private set; }

            internal byte[] PdbFile { get; private set; }
            internal string PdbFileName { get; private set; }

            internal AddonType Type { get; private set; }

            internal CompileResult(Project project, bool buildSuccessful, string logFile)
            {
                Project = project;
                BuildSuccessful = buildSuccessful;
                LogFile = File.ReadAllBytes(logFile);
                Type = Project.GetPropertyValue("OutputType").ToLower() == "library" ? AddonType.Library : AddonType.Executable;

                if (BuildSuccessful)
                {
                    var filePath = GetOutputFilePath();
                    OutputFile = File.ReadAllBytes(filePath);

                    var pdbPath = Directory.GetFiles(Path.GetDirectoryName(filePath), "*.pdb", SearchOption.TopDirectoryOnly).FirstOrDefault();

                    if (File.Exists(pdbPath))
                    {
                        PdbFile = File.ReadAllBytes(pdbPath);
                        PdbFileName = Path.GetFileName(pdbPath);
                    }
                }
            }

            internal CompileResult(byte[] assembly)
            {
                OutputFile = assembly;
            }

            internal string GetOutputFilePath()
            {
                var project = Project;
                var extension = project.GetPropertyValue("OutputType").ToLower().Contains("exe")
                    ? ".exe"
                    : (project.GetPropertyValue("OutputType").ToLower() == "library" ? ".dll" : string.Empty);
                var pathDir = Path.GetDirectoryName(project.FullPath);
                if (!string.IsNullOrWhiteSpace(extension) && !string.IsNullOrWhiteSpace(pathDir))
                {
                    return Path.Combine(
                        pathDir, project.GetPropertyValue("OutputPath"),
                        (project.GetPropertyValue("AssemblyName") + extension));
                }

                return string.Empty;
            }
        }
    }
}
