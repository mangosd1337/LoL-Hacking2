using System;
using System.Collections.Generic;
using System.Globalization;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Data;
using EloBuddy.Loader.Controls;
using EloBuddy.Loader.Logger;

namespace EloBuddy.Loader.Converter
{
    public class WizardProgressbarProgressConverter : IMultiValueConverter
    {
        public object Convert(object[] values, Type targetType, object parameter, CultureInfo culture)
        {
            if (!(values[0] is ContentPresenter && values[1] is int))
            {
                return 0d;
            }

            var leftBar = System.Convert.ToBoolean(parameter);
            var contentPresenter = (ContentPresenter) values[0];
            var progress = (int) values[1];
            var itemsControl = ItemsControl.ItemsControlFromItemContainer(contentPresenter);
            var index = itemsControl.ItemContainerGenerator.IndexFromContainer(contentPresenter) - 1;

            if (leftBar)
            {
                index += 1;
            }

            var wizardProgressBar = itemsControl.TemplatedParent as WizardProgressBar;
            var percent = (((double) index / wizardProgressBar.Items.Count) * 100);
            var itemPercent = 100 / wizardProgressBar.Items.Count;
            var maxBarWidth = wizardProgressBar.Width / wizardProgressBar.Items.Count;

            if (percent < 0)
            {
                percent = 0;
            }

            if (progress < percent)
            {
                return 0d;
            }

            var barWidth = (progress - percent) / itemPercent * maxBarWidth;
            return barWidth / maxBarWidth >= (leftBar ? 0d : 0.5d) ? (leftBar ? barWidth : barWidth / 2) : 0d;
        }

        public object[] ConvertBack(object value, Type[] targetTypes, object parameter, CultureInfo culture)
        {
            throw new NotSupportedException();
        }
    }
}
