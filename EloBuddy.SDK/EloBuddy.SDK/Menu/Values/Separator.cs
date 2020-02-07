using System;
using System.Collections.Generic;
using System.Linq;

namespace EloBuddy.SDK.Menu.Values
{
    public sealed class Separator : ValueBase<int>
    {
        public override bool ShouldSerialize
        {
            get { return false; }
        }

        public override int CurrentValue
        {
            get { return Height; }
            set
            {
                Height = value;
                base.CurrentValue = Height;
            }
        }

        public override string DisplayName
        {
            get { return base.DisplayName; }
            // ReSharper disable once ValueParameterNotUsed
            set { }
        }

        public override string VisibleName
        {
            get { return DisplayName; }
        }

        public Separator(int height = DefaultHeight) : base("", Math.Max(DefaultHeight / 2, height))
        {
            // Initalize theme specific properties
            OnThemeChange();
        }

        public override bool Draw()
        {
            // Do not draw anything
            return true;
        }

        #region Serialization

        public override Dictionary<string, object> Serialize()
        {
            var baseData = base.Serialize();
            //baseData.Add("Height", Height);
            return baseData;
        }

        protected internal override bool ApplySerializedData(Dictionary<string, object> data)
        {
            if (data == null)
            {
                throw new ArgumentNullException("data");
            }

            if (base.ApplySerializedData(data))
            {
                // Check if all keys are present
                if (DeserializationNeededKeys.Any(key => !data.ContainsKey(key)))
                {
                    return false;
                }

                // Apply all keys to the object instance
                //Height = Convert.ToInt32(data["Height"]);

                return true;
            }
            return false;
        }

        internal static readonly List<string> DeserializationNeededKeys = new List<string>
        {
            //"Height"
        };

        #endregion
    }
}
