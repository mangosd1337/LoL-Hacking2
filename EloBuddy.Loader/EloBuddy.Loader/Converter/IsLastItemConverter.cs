using System;
using System.Globalization;
using System.Windows.Controls;
using System.Windows.Data;

namespace EloBuddy.Loader.Converter
{
    public class IsLastItemConverter : IValueConverter
    {
        public object Convert(object value, Type targetType, object parameter, CultureInfo culture)
        {
            var contentPresenter = value as ContentPresenter;
            var itemsControl = ItemsControl.ItemsControlFromItemContainer(contentPresenter);
            var index = itemsControl.ItemContainerGenerator.IndexFromContainer(contentPresenter);
            
            return (index == (itemsControl.Items.Count - 1));
        }

        public object ConvertBack(object value, Type targetType, object parameter, CultureInfo culture)
        {
            throw new NotSupportedException();
        }
    }
}