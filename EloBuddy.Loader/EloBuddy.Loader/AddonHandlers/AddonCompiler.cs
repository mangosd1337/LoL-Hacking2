using System.Collections.Generic;
using System.IO;
using EloBuddy.Loader.Data;

namespace EloBuddy.Loader.AddonHandlers
{
    internal abstract class AddonHandler
    {
        internal abstract void Compile(ElobuddyAddon addon);
    }

    internal static class AddonCompiler
    {
        internal static readonly Dictionary<string, AddonHandler> Handlers;

        static AddonCompiler()
        {
            Handlers = new Dictionary<string, AddonHandler>();
            Handlers[".csproj"] = new ProjectAddonHandler();
            Handlers[".vbproj"] = new ProjectAddonHandler();
            Handlers[".ebaddon"] = new SignedAddonHandler();
        }

        internal static void Compile(ElobuddyAddon addon)
        {
            var format = Path.GetExtension(addon.ProjectFilePath);

            AddonHandler handler;
            if (Handlers.TryGetValue(format, out handler))
            {
                handler.Compile(addon);
            }
        }
    }
}