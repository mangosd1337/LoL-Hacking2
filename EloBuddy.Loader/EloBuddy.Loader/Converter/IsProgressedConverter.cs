using System;
using System.Globalization;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Data;
using EloBuddy.Loader.Controls;

namespace EloBuddy.Loader.Converter
{
    public class IsProgressedConverter : IMultiValueConverter
    {
        public object Convert(object[] values, Type targetType, object parameter, CultureInfo culture)
        {
            if ((values[0] is ContentPresenter &&
                 values[1] is int) == false)
            {
                return Visibility.Collapsed;
            }
         
            var checkNextItem = System.Convert.ToBoolean(parameter.ToString());
            var contentPresenter = values[0] as ContentPresenter;
            var progress = (int) values[1];
            var itemsControl = ItemsControl.ItemsControlFromItemContainer(contentPresenter);
            var index = itemsControl.ItemContainerGenerator.IndexFromContainer(contentPresenter);
         
            if (checkNextItem)
            {
                index++;
            }
       
            var wizardProgressBar = itemsControl.TemplatedParent as WizardProgressBar;
            var percent = (int) (((double) index / wizardProgressBar.Items.Count) * 100);
       
            if (percent < progress)
            {
                return Visibility.Visible;
            }

            return Visibility.Collapsed;
        }

        public object[] ConvertBack(object value, Type[] targetTypes, object parameter, CultureInfo culture)
        {
            throw new NotSupportedException();
        }
    }
}
