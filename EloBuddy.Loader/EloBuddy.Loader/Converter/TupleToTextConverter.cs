using System;
using System.Collections.Generic;
using System.Globalization;
using System.Linq;
using System.Windows.Data;
using EloBuddy.Loader.Logger;

namespace EloBuddy.Loader.Converter
{
    internal class TupleToTextConverter : IValueConverter
    {
        public object Convert(object value, Type targetType, object parameter, CultureInfo culture)
        {
            var tuple = value as List<Tuple<Log.LogType, string>>;
            if (tuple == null)
            {
                return string.Empty;
            }

            var resultString = tuple.Aggregate(string.Empty, (current, s) => current + s.Item2);
            return resultString.Replace("\r", string.Empty);
        }

        public object ConvertBack(object value, Type targetType, object parameter, CultureInfo culture)
        {
            throw new NotImplementedException();
        }
    }
}
