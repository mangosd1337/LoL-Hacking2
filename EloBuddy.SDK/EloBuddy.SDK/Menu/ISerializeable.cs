using System.Collections.Generic;

namespace EloBuddy.SDK.Menu
{
    public interface ISerializeable
    {
        string SerializationId { get; }
        bool ShouldSerialize { get; }

        Dictionary<string, object> Serialize();
    }
}
