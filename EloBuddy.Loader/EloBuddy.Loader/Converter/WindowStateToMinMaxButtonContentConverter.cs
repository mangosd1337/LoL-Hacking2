using System;
using System.Globalization;
using System.Windows;
using System.Windows.Data;

namespace EloBuddy.Loader.Converter
{
    internal class WindowStateToMinMaxButtonContentConverter : IValueConverter
    {
        public object Convert(object value, Type targetType, object parameter, CultureInfo culture)
        {
            if (!(value is WindowState))
                throw new ArgumentException("value");

            return ((WindowState) value) == WindowState.Maximized ? "\uf066" : "\uf065";
        }

        public object ConvertBack(object value, Type targetType, object parameter, CultureInfo culture)
        {
            throw new NotImplementedException();
        }
    }
}
