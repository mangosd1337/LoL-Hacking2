namespace EloBuddy.Loader.Globals
{
    internal static class Constants
    {
        internal static readonly string DependenciesJsonUrl = "https://raw.githubusercontent.com/EloBuddy/EloBuddy.Dependencies/master/dependencies.json";
        internal static readonly string CoreJsonUrl = "https://raw.githubusercontent.com/EloBuddy/EloBuddy.Dependencies/master/core.json";

        internal static readonly string UriSchemePrefix = "elobuddy";
        internal static readonly string[] SupportedProjects = { ".csproj", ".vbproj", ".ebaddon" };
        internal static readonly string[] LeagueProcesses = { "League of Legends" };
        internal static readonly string LoaderTempFileName = "Elobuddy.Loader.old";
        internal static readonly string LoaderMainLogFileName = "Elobuddy.Loader.Log.txt";
    }
}
