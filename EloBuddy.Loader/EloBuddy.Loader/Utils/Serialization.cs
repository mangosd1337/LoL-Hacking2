using System;
using System.IO;
using System.Reflection;
using System.Runtime.Serialization;
using System.Runtime.Serialization.Formatters.Binary;
using EloBuddy.Loader.Logger;

namespace EloBuddy.Loader.Utils
{
    public class UniversalBinder : SerializationBinder
    {
        public override Type BindToType(string assemblyName, string typeName)
        {
            var currentAssembly = Assembly.GetExecutingAssembly().FullName;
            assemblyName = currentAssembly;
            var typeToDeserialize = Type.GetType(String.Format("{0}, {1}", typeName, assemblyName));
            return typeToDeserialize;
        }
    }

    public class Serialization
    {
        public static byte[] Serialize(object @object)
        {
            if (@object == null)
                return null;

            var memoryStream = new MemoryStream();
            byte[] array = null;

            try
            {
                var binaryFormatter = new BinaryFormatter { Binder = new UniversalBinder() };
                binaryFormatter.Serialize(memoryStream, @object);
                array = memoryStream.ToArray();
            }
            catch (Exception ex)
            {
                Log.Instance.DoLog(string.Format("Serialization failed: {0}", ex), Log.LogType.Error);
            }
            finally
            {
                memoryStream.Dispose();
            }

            return array;
        }

        public static object Deserialize(byte[] array)
        {
            if (array == null || array.Length == 0)
                return null;

            var memoryStream = new MemoryStream(array);
            object _object = null;

            try
            {
                var binaryFormatter = new BinaryFormatter { Binder = new UniversalBinder() };
                memoryStream.Position = 0;
                _object = binaryFormatter.Deserialize(memoryStream);
            }
            catch (Exception ex)
            {
                Log.Instance.DoLog(string.Format("Deserialization failed: {0}", ex), Log.LogType.Error);
            }
            finally
            {
                memoryStream.Dispose();
            }
            return _object;
        }
    }
}
