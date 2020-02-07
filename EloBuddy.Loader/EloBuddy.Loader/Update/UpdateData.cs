using System.Collections.Generic;

namespace EloBuddy.Loader.Update
{
    public class FileEntry
    {
        public string MD5 { get; set; }
        public string Download { get; set; }
    }

    public class PatchData
    {
        public Dictionary<string, FileEntry> Files { get; set; }
    }

    public class UpdateData
    {
        public Dictionary<string, FileEntry> StaticFiles { get; set; }
        public Dictionary<string, PatchData> Patches { get; set; }
        public FileEntry Loader { get; set; }
    }
}
