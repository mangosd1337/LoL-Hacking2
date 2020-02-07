using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Drawing;
using System.IO;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Input;
using EloBuddy.Loader.Data;
using EloBuddy.Loader.Installers;
using Newtonsoft.Json;
// ReSharper disable InconsistentNaming

namespace EloBuddy.Loader.Views
{
    /// <summary>
    /// Interaction logic for FirstTimeWizardWindow.xaml
    /// </summary>
    public partial class FirstTimeWizardWindow : Window, INotifyPropertyChanged
    {
        public event PropertyChangedEventHandler PropertyChanged;

        private Bitmap _previewImage;
        public Bitmap PreviewImage
        {
            get { return _previewImage; }
            set
            {
                _previewImage = value;
                OnPropertyChanged("PreviewImage");
            }
        }

        private readonly List<WizardStep> SelectedSettings;
        private readonly Wizard Wizard;
        private int _wizardGroup;
        private int _wizardStep;

        private WizardStep CurrentStep
        {
            get
            {
                if (_wizardGroup < Wizard.Groups.Length)
                {
                    var group = Wizard.Groups[_wizardGroup];

                    if (_wizardStep < group.Steps.Length)
                    {
                        return group.Steps[_wizardStep];
                    }
                }

                return null;
            }
        }

        private void UpdateDisplay()
        {
            var step = CurrentStep;

            if (step != null)
            {
                TitleLabel.Content = step.Title;
                ContentLabel.Text = step.Description;
                PreviewImage = step.PreviewImage;
                ToggleButton.IsChecked = step.Value;
                ToggleButton.IsEnabled = step.Value.HasValue;
                ToggleButton.Visibility = step.Behavior == WizardStep.WizardStepBehavior.Text ? Visibility.Hidden : Visibility.Visible;
            }
            else
            {
                
            }
        }

        private void NextGroup()
        {
            _wizardGroup++;
            _wizardStep = 0;

            if (_wizardGroup > Wizard.Groups.Length - 1)
            {
                Finish();
            }
        }

        private void NextStep()
        {
            var group = Wizard.Groups[_wizardGroup];
            var step = group.Steps[_wizardStep];

            if (step.Value.HasValue && step.Value.Value)
            {
                SelectedSettings.Add(step);
            }

            _wizardStep++;

            if (_wizardStep > group.Steps.Length - 1)
            {
                NextGroup();
            }
            else
            {
                if (CurrentStep.RequiresBuddy && !Authenticator.IsBuddy)
                {
                    NextStep();
                }
            }

            UpdateDisplay();
        }

        private void Finish()
        {
            Hide();

            var configuration = Settings.Instance.Configuration;
            var properties = configuration.GetType().GetProperties();
            var settings = SelectedSettings.GroupBy(s => s.Behavior);

            foreach (var g in settings)
            {
                switch (g.Key)
                {
                    case WizardStep.WizardStepBehavior.Toggle:
                        foreach (var step in g)
                        {
                            var property = properties.FirstOrDefault(p => p.Name == step.Tag);
                            if (property != null)
                            {
                                property.SetValue(configuration, step.Value);
                            }
                        }
                        break;

                    case WizardStep.WizardStepBehavior.Install:
                        var repositories = g.GroupBy(s => s.Tag.Split(';')[0]);
                        
                        foreach (var repo in repositories)
                        {
                            AddonInstaller.InstallAddonsFromRepo(repo.Key, repo.Select(s => s.Tag.Split(';')[1]).ToArray());
                        }

                        break;

                    case WizardStep.WizardStepBehavior.Text:
                        break;
                }
            }

            Close();
        }

        public FirstTimeWizardWindow()
        {
            InitializeComponent();
            DataContext = this;
            SelectedSettings = new List<WizardStep>();
            Wizard = JsonConvert.DeserializeObject<Wizard>(Encoding.ASCII.GetString(Properties.Resources.FirstTimeWizard));

            UpdateDisplay();
        }

        private void OnPropertyChanged(string propertyName)
        {
            if (PropertyChanged != null)
            {
                PropertyChanged(this, new PropertyChangedEventArgs(propertyName));
            }
        }

        private void Grid_MouseMove(object sender, MouseEventArgs e)
        {
            if (e.LeftButton == MouseButtonState.Pressed)
            {
                DragMove();
            }
        }

        private void CloseButton_Click(object sender, RoutedEventArgs e)
        {
            Close();
        }

        private void NextButton_Click(object sender, RoutedEventArgs e)
        {
            NextStep();
        }

        private void SkipButton_Click(object sender, RoutedEventArgs e)
        {
            NextGroup();
            UpdateDisplay();
        }

        private void ToggleButton_Changed(object sender, RoutedEventArgs e)
        {
            CurrentStep.Value = ToggleButton.IsChecked ?? true;
            PreviewImage = CurrentStep.PreviewImage;
        }
    }

    public class Wizard
    {
        public WizardGroup[] Groups;

        public Wizard()
        {
        }
    }

    public class WizardGroup
    {
        public string Name;
        public WizardStep[] Steps;
    }

    public class WizardStep
    {
        public enum WizardStepBehavior
        {
            None,
            Toggle,
            Install,
            Text,
        }

        public byte[] PreviewImageOn;
        public byte[] PreviewImageOff;
        public string Title;
        public string Description;
        public bool RequiresBuddy;
        public bool? Value;

        public WizardStepBehavior Behavior;
        public string Tag;

        private Bitmap _priviewImageon;
        private Bitmap _priviewImageoff;

        internal Bitmap PreviewImage
        {
            get
            {
                if (_priviewImageon == null && PreviewImageOn != null)
                {
                    _priviewImageon = new Bitmap(new MemoryStream(PreviewImageOn));
                }

                if (_priviewImageoff == null && PreviewImageOff != null)
                {
                    _priviewImageoff = new Bitmap(new MemoryStream(PreviewImageOff));
                }

                if (Value == null)
                {
                    return _priviewImageon ?? _priviewImageoff;
                }

                if ((bool) Value && _priviewImageon != null)
                {
                    return _priviewImageon;
                }

                return _priviewImageoff;
            }
        }

        public WizardStep()
        {
        }
    }
}