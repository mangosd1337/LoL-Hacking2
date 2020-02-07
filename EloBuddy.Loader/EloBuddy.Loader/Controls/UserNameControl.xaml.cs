using System.ComponentModel;
using System.Drawing;
using System.Windows;
using System.Windows.Input;
using System.Windows.Media;
using Brush = System.Windows.Media.Brush;

namespace EloBuddy.Loader.Controls
{
    /// <summary>
    ///     Interaction logic for UserNameControl.xaml
    /// </summary>
    public partial class UserNameControl : INotifyPropertyChanged
    {
        private Bitmap _avatar;
        private Brush _imageBackgroundBrush;
        private string _userName;

        public UserNameControl()
        {
            InitializeComponent();

            //DefaultStyleKeyProperty.OverrideMetadata(typeof(UserNameControl), new FrameworkPropertyMetadata(typeof(UserNameControl)));

            DataContext = this;

            ImageBackgroundBrush = new SolidColorBrush(Colors.Lime);
        }

        public string UserName
        {
            get { return _userName; }
            set
            {
                _userName = value;
                RaisePropertyChanged("UserName");
            }
        }

        public Brush ImageBackgroundBrush
        {
            get { return _imageBackgroundBrush; }
            set
            {
                _imageBackgroundBrush = value;
                RaisePropertyChanged("ImageBackgroundBrush");
            }
        }

        public Bitmap Avatar
        {
            get { return _avatar; }
            set
            {
                _avatar = value;
                RaisePropertyChanged("Avatar");
            }
        }

        public event PropertyChangedEventHandler PropertyChanged;

        private void RaisePropertyChanged(string prop)
        {
            if (PropertyChanged != null)
            {
                PropertyChanged(this, new PropertyChangedEventArgs(prop));
            }
        }

        #region click

        public static RoutedEvent ClickEvent =
            EventManager.RegisterRoutedEvent("Click", RoutingStrategy.Bubble, typeof (RoutedEventHandler),
                typeof (UserNameControl));

        public event RoutedEventHandler Click
        {
            add { AddHandler(ClickEvent, value); }
            remove { RemoveHandler(ClickEvent, value); }
        }

        protected virtual void OnClick()
        {
            var args = new RoutedEventArgs(ClickEvent, this);

            RaiseEvent(args);
        }

        protected override void OnMouseLeftButtonUp(MouseButtonEventArgs e)
        {
            base.OnMouseLeftButtonUp(e);

            OnClick();
        }

        #endregion
    }
}
