using System;
using System.Collections;
using System.Collections.Generic;
using System.Linq;

namespace EloBuddy.SDK.Menu
{
    public class ControlList<T> : IList<T>, IList where T : Control
    {
        internal List<Control> RefList { get; set; }

        internal ControlList(ref List<Control> refList)
        {
            // Initialize properties
            RefList = refList;
        }

        public int FindIndex(Predicate<T> match)
        {
            for (var i = 0; i < RefList.Count; i++)
            {
                if (match((T) RefList[i]))
                {
                    return i;
                }
            }
            return -1;
        }

        public IEnumerator<T> GetEnumerator()
        {
            return RefList.Cast<T>().GetEnumerator();
        }

        IEnumerator IEnumerable.GetEnumerator()
        {
            return GetEnumerator();
        }

        public void Add(T item)
        {
            RefList.Add(item);
        }

        int IList.Add(object value)
        {
            throw new NotImplementedException();
        }

        bool IList.Contains(object value)
        {
            throw new NotImplementedException();
        }

        public void Clear()
        {
            RefList.Clear();
        }

        int IList.IndexOf(object value)
        {
            throw new NotImplementedException();
        }

        void IList.Insert(int index, object value)
        {
            throw new NotImplementedException();
        }

        void IList.Remove(object value)
        {
            throw new NotImplementedException();
        }

        public bool Contains(T item)
        {
            return RefList.Contains(item);
        }

        public void CopyTo(T[] array, int arrayIndex)
        {
            RefList.CopyTo(array, arrayIndex);
        }

        public bool Remove(T item)
        {
            return RefList.Remove(item);
        }

        void ICollection.CopyTo(Array array, int index)
        {
            throw new NotImplementedException();
        }

        public int Count
        {
            get { return RefList.Count; }
        }
        object ICollection.SyncRoot
        {
            get { throw new NotImplementedException(); }
        }
        bool ICollection.IsSynchronized
        {
            get { throw new NotImplementedException(); }
        }
        bool ICollection<T>.IsReadOnly
        {
            get { return false; }
        }
        bool IList.IsFixedSize
        {
            get { throw new NotImplementedException(); }
        }

        public int IndexOf(T item)
        {
            return RefList.IndexOf(item);
        }

        public void Insert(int index, T item)
        {
            RefList.Insert(index, item);
        }

        public void RemoveAt(int index)
        {
            RefList.RemoveAt(index);
        }

        object IList.this[int index]
        {
            get { throw new NotImplementedException(); }
            set { throw new NotImplementedException(); }
        }
        bool IList.IsReadOnly
        {
            get { throw new NotImplementedException(); }
        }

        public T this[int index]
        {
            get { return (T) RefList[index]; }
            set { RefList[index] = value; }
        }
    }
}
