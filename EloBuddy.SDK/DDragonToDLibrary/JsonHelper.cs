using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

namespace DDragonToDLibrary
{
    public static class JsonHelper
    {
        private const string IndentString = "  ";
        public static string FormatJson(string str)
        {
            var indent = 0;
            var quoted = false;
            var inArray = false;
            var sb = new StringBuilder();
            for (var i = 0; i < str.Length; i++)
            {
                var ch = str[i];
                switch (ch)
                {
                    case '{':
                    case '[':
                        sb.Append(ch);
                        if (!quoted)
                        {
                            float value;
                            if (i < str.Length && float.TryParse(str[i + 1].ToString(), out value))
                            {
                                inArray = true;
                                sb.Append(" ");
                            }
                            else
                            {
                                sb.AppendLine();
                                Enumerable.Range(0, ++indent).ForEach(item => sb.Append(IndentString));
                            }
                        }
                        break;
                    case '}':
                    case ']':
                        if (!quoted)
                        {
                            if (!inArray)
                            {
                                sb.AppendLine();
                                Enumerable.Range(0, --indent).ForEach(item => sb.Append(IndentString));
                            }
                            else
                            {
                                sb.Append(" ");
                            }
                            inArray = false;
                        }
                        sb.Append(ch);
                        break;
                    case '"':
                        sb.Append(ch);
                        var escaped = false;
                        var index = i;
                        while (index > 0 && str[--index] == '\\')
                            escaped = !escaped;
                        if (!escaped)
                            quoted = !quoted;
                        break;
                    case ',':
                        sb.Append(ch);
                        if (!quoted)
                        {
                            if (!inArray)
                            {
                                sb.AppendLine();
                                Enumerable.Range(0, indent).ForEach(item => sb.Append(IndentString));
                            }
                            else
                            {
                                sb.Append(" ");
                            }
                        }
                        break;
                    case ':':
                        sb.Append(ch);
                        if (!quoted)
                            sb.Append(" ");
                        break;
                    case '.':
                        var failed = false;
                        var skip = 0;
                        for (var j = i + 1; j < str.Length; j++)
                        {
                            int value;
                            if (int.TryParse(str[j].ToString(), out value))
                            {
                                if (value != 0)
                                {
                                    failed = true;
                                    break;
                                }
                                skip++;
                            }
                            else
                            {
                                break;
                            }
                        }
                        if (failed)
                        {
                            sb.Append(ch);
                        }
                        else
                        {
                            i += skip;
                        }
                        break;
                    default:
                        sb.Append(ch);
                        break;
                }
            }
            return sb.ToString();
        }
    }

    public static partial class Extensions
    {
        public static void ForEach<T>(this IEnumerable<T> ie, Action<T> action)
        {
            foreach (var i in ie)
            {
                action(i);
            }
        }
    }
}
