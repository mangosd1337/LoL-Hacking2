using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Data;
using System.Windows.Documents;
using System.Windows.Input;
using System.Windows.Media;
using System.Windows.Media.Imaging;
using System.Windows.Navigation;
using System.Windows.Shapes;
using Elobuddy.Telemetry.Networking;
using Elobuddy.Telemetry.Utils;
using EloBuddy.Auth.Services;
using EloBuddy.Networking;
using EloBuddy.Networking.Objects;

namespace Elobuddy.Telemetry
{
    /// <summary>
    /// Interaction logic for MainWindow.xaml
    /// </summary>
    public partial class MainWindow : Window
    {
        public static TelemetryService.TelemetryData Telemetry { get; private set; }

        public MainWindow()
        {
            InitializeComponent();
        }

        private void Button_Click(object sender, RoutedEventArgs e)
        {
            try
            {
                var client = new EbClient();
                var response = client.Do((byte) Headers.Reserved2, new object[]
                {
                    new StatisticsRequest()
                    {
                        Username = UsernameTextBox.Text,
                        Password = PasswordTextBox.Password,
                        Data = new object[] { 0, 55, 10, 90 }
                    }
                });

                if (response.Success)
                {
                    Telemetry = (TelemetryService.TelemetryData) Serialization.Deserialize(response.Data);
                }

                MessageBox.Show(response.Success ? "Got data!" : "Server denied the request", "", MessageBoxButton.OK, MessageBoxImage.Information);
            }
            catch (Exception ex)
            {
                MessageBox.Show(ex.ToString(), "Error", MessageBoxButton.OK, MessageBoxImage.Error);
            }
        }

        private void ComboBox_Loaded(object sender, RoutedEventArgs e)
        {
            var data = new List<string> { "top addons", "top developers", "general statistics" };

            var comboBox = sender as ComboBox;
            comboBox.ItemsSource = data;
            comboBox.SelectedIndex = 0;
        }

        private void ComboBox_SelectionChanged(object sender, SelectionChangedEventArgs e)
        {
            if (Telemetry == null)
            {
                return;
            }

            var comboBox = sender as ComboBox;
            var index = comboBox.SelectedIndex;

            switch (index)
            {
                case 0:
                    var total = Telemetry.Data.SelectMany(t => t.Item2.Assemblies);
                    var size = total.Count();
                    var groups = total.GroupBy(a => a.Name.Split(new[] {"_"}, StringSplitOptions.None)[0]).OrderByDescending(g => g.Count()).ToArray();
                    var sb = new StringBuilder();

                    sb.AppendLine("-------------------------");
                    sb.AppendLine();

                    foreach (var g in groups)
                    {
                        sb.AppendLine(string.Format("{0}% {1}/{2} addon: {3} dev: {4}",  ((float) g.Count() / size) * 100, g.Count(), size, g.Key, g.FirstOrDefault().Author));
                    }

                    sb.AppendLine();
                    sb.AppendLine("-------------------------");




                    TextBlock1.Text = sb.ToString();

                    break;
                case 1:

                    break;
                case 2:

                    break;
            }
        }
    }
}
