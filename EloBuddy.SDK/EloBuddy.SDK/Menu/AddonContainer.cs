using System;
using System.Collections.Generic;
using System.Linq;
using EloBuddy.SDK.Menu.Values;
using EloBuddy.SDK.Rendering;
using SharpDX;
using SharpDX.Direct3D9;

namespace EloBuddy.SDK.Menu
{
    public sealed class AddonContainer : ControlContainer<Button>
    {
        public Button ActiveButton { get; internal set; }

        internal Dictionary<Button, Tuple<Control, ControlContainer<ValueBase>>> LinkedContainers { get; set; }

        public override Vector2 Position
        {
            get { return base.Position; }
            internal set
            {
                // Set base position
                base.Position = value;

                // Update container positions
                if (LinkedContainers != null)
                {
                    foreach (var controls in LinkedContainers.Values)
                    {
                        controls.Item1.RecalculatePosition();
                        controls.Item2.RecalculatePosition();
                    }
                }
            }
        }

        internal AddonContainer() : base(ThemeManager.SpriteType.FormAddonButtonContainer, true)
        {
            // Initialize properties
            LinkedContainers = new Dictionary<Button, Tuple<Control, ControlContainer<ValueBase>>>();

            // Initalize theme specific properties
            OnThemeChange();
        }

        internal new void Add(Button button)
        {
        }

        internal ValueContainer AddMenu(Button button, string addonId, string uniqueMenuId, string longTitle = null, int index = -1)
        {
            if (button == null)
            {
                throw new ArgumentNullException("button");
            }
            if (uniqueMenuId == null)
            {
                throw new ArgumentNullException("uniqueMenuId");
            }

            // Add button to Children
            if (!Children.Contains(button))
            {
                button.SetParent(this);
                Children.Insert(index >= 0 ? index : Children.Count, button);
                RecalculateAlignOffsets();
            }

            // TODO: Improve
            button.TextHandle.Size = button.Size - new Vector2(button.TextHandle.Padding.X * 2f, 0);

            // Listen to state changes
            button.OnActiveStateChanged += OnActiveStateChanged;

            // Create a new control container for the button
            var container = new ValueContainer(addonId, uniqueMenuId) { IsVisible = false };
            MainMenu.Instance.Add(container);

            // Create a new emtpry control for the text handle
            var textHolder = new EmptyControl(ThemeManager.SpriteType.FormContentHeader) { IsVisible = false };
            textHolder.TextObjects.Add(new Text(longTitle ?? button.TextValue, new FontDescription
            {
                FaceName = "Gill Sans MT Pro Medium",
                Height = 20,
                Quality = FontQuality.Antialiased,
                Weight = FontWeight.ExtraBold
            })
            {
                TextAlign = Text.Align.Left,
                TextOrientation = Text.Orientation.Center,
                Color = System.Drawing.Color.FromArgb(255, 143, 122, 72)
            });
            MainMenu.Instance.Add(textHolder);

            // Associate button with container and long title text handle
            LinkedContainers.Add(button, new Tuple<Control, ControlContainer<ValueBase>>(textHolder, container));

            // Return container
            return container;
        }

        internal ValueContainer AddSubMenu(Button parent, Button subButton, string addonId, string uniqueMenuId, string longTitle = null)
        {
            if (parent == null)
            {
                throw new ArgumentNullException("parent");
            }
            if (subButton == null)
            {
                throw new ArgumentNullException("subButton");
            }
            if (!LinkedContainers.ContainsKey(parent))
            {
                throw new ArgumentException("parent was not added to the menu yet!", "parent");
            }
            if (parent.CurrentButtonType != Button.ButtonType.Addon)
            {
                throw new ArgumentException(string.Format("parent is of type {0}, which is invalid", parent.CurrentButtonType), "parent");
            }
            if (subButton.CurrentButtonType != Button.ButtonType.AddonSub)
            {
                throw new ArgumentException(string.Format("subButton is of type {0}, which is invalid", subButton.CurrentButtonType), "subButton");
            }

            var index = Children.FindIndex(o => o == parent) + 1;
            while (Children.Count > index && Children[index].CurrentButtonType == Button.ButtonType.AddonSub)
            {
                index++;
            }
            return AddMenu(subButton, addonId, uniqueMenuId, string.Format("{0} :: {1}", LinkedContainers[parent].Item1.TextObjects[0].TextValue, longTitle ?? subButton.TextValue), index);
        }

        public new void Remove(Button button)
        {
            // Remove button links
            if (button != null)
            {
                button.OnActiveStateChanged -= OnActiveStateChanged;
                if (LinkedContainers.ContainsKey(button))
                {
                    if (button.CurrentButtonType == Button.ButtonType.Addon)
                    {
                        // Remove all sub menu buttons
                        foreach (var subButton in GetSubMenus(button))
                        {
                            Remove(subButton);
                        }
                    }

                    // Base remove button
                    base.Remove(button);

                    MainMenu.Instance.Remove(LinkedContainers[button].Item1);
                    MainMenu.Instance.Remove(LinkedContainers[button].Item2);
                    LinkedContainers.Remove(button);
                }
            }
        }

        internal List<Button> GetSubMenus(Button mainButton)
        {
            if (mainButton == null)
            {
                throw new ArgumentNullException("mainButton");
            }
            if (mainButton.CurrentButtonType != Button.ButtonType.Addon)
            {
                throw new ArgumentException(string.Format("Button is not of type {0}!", Button.ButtonType.Addon), "mainButton");
            }
            if (!Children.Contains(mainButton))
            {
                throw new ArgumentException("Button is not yet added to the main menu", "mainButton");
            }

            var buttons = new List<Button>();
            var index = Children.FindIndex(o => o == mainButton) + 1;
            while (Children.Count > index + 1 && Children[index].CurrentButtonType == Button.ButtonType.AddonSub)
            {
                buttons.Add(Children[index]);
                index++;
            }

            return buttons;
        }

        internal void OnActiveStateChanged(Control sender, EventArgs args)
        {
            // Cast to the original button instance
            var button = (Button) sender;

            // Sender state active
            if (button.IsActive)
            {
                // Store the current button
                var oldButton = ActiveButton;

                // Apply new active button
                ActiveButton = button;

                // Check if there was a previous active button
                if (oldButton != null)
                {
                    // Set previous button to inactive
                    oldButton.IsActive = false;
                }

                // Show content of new active button
                SetContentView(ActiveButton, true);

                // Collapse sub menus (if present)
                if (ActiveButton.CurrentButtonType != Button.ButtonType.AddonSub)
                {
                    SetSubMenuState(ActiveButton, true);
                }
            }
            // Sender state inactive
            else
            {
                // Hide content of the button
                SetContentView(button, false);

                // Collapse sub menus (if button is main addon button)
                if (ActiveButton.CurrentButtonType == Button.ButtonType.Addon)
                {
                    SetSubMenuState(button, false);
                }

                // Check if the active button is the sender button
                if (ActiveButton == button)
                {
                    // Set active button to null
                    ActiveButton = null;
                }
            }
        }

        internal void SetSubMenuState(Button button, bool state)
        {
            var index = Children.FindIndex(o => o == button);
            if (button.CurrentButtonType == Button.ButtonType.AddonSub)
            {
                do
                {
                    // Decrease index
                    index--;
                } while (Children[index].CurrentButtonType == Button.ButtonType.AddonSub);
            }
            index++;
            if (Children.Count > index)
            {
                for (var i = index; i < Children.Count; i++)
                {
                    if (Children[i].CurrentButtonType == Button.ButtonType.AddonSub)
                    {
                        Children[i].ExcludeFromParent = !state;
                    }
                    else
                    {
                        break;
                    }
                }
                RecalculateAlignOffsets();
            }
        }

        internal void SetContentView(Button button, bool state)
        {
            if (LinkedContainers.ContainsKey(button))
            {
                LinkedContainers[button].Item1.IsVisible = state;
                LinkedContainers[button].Item2.IsVisible = state;
            }
        }
    }

    public sealed class ValueContainer : ControlContainer<ValueBase>
    {
        internal string _serializationId;
        public string SerializationId
        {
            get { return _serializationId; }
        }

        internal string AddonId { get; set; }

        internal List<Dictionary<string, object>> ChildrenSerializedData { get; set; }

        internal ValueContainer(string addonId, string serializationId)
            : base(ThemeManager.SpriteType.FormContentContainer, true, false, true)
        {
            // Initialize properties
            AddonId = addonId;
            _serializationId = serializationId;

            // Initalize theme specific properties
            OnThemeChange();

            // Initialize serialized data
            ChildrenSerializedData = new List<Dictionary<string, object>>();
            if (MainMenu.SavedValues != null && MainMenu.SavedValues.ContainsKey(AddonId) && MainMenu.SavedValues[AddonId].ContainsKey(serializationId))
            {
                ChildrenSerializedData = new List<Dictionary<string, object>>(MainMenu.SavedValues[AddonId][serializationId]);
            }
        }

        protected override Control BaseAdd(Control control)
        {
            // Base add the control
            base.BaseAdd(control);

            // Deserialize any stored data
            var value = (ValueBase) control;
            DeserializeChild(value);
            return value;
        }

        internal override void RecalculateAlignOffsets()
        {
            // Calculate the align offset for each control
            var currentHeight = 0;
            var previousCheckBox = false;
            var previousKeyBind = false;
            var previousLabel = false;
            for (var i = 0; i < Children.Count; i++)
            {
                var child = Children[i];
                if (child is CheckBox)
                {
                    if (previousCheckBox)
                    {
                        // Align check box in 2nd row
                        child.AlignOffset = new Vector2(Size.X / 2f - ContainerView.ScrollbarWidth / 2f, currentHeight);
                        currentHeight += Padding + (int) child.Size.Y;
                        previousCheckBox = false;
                    }
                    else
                    {
                        // Mark check box for next loop
                        child.AlignOffset = new Vector2(0, currentHeight);
                        previousCheckBox = true;
                    }

                    // Update helper markers
                    previousKeyBind = false;
                    previousLabel = false;
                }
                else
                {
                    if (previousCheckBox)
                    {
                        // Increase height because it was not increased with the last checkbox
                        currentHeight += Padding + (int) Children[i - 1].Size.Y;
                        previousCheckBox = false;
                    }

                    // KeyBind
                    var keyBind = child as KeyBind;
                    if (keyBind != null)
                    {
                        if (!previousKeyBind)
                        {
                            // Set key bind to draw the header
                            keyBind.DrawHeader = true;

                            if (previousLabel)
                            {
                                // Shorten the height
                                currentHeight -= KeyBind.HeaderHeight;

                                // Adjust width of the previous label
                                ((Label) Children[i - 1]).TextWidthMultiplier = (KeyBind.KeyButtonHandle.WidthMultiplier * 2 * ValueBase.DefaultWidth) / ValueBase.DefaultWidth;
                            }
                        }
                        else
                        {
                            // Set key bind to not draw the header as there is a previous key bind
                            keyBind.DrawHeader = false;
                        }
                    }
                    else if (previousLabel)
                    {
                        // Set the text width multiplier back to default (1)
                        ((Label) Children[i - 1]).TextWidthMultiplier = 1;
                    }

                    // Set align offset and increase height
                    child.AlignOffset = new Vector2(0, currentHeight);
                    currentHeight += Padding + (int) child.Size.Y;

                    // Update helper markers
                    previousKeyBind = child is KeyBind;
                    previousLabel = child is Label;
                }
            }

            // Recalculate Bounding
            RecalculateBounding();

            // Recalculate Cropping
            ContainerView.UpdateChildrenCropping();
        }

        internal List<Dictionary<string, object>> Serialize()
        {
            // Verify ChildrenSerializedData
            if (ChildrenSerializedData == null)
            {
                ChildrenSerializedData = new List<Dictionary<string, object>>();
            }

            // Merge current values with previous values
            var currentIds = Children.Select(o => o.SerializationId);
            ChildrenSerializedData.RemoveAll(o => o.ContainsKey("SerializationId") && currentIds.Contains(o["SerializationId"]));
            ChildrenSerializedData.AddRange(Children.Where(o => o.ShouldSerialize).Select(o => o.Serialize()));

            return new List<Dictionary<string, object>>(ChildrenSerializedData);
        }

        internal bool DeserializeChild(ValueBase value)
        {
            if (value == null)
            {
                throw new ArgumentNullException("value");
            }

            return ChildrenSerializedData.Any(value.ApplySerializedData);
        }
    }
}
