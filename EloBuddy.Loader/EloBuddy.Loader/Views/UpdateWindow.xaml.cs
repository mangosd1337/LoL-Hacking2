using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Threading;
using System.Windows;
using System.Windows.Input;
using System.Windows.Interop;
using System.Windows.Shell;
using EloBuddy.Loader.Utils;

namespace EloBuddy.Loader.Views
{
    /// <summary>
    ///     Interaction logic for UpdateWindow.xaml
    /// </summary>
    public partial class UpdateWindow
    {
        public delegate void UpdateWindowDelegate(UpdateWindow ui, Dictionary<string, object> args);

        private int _currentProgress;
        private string _details;
        private bool _isClosing;
        private int _maxProgress;
        private int _overalCurrentProgress;
        private int _overalMaxProgress;
        private string _status;
        private Thread _t;

        public UpdateWindow()
        {
            InitializeComponent();

            AllowMove = true;
            AllowExit = false;
            CurrentRoutineIndex = 0;
            Status = "";
        }

        public bool AllowMove { get; set; }
        public bool AllowExit { get; set; }
        public UpdateWindowDelegate[] Routines { get; private set; }
        public int CurrentRoutineIndex { get; private set; }
        public Dictionary<string, object> Args { get; private set; }

        public int MaxProgress
        {
            get { return _maxProgress; }

            set
            {
                if (_maxProgress == value)
                {
                    return;
                }

                StatusProgressBar.Dispatcher.Invoke(() => { StatusProgressBar.Maximum = value; });
                _maxProgress = value;
            }
        }

        public int CurrentProgress
        {
            get { return _currentProgress; }

            set
            {
                if (_currentProgress == value)
                {
                    return;
                }

                StatusProgressBar.Dispatcher.Invoke(() => { StatusProgressBar.Value = value; });
                _currentProgress = value;
            }
        }

        public int OveralMaxProgress
        {
            get { return _overalMaxProgress; }

            set
            {
                if (_overalMaxProgress == value)
                {
                    return;
                }

                OveralStatusProgressBar.Dispatcher.Invoke(
                    () => { OveralStatusProgressBar.Maximum = value; });
                _overalMaxProgress = value;
            }
        }

        public int OveralCurrentProgress
        {
            get { return _overalCurrentProgress; }

            set
            {
                if (_overalCurrentProgress == value)
                {
                    return;
                }

                OveralStatusProgressBar.Dispatcher.Invoke(
                    () => { OveralStatusProgressBar.Value = value; });
                _overalCurrentProgress = value;
            }
        }

        public string Status
        {
            get { return _status; }

            set
            {
                if (_status == value)
                {
                    return;
                }

                StatusLabel.Dispatcher.Invoke(() => { StatusLabel.Content = value; });
                _status = value;
            }
        }

        public string Details
        {
            get { return _details; }

            set
            {
                if (value == _details)
                {
                    return;
                }

                _details = value;
                StatusLabel.Dispatcher.Invoke(() => { StatusLabel.Content = StatusString; });
            }
        }

        private string StatusString
        {
            get
            {
                return string.Format("{2} (step {0} of {1}):  {3}", CurrentRoutineIndex + 1,
                    Routines == null ? -1 : Routines.Length, Status, Details);
            }
        }

        private void Window_Loaded(object sender, RoutedEventArgs e)
        {
            WindowChrome.SetWindowChrome(this, new WindowChrome { CaptionHeight = 0 });

            if (Owner != null)
            {
                AllowMove = false;
                ShowInTaskbar = false;
            }
            else
            {
                var handle = new WindowInteropHelper(this).Handle;

                NativeImports.ShowWindow(handle, 5);
                NativeImports.SetForegroundWindow(handle);
            }
        }

        private void Grid_MouseMove(object sender, MouseEventArgs e)
        {
            if (e.LeftButton == MouseButtonState.Pressed && AllowMove)
            {
                DragMove();
            }
        }

        public void BeginUpdate(UpdateWindowDelegate[] routines, Dictionary<string, object> args)
        {
            if (_t != null)
            {
                return;
            }

            Routines = routines;
            Args = args ?? new Dictionary<string, object>();

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
                    Routines[i](this, Args);
                }
                catch (Exception ex)
                {
                    MessageBox.Show(
                        string.Format(
                            "Unexpected error during update routine! \n\n Routine: \"{0}\",\n Exception: {1}",
                            Routines[i].Method.Name, ex), "", MessageBoxButton.OK, MessageBoxImage.Error);
                }

                // Smooth UI
                CurrentProgress = MaxProgress;
                Thread.Sleep(30);
            }

            Thread.Sleep(100);
            Terminate();
        }

        private void Window_Closing(object sender, CancelEventArgs e)
        {
            _isClosing = true;

            if (!AllowExit)
            {
                var result =
                    MessageBox.Show(
                        "EloBuddy update wizard is currently busy. It is recommended that you wait to finish. Do you really want to exit?",
                        "EloBuddy Update Wizard", MessageBoxButton.YesNo, MessageBoxImage.Exclamation);

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
}
