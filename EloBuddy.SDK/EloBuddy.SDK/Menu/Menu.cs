using System;
using System.Collections.Generic;
using System.Linq;
using EloBuddy.SDK.Menu.Values;

namespace EloBuddy.SDK.Menu
{
    public sealed class Menu
    {
        public Menu Parent { get; internal set; }
        public bool IsSubMenu
        {
            get { return Parent != null; }
        }

        internal List<string> UsedSubMenuNames { get; set; }
        public List<Menu> SubMenus { get; set; }

        internal Button AddonButton { get; set; }
        internal ValueContainer ValueContainer { get; set; }

        public Dictionary<string, ValueBase> LinkedValues { get; set; }

        public string DisplayName
        {
            get { return AddonButton.TextValue; }
            set { AddonButton.TextHandle.TextValue = value; }
        }

        public string UniqueMenuId
        {
            get { return ValueContainer.SerializationId; }
        }

        internal string AddonId { get; set; }

        internal Menu(string displayName, string addonId, string uniqueMenuId, string longTitle = null, Menu parent = null)
        {
            if (string.IsNullOrWhiteSpace(displayName))
            {
                throw new ArgumentNullException("displayName");
            }
            if (string.IsNullOrWhiteSpace(uniqueMenuId))
            {
                throw new ArgumentNullException("uniqueMenuId");
            }

            // Initialize properties
            Parent = parent;
            AddonId = addonId;
            UsedSubMenuNames = new List<string>();
            SubMenus = new List<Menu>();
            LinkedValues = new Dictionary<string, ValueBase>();

            // Initialize controls
            ValueContainer = parent == null
                ? MainMenu.AddonButtonContainer.AddMenu(AddonButton = new Button(Button.ButtonType.Addon, displayName), addonId, uniqueMenuId, longTitle)
                : MainMenu.AddonButtonContainer.AddSubMenu(parent.AddonButton, AddonButton = new Button(Button.ButtonType.AddonSub, displayName), addonId, uniqueMenuId, longTitle);

            // Add menu to MainMenu instances
            if (!MainMenu.MenuInstances.ContainsKey(addonId))
            {
                MainMenu.MenuInstances.Add(addonId, new List<Menu>());
            }
            MainMenu.MenuInstances[addonId].Add(this);

            // Listen to required events
            AppDomain.CurrentDomain.DomainUnload += OnUnload;
            AppDomain.CurrentDomain.ProcessExit += OnUnload;
        }

        internal void OnUnload(object sender, EventArgs eventArgs)
        {
            if (AddonButton != null)
            {
                if (!IsSubMenu)
                {
                    MainMenu.AddonButtonContainer.Remove(AddonButton);
                    UsedSubMenuNames.Clear();
                    SubMenus.Clear();
                }

                // Remove menu from MainMenu instances
                MainMenu.MenuInstances.Remove(UniqueMenuId);
                AddonButton = null;
            }
        }

        public Menu AddSubMenu(string displayName, string uniqueSubMenuId = null, string longTitle = null)
        {
            if (string.IsNullOrWhiteSpace(displayName))
            {
                throw new ArgumentNullException("displayName");
            }
            if (IsSubMenu)
            {
                throw new ArgumentException("Can't add a sub menu to a sub menu!");
            }
            if (UsedSubMenuNames.Contains(uniqueSubMenuId ?? displayName))
            {
                throw new ArgumentException(string.Format("A sub menu with that name ({0}) already exists!", uniqueSubMenuId ?? displayName));
            }

            UsedSubMenuNames.Add(uniqueSubMenuId ?? displayName);
            var subMenu = new Menu(displayName, AddonId, string.Concat(ValueContainer.SerializationId, ".", uniqueSubMenuId ?? displayName), longTitle, this);
            SubMenus.Add(subMenu);
            return subMenu;
        }

        public void AddSeparator(int height = ValueBase.DefaultHeight)
        {
            ValueContainer.Add(new Separator(height));
        }

        public void AddLabel(string text, int height = ValueBase.DefaultHeight)
        {
            if (string.IsNullOrWhiteSpace(text))
            {
                throw new ArgumentNullException("text");
            }

            Add(text, new Label(text, height));
            //ValueContainer.Add(new Label(text, height));
        }

        public void AddGroupLabel(string text)
        {
            if (string.IsNullOrWhiteSpace(text))
            {
                throw new ArgumentNullException("text");
            }
            Add(text, new GroupLabel(text));
            //ValueContainer.Add(new GroupLabel(text));
        }

        public T Add<T>(string uniqueIdentifier, T value) where T : ValueBase
        {
            if (string.IsNullOrWhiteSpace(uniqueIdentifier))
            {
                throw new ArgumentNullException("uniqueIdentifier");
            }
            if (value == null)
            {
                throw new ArgumentNullException("value");
            }
            var lowerUniqueIdentifier = uniqueIdentifier.ToLower();
            if (LinkedValues.ContainsKey(lowerUniqueIdentifier))
            {
                return LinkedValues[lowerUniqueIdentifier] as T;
                //throw new ArgumentException("An unique identifier with that name already exists!", "uniqueIdentifier");
            }
            if (value.Parent != null)
            {
                throw new ArgumentException("Value has already been added to another menu!", "value");
            }

            // Update serilization id of the value
            value._serializationId = uniqueIdentifier;

            LinkedValues.Add(lowerUniqueIdentifier, value);
            ValueContainer.Add(value);
            return value;
        }

        public void Remove(string uniqueIdentifier)
        {
            if (string.IsNullOrWhiteSpace(uniqueIdentifier))
            {
                throw new ArgumentNullException("uniqueIdentifier");
            }
            uniqueIdentifier = uniqueIdentifier.ToLower();

            if (LinkedValues.ContainsKey(uniqueIdentifier))
            {
                Remove(LinkedValues[uniqueIdentifier]);
                LinkedValues.Remove(uniqueIdentifier);
            }
        }

        public void Remove(ValueBase value)
        {
            if (value == null)
            {
                throw new ArgumentNullException("value");
            }

            ValueContainer.Remove(value);
            foreach (var entry in LinkedValues.Where(entry => entry.Value == value).ToArray())
            {
                LinkedValues.Remove(entry.Key);
                break;
            }
        }

        public ValueBase this[string uniqueIdentifier]
        {
            get
            {
                if (string.IsNullOrWhiteSpace(uniqueIdentifier))
                {
                    throw new ArgumentNullException("uniqueIdentifier");
                }
                uniqueIdentifier = uniqueIdentifier.ToLower();
                return LinkedValues.ContainsKey(uniqueIdentifier) ? LinkedValues[uniqueIdentifier] : null;
            }
        }

        public T Get<T>(string uniqueIdentifier) where T : ValueBase
        {
            if (string.IsNullOrWhiteSpace(uniqueIdentifier))
            {
                throw new ArgumentNullException("uniqueIdentifier");
            }
            uniqueIdentifier = uniqueIdentifier.ToLower();
            return LinkedValues.ContainsKey(uniqueIdentifier) ? LinkedValues[uniqueIdentifier].Cast<T>() : null;
        }
    }
}
