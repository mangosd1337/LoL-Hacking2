using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using EloBuddy.Loader.Data;
using EloBuddy.Loader.Globals;
using EloBuddy.Loader.Views;

namespace EloBuddy.Loader.Installers
{
    internal static class AddonInstaller
    {
        internal static void InstallAddonsFromRepo(string url, string[] projects = null, bool showUi = true)
        {
            // you wut mate?
            if (projects == null && !showUi) 
            {
                return;
            }

            projects = projects ?? new string[] { };

            // setup ui
            var ui = new RemoteAddonInstallerWindow
            {
                Url = url,
                ProjectsToInstall = projects,
                RepoHolder = new ElobuddyAddon(url, "not_set")
            };

            //check if Ui should be displayed
            if (showUi)
            {
                ui.ShowDialog();
                return;
            }
            
            // install logic without ui
            // fetch the repo folder
            ui.RepoHolder.Update(false, false);

            // install
            PerformInstall(url, ui.GetAddons().Where(p => p.Install).OrderBy(p => Array.IndexOf(projects, p.AddonName)));
        }

        internal static string[] GetProjectsFromRepo(string path)
        {
            return Directory.GetFiles(path, "*", SearchOption.AllDirectories).Where(p => Constants.SupportedProjects.Any(p.EndsWith)).ToArray();
        }

        internal static void PerformInstall(string url, IEnumerable<AddonToInstall> addons)
        {
            foreach (var p in addons)
            {
                p.Status = "Queued";
                p.Success = false;
            }

            foreach (var p in addons)
            {
                p.Status = "Installing...";
                p.Success = Settings.Instance.InstalledAddons.InstallAddon(url, p.AddonFullName);
                p.Status = p.Success ? "Installed" : "Failed";
            }
        }
    }
}