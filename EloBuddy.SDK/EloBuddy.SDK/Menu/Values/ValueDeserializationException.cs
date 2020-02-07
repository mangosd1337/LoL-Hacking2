using System;

namespace EloBuddy.SDK.Menu.Values
{
    public class ValueDeserializationException : ArgumentException
    {
        public ValueDeserializationException(string key)
            : base(string.Format("Serialized data does not contain key '{0}'", key))
        {
        }
    }
}
