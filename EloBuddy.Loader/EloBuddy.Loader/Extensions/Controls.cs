using System.Windows.Controls;
using System.Windows.Documents;
using System.Windows.Media;

namespace EloBuddy.Loader.Extensions
{
    public static class Controls
    {
        public static void AppendText(this RichTextBox box, string text, string color)
        {
            var b = new BrushConverter();
            var tr = new TextRange(box.Document.ContentEnd, box.Document.ContentEnd) { Text = text };
            tr.ApplyPropertyValue(TextElement.ForegroundProperty, b.ConvertFromString(color));
        }
    }
}
