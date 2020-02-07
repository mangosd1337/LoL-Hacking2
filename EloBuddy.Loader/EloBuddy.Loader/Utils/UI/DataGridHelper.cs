using System.Windows;
using System.Windows.Controls;
using System.Windows.Controls.Primitives;
using System.Windows.Input;
using System.Windows.Media;

namespace EloBuddy.Loader.Utils.UI
{
    internal static class DataGridHelper
    {
        public delegate Point GetDragPosition(IInputElement element);

        public static int GetDataGridItemCurrentRowIndex(this DataGrid grid, GetDragPosition pos)
        {
            if (grid == null || pos == null)
            {
                return -1;
            }

            var curIndex = -1;
            for (var i = 0; i < grid.Items.Count; i++)
            {
                var item = grid.GetDataGridRowItem(i);
                if (!IsTheMouseOnTargetRow(item, pos))
                {
                    continue;
                }

                curIndex = i;
                break;
            }

            return curIndex;
        }

        public static DataGridRow GetDataGridRowItem(this DataGrid grid, int index)
        {
            if (grid.ItemContainerGenerator.Status != GeneratorStatus.ContainersGenerated)
            {
                return null;
            }

            return grid.ItemContainerGenerator.ContainerFromIndex(index) as DataGridRow;
        }

        public static bool IsColumnSelected(this MouseEventArgs e)
        {
            var dep = (DependencyObject) e.OriginalSource;
            while ((dep != null) && !(dep is DataGridCell) && !(dep is DataGridColumnHeader))
            {
                dep = VisualTreeHelper.GetParent(dep);
            }

            return dep is DataGridColumnHeader;
        }

        public static bool IsTheMouseOnTargetRow(this Visual target, GetDragPosition pos)
        {
            if (target == null || pos == null)
            {
                return false;
            }

            var posBounds = VisualTreeHelper.GetDescendantBounds(target);
            var mousePos = pos((IInputElement) target);

            return posBounds.Contains(mousePos);
        }
    }
}
