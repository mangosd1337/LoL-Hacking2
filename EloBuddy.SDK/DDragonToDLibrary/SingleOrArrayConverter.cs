using System;
using System.Collections.Generic;
using Newtonsoft.Json;
using Newtonsoft.Json.Linq;

namespace DDragonToDLibrary
{
    // credits: http://stackoverflow.com/a/18997172
    public class SingleOrArrayConverter<T> : JsonConverter
    {
        public override bool CanConvert(Type objectType)
        {
            return (objectType == typeof(List<T>));
        }

        public override object ReadJson(JsonReader reader, Type objectType, object existingValue, JsonSerializer serializer)
        {
            var token = JToken.Load(reader);
            return token.Type == JTokenType.Array ? token.ToObject<List<T>>() : new List<T> { token.ToObject<T>() };
        }

        public override bool CanWrite
        {
            get { return true; }
        }

        public override void WriteJson(JsonWriter writer, object value, JsonSerializer serializer)
        {
            var list = (List<T>) value;
            if (list.Count == 1)
            {
                value = list[0];
            }
            serializer.Serialize(writer, value);
        }
    }
}
