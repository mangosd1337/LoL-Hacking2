using System;
using System.Globalization;
using System.Linq;
using System.Text.RegularExpressions;
using System.Windows.Controls;
using System.Windows.Data;
using System.Windows.Markup;

namespace EloBuddy.Loader.Converter
{
    public class XamlToTextBlockConverter : IValueConverter
    {
        public object Convert(object value, Type targetType, object parameter, CultureInfo culture)
        {
            var xaml = value as string;
            if (xaml == null)
            {
                return Binding.DoNothing;
            }

            var matches = Regex.Matches(xaml,
                @"\b((https?|ftp|file)://|(www|ftp)\.)[-A-Z0-9+&@#/%?=~_|$!:,.;]*[A-Z0-9+&@#/%=~_|$]",
                RegexOptions.IgnoreCase);
            xaml = matches.Cast<Match>()
                .Aggregate(xaml,
                    (current, match) =>
                        current.Replace(match.Value,
                            string.Format("<Hyperlink Style=\"{0}\" NavigateUri=\"{1}\">{1}</Hyperlink>",
                                "{StaticResource HyperlinkLaunch}", match.Value)));

            const string textBlockFormat =
                @"<TextBlock xmlns=""http://schemas.microsoft.com/winfx/2006/xaml/presentation"" FontFamily=""Arial"" FontSize=""14"" Foreground=""Gray"" TextWrapping=""Wrap"" Margin=""15"">{0}</TextBlock>";
            var fullXaml = string.Format(textBlockFormat, xaml);
            return (TextBlock) XamlReader.Parse(fullXaml);
        }

        public object ConvertBack(object value, Type targetType, object parameter, CultureInfo culture)
        {
            throw new NotImplementedException();
        }
    }
}
