using System;
using System.IO;
using System.Reflection;
using System.Runtime.Serialization;
using System.Runtime.Serialization.Formatters.Binary;
using System.Windows;
using EloBuddy.Auth.Services;

namespace Elobuddy.Telemetry.Utils
{
    public class UniversalBinder : SerializationBinder
    {
        public override Type BindToType(string assemblyName, string typeName)
        {
            var currentAssembly = Assembly.GetExecutingAssembly().FullName;
            assemblyName = currentAssembly;
            var typeToDeserialize = Type.GetType(String.Format("{0}, {1}", typeName, assemblyName));

            if (typeToDeserialize == null && typeName.Contains("TelemetryService"))
            {
                return typeof(TelemetryService);
            }

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
                MessageBox.Show(string.Format("Serialization failed: {0}", ex), "", MessageBoxButton.OK, MessageBoxImage.Error);
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
                MessageBox.Show(string.Format("Deserialization failed: {0}", ex), "", MessageBoxButton.OK, MessageBoxImage.Error);
            }
            finally
            {
                memoryStream.Dispose();
            }
            return _object;
        }
    }
}
