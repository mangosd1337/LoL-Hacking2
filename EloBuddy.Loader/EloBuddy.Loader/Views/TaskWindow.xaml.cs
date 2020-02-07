using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Threading;
using System.Windows;
using System.Windows.Input;
using System.Windows.Shell;

namespace EloBuddy.Loader.Views
{
    /// <summary>
    ///     Interaction logic for TaskWindow.xaml
    /// </summary>
    public partial class TaskWindow
    {
        public delegate bool TaskWindowDelegate(TaskWindow ui, Dictionary<string, object> args);

        private bool _isClosing;
        private string _status;
        private Thread _t;

        public TaskWindow()
        {
            InitializeComponent();

            AllowMove = false;
            AllowExit = false;
            Success = false;
            CustomStatusString = false;
            CurrentRoutineIndex = 0;
            Status = "";
        }

        public bool AllowMove { get; set; }
        public bool AllowExit { get; set; }
        public bool CustomStatusString { get; set; }
        public TaskWindowDelegate[] Routines { get; private set; }
        public int CurrentRoutineIndex { get; private set; }
        public Dictionary<string, object> Args { get; private set; }
        public bool Success { get; private set; }

        public string Status
        {
            get { return _status; }

            set
            {
                _status = value;
                StatusLabel.Dispatcher.Invoke(() => { StatusLabel.Content = StatusString; });
            }
        }

        public string StatusString
        {
            get
            {
                return CustomStatusString
                    ? Status
                    : string.Format("Step {0} of {1}: {2}", CurrentRoutineIndex + 1,
                        Routines == null ? -1 : Routines.Length, Status);
            }
        }

        public string WindowTitle
        {
            get { return (string) TitleLabel.Content; }

            set { TitleLabel.Dispatcher.Invoke(() => { TitleLabel.Content = value; }); }
        }

        private void Window_Loaded(object sender, RoutedEventArgs e)
        {
            WindowChrome.SetWindowChrome(this, new WindowChrome { CaptionHeight = 0 });

            ShowInTaskbar = Owner == null;
        }

        private void Grid_MouseMove(object sender, MouseEventArgs e)
        {
            if (e.LeftButton == MouseButtonState.Pressed && AllowMove)
            {
                DragMove();
            }
        }

        public void BeginTask(TaskWindowDelegate[] routines, Dictionary<string, object> args)
        {
            if (_t != null)
            {
                return;
            }

            Routines = routines;
            Args = args;

            _t = new Thread(DoWork) { IsBackground = true };
            _t.Start(null);

            ShowDialog();
        }

        public void Terminate()
        {
            AllowExit = true;

            if (!_isClosing)
            {
                Dispatcher.Invoke(Close);
            }
        }

        private void DoWork(object args)
        {
            for (var i = 0; i < Routines.Length; i++)
            {
                CurrentRoutineIndex = i;

                try
                {
                    if (!Routines[i](this, Args))
                    {
                        Success = false;
                        break;
                    }
                }
                catch (Exception ex)
                {
                    MessageBox.Show(
                        string.Format("Unexpected error during task routine! \n\n Routine: \"{0}\",\n Exception: {1}",
                            Routines[i].Method.Name, ex), "", MessageBoxButton.OK, MessageBoxImage.Error);
                }

                Success = true;
            }

            Thread.Sleep(100);
            Terminate();
        }

        private void Window_Closing(object sender, CancelEventArgs e)
        {
            _isClosing = true;

            if (AllowExit)
            {
                return;
            }

            var result =
                MessageBox.Show(
                    "EloBuddy Task Window is currently busy. It is recommended that you wait to finish. Do you really want to exit?",
                    "EloBuddy Task Window", MessageBoxButton.YesNo, MessageBoxImage.Exclamation);

            if (result == MessageBoxResult.No && !AllowExit)
            {
                e.Cancel = true;
                _isClosing = false;

                return;
            }

            if (_t.IsAlive)
            {
                _t.Abort();
            }
        }
    }
}
