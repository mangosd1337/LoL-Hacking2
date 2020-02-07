using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Reflection;
using System.Runtime.InteropServices;
using System.Timers;
using EloBuddy.SDK.Rendering;
using EloBuddy.SDK.Utils;
using Newtonsoft.Json;
using SharpDX;
using SharpDX.Direct3D9;
using Color = System.Drawing.Color;
using Sprite = EloBuddy.SDK.Rendering.Sprite;

namespace EloBuddy.SDK.Menu
{
    public static class MainMenu
    {
        internal const string SaveDataFileName = "SerializedMenu.ebm";
        internal static readonly string SaveDataDirectoryPath = string.Concat(DefaultSettings.EloBuddyPath, Path.DirectorySeparatorChar, "MenuSaveData");
        internal static readonly string SaveDataFilePath = string.Concat(SaveDataDirectoryPath, Path.DirectorySeparatorChar, SaveDataFileName);
        internal static readonly string SaveDataBackupFilePath = string.Concat(SaveDataFilePath, "_backup");

        public delegate void OpenHandler(object sender, EventArgs args);

        public delegate void CloseHandler(object sender, EventArgs args);

        public static event OpenHandler OnOpen;
        public static event CloseHandler OnClose;

        public static bool IsOpen
        {
            get { return IsVisible; }
        }

        internal static ControlContainer Instance { get; set; }

        internal static readonly TextureLoader TextureLoader = new TextureLoader();

        // Position of the menu
        public static Vector2 Position
        {
            get { return Instance.Position; }
            internal set { Instance.Position = value; }
        }

        // Text types
        internal static Text TitleText { get; set; }

        // Empty controls
        internal static Control TitleBar { get; set; }

        // Control containers
        internal static AddonContainer AddonButtonContainer { get; set; }

        // Buttons
        internal static Button ExitButton { get; set; }

        // General sprite used for all common controls
        internal static Sprite Sprite { get; set; }

        // Move offset
        internal static Vector2 MoveOffset { get; set; }

        // IsVisible wrapper from the Instance
        public static bool IsVisible
        {
            get { return Instance.IsVisible; }
            internal set
            {
                if (Instance.IsVisible != value)
                {
                    Instance.IsVisible = value;
                    if (!value)
                    {
                        RemoveAllMouseInteractions(Instance);
                        if (OnClose != null)
                        {
                            OnClose(null, EventArgs.Empty);
                        }
                    }
                    else
                    {
                        if (OnOpen != null)
                        {
                            OnOpen(null, EventArgs.Empty);
                        }
                    }
                }
            }
        }

        internal static uint CurrentKeyDown { get; set; }

        public static bool IsMouseInside
        {
            get { return Instance.IsMouseInside || ExitButton.IsMouseInside; }
        }

        // All created menus
        public static readonly Dictionary<string, List<Menu>> MenuInstances = new Dictionary<string, List<Menu>>();

        // Unique menu ids that have been used already
        public static List<string> UsedUniqueNames
        {
            get { return new List<string>(MenuInstances.Keys); }
        }

        // Timer used to save menu data
        internal static Timer SaveTimer { get; set; }

        internal static Dictionary<string, Dictionary<string, List<Dictionary<string, object>>>> SavedValues { get; set; }

        public static bool IsLoaded { get; internal set; }

        internal static void Initialize()
        {
            // Initialize properties
            SavedValues = new Dictionary<string, Dictionary<string, List<Dictionary<string, object>>>>();
            Instance = new SimpleControlContainer(ThemeManager.SpriteType.FormComplete, false, true, false)
            {
                IsVisible = false
            };
            Sprite = new Sprite(() => ThemeManager.CurrentTheme.Texture);
            // Control containers
            Instance.Add(AddonButtonContainer = new AddonContainer());
            // Empty controls
            Instance.Add(TitleBar = new EmptyControl(ThemeManager.SpriteType.FormHeader));
            // Buttons
            Instance.Add(ExitButton = new Button(Button.ButtonType.Exit));
            // Text types
            TitleBar.TextObjects.Add(TitleText = new Text("ELOBUDDY", new FontDescription
            {
                FaceName = "Gill Sans MT Pro Medium",
                Height = 28,
                Quality = FontQuality.Antialiased,
                Weight = FontWeight.ExtraBold,
                Width = 12,
            })
            {
                TextAlign = Text.Align.Center,
                TextOrientation = Text.Orientation.Center,
                Color = Color.FromArgb(255, 143, 122, 72),
                Padding = new Vector2(0, 3)
            });

            // Simple event hooks
            ExitButton.OnActiveStateChanged += delegate
            {
                if (ExitButton.IsActive)
                {
                    IsVisible = false;
                    ExitButton.IsActive = false;
                }
            };
            TitleBar.OnLeftMouseDown += delegate { MoveOffset = Position - Game.CursorPos2D; };
            TitleBar.OnLeftMouseUp += delegate { MoveOffset = Vector2.Zero; };

            // Don't pass anything through the menu to the game if the mouse is inside of the menu
            Messages.OnMessage += delegate(Messages.WindowMessage args)
            {
                if (IsMouseInside)
                {
                    args.Process = false;
                }
            };

            // Center menu position
            Position = (new Vector2(Drawing.Width, Drawing.Height) - Instance.Size) / 2;

            // Setup save timer
            SaveTimer = new Timer(60000);
            SaveTimer.Elapsed += OnSaveTimerElapsed;
            SaveTimer.Start();
            OnSaveTimerElapsed(null, null);

            // Listen to events
            ThemeManager.OnThemeChanged += OnThemeChanged;
            Sprite.OnMenuDraw += OnMenuDraw;
            Messages.OnMessage += OnWndMessage;
            AppDomain.CurrentDomain.DomainUnload += OnUnload;
            AppDomain.CurrentDomain.ProcessExit += OnUnload;

#if DEBUG
            var debugMenu = AddMenu("Debug", "debugging");
            debugMenu.AddGroupLabel("Menu outline (skeleton)");
            debugMenu.Add("drawOutline", new CheckBox("Draw outline", false)).CurrentValue = false;
            debugMenu.AddGroupLabel("Load Bootstrap");
            debugMenu.Add("targetselector", new CheckBox("TargetSelector", false)).CurrentValue = false;
            debugMenu.Add("orbwalker", new CheckBox("Orbwalker", false)).CurrentValue = false;
            debugMenu.Add("prediction", new CheckBox("Prediction", false)).CurrentValue = false;
            debugMenu.Add("damagelibrary", new CheckBox("DamageLibrary", false)).CurrentValue = false;

            debugMenu["drawOutline"].Cast<CheckBox>().OnValueChange += delegate(ValueBase<bool> sender, ValueBase<bool>.ValueChangeArgs args)
            {
                ControlContainerBase.DrawOutline = args.NewValue;
            };

            debugMenu["targetselector"].Cast<CheckBox>().OnValueChange += delegate
            {
                TargetSelector.Initialize();
                Core.DelayAction(() => debugMenu.Remove("targetselector"), 25);
            };
            debugMenu["orbwalker"].Cast<CheckBox>().OnValueChange += delegate
            {
                Orbwalker.Initialize();
                Core.DelayAction(() => debugMenu.Remove("orbwalker"), 25);
            };
            debugMenu["prediction"].Cast<CheckBox>().OnValueChange += delegate
            {
                Prediction.Initialize();
                Core.DelayAction(() => debugMenu.Remove("prediction"), 25);
            };
            debugMenu["damagelibrary"].Cast<CheckBox>().OnValueChange += delegate
            {
                DamageLibrary.Initialize();
                Core.DelayAction(() => debugMenu.Remove("damagelibrary"), 25);
            };
#endif

            // Indicate that the menu has finished loading
            IsLoaded = true;
        }

        public static Menu AddMenu(string displayName, string uniqueMenuId, string longTitle = null)
        {
            if (string.IsNullOrWhiteSpace(displayName))
            {
                throw new ArgumentNullException("displayName");
            }
            if (string.IsNullOrWhiteSpace(uniqueMenuId))
            {
                throw new ArgumentNullException("uniqueMenuId");
            }

            // Get the addon id of the calling addon
            var addonId = Assembly.GetCallingAssembly().GetAddonId();

            // Load saved data (if existing)
            LoadAddonSavedValues(addonId);

            // Validate that the menu unique id does not exist
            if (MenuInstances.ContainsKey(addonId) && MenuInstances[addonId].Any(o => o.UniqueMenuId == uniqueMenuId))
            {
                throw new ArgumentException("The provided unique menu id is already given!", uniqueMenuId);
            }

            // Add menu instance
            return new Menu(displayName, addonId, uniqueMenuId, longTitle);
        }

        public static Menu GetMenu(string uniqueMenuId)
        {
            // Get the addon id of the calling addon
            var addonId = Assembly.GetCallingAssembly().GetAddonId();

            return MenuInstances.ContainsKey(addonId) ? MenuInstances[addonId].Find(o => o.UniqueMenuId == uniqueMenuId) : null;
        }

        internal static void OnWndMessage(Messages.WindowMessage args)
        {
            // Do not open the menu when the chat is open
            if (!Chat.IsOpen)
            {
                // Shift key check
                switch (args.Message)
                {
                    case WindowMessages.KeyDown:
                    case WindowMessages.KeyUp:
                        // Shift key
                        if (args.Handle.WParam == 16)
                        {
                            if (args.Message == WindowMessages.KeyDown && CurrentKeyDown == 16)
                            {
                                break;
                            }
                            IsVisible = args.Message == WindowMessages.KeyDown;
                        }
                        break;
                }

                // Call key events for each control
                switch (args.Message)
                {
                    case WindowMessages.KeyDown:
                        if (CurrentKeyDown != args.Handle.WParam)
                        {
                            CurrentKeyDown = args.Handle.WParam;
                            Instance.OnKeyDown((Messages.KeyDown) args);
                        }
                        break;
                    case WindowMessages.KeyUp:
                        CurrentKeyDown = 0;
                        Instance.OnKeyUp((Messages.KeyUp) args);
                        break;
                }
            }
            else
            {
                // Close menu on chat opening
                IsVisible = false;
            }

            if (IsVisible)
            {
                var mouseEvent = args as Messages.MouseEvent;
                if (mouseEvent != null)
                {
                    // Handle main menu moving first
                    switch (args.Message)
                    {
                        case WindowMessages.MouseMove:
                            if (TitleBar.IsLeftMouseDown)
                            {
                                Position = Game.CursorPos2D + MoveOffset;
                            }
                            break;
                        case WindowMessages.LeftButtonUp:
                        case WindowMessages.RightButtonUp:
                            CallMouseUpMethods(Instance, args.Message == WindowMessages.LeftButtonUp);
                            break;
                    }

                    // Call general methods
                    CallMouseMoveMethods(Instance, mouseEvent.MousePosition, Control.IsOnOverlay(mouseEvent.MousePosition));

                    // Get the top most control to work with
                    switch (args.Message)
                    {
                        case WindowMessages.LeftButtonDown:
                        case WindowMessages.RightButtonDown:
                        {
                            if (args.Message == WindowMessages.LeftButtonDown)
                            {
                                var container = GetTopMostControl<ControlContainerBase>(Instance, mouseEvent.MousePosition, false);
                                if (container != null && container.ContainerView.CheckScrollbarDown(mouseEvent.MousePosition))
                                {
                                    break;
                                }
                            }
                            var control = GetTopMostControl(Instance, mouseEvent.MousePosition, false);
                            switch (args.Message)
                            {
                                case WindowMessages.LeftButtonDown:
                                    control.IsLeftMouseDown = true;
                                    control.CallLeftMouseDown();
                                    break;
                                case WindowMessages.RightButtonDown:
                                    control.IsRightMouseDown = true;
                                    control.CallRightMouseDown();
                                    break;
                            }
                            break;
                        }
                        case WindowMessages.MouseWheel:
                        {
                            var container = GetTopMostControl<ControlContainerBase>(Instance, mouseEvent.MousePosition, false);
                            if (container != null)
                            {
                                container.CallMouseWheel((Messages.MouseWheel) args);
                            }
                            break;
                        }
                    }
                }
            }
        }

        internal static Control GetTopMostControl(
            ControlContainerBase container,
            Vector2 position,
            bool validatePosition = true,
            bool checkOverlay = true)
        {
            return GetTopMostControl<Control>(container, position, validatePosition, checkOverlay);
        }

        internal static T GetTopMostControl<T>(
            ControlContainerBase container,
            Vector2 position,
            bool validatePosition = true,
            bool checkOverlay = true) where T : Control
        {
            // Overlay control handling
            if (checkOverlay)
            {
                for (var i = Control.OverlayControls.Count - 1; i >= 0; i--)
                {
                    var overlay = Control.OverlayControls[i];
                    if (overlay.IsVisible && overlay.IsInside(position))
                    {
                        var overlayContainer = overlay as ControlContainerBase;
                        if (overlayContainer != null && !overlayContainer.GetType().IsAssignableFrom(typeof (T)))
                        {
                            return GetTopMostControl<T>(overlayContainer, position, false, false);
                        }
                        var typedOverlay = overlay as T;
                        if (typedOverlay != null)
                        {
                            return typedOverlay;
                        }
                    }
                }
            }

            if (validatePosition && !container.IsInside(position))
            {
                return null;
            }

            // Regular control handling
            for (var i = container.Children.Count - 1; i >= 0; i--)
            {
                var child = container.Children[i];
                if (child.IsVisible && !child.ExcludeFromParent && child.IsInside(position))
                {
                    var childContainer = child as ControlContainerBase;
                    if (childContainer != null && !childContainer.GetType().IsAssignableFrom(typeof (T)))
                    {
                        return GetTopMostControl<T>(childContainer, position, false, false);
                    }
                    var typedChild = child as T;
                    if (typedChild != null)
                    {
                        return typedChild;
                    }
                }
            }

            return container as T;
        }

        internal static void RemoveAllMouseInteractions(ControlContainerBase container)
        {
            container.IsLeftMouseDown = false;
            container.IsRightMouseDown = false;
            container.IsMouseInside = false;
            foreach (var child in container.Children)
            {
                child.IsLeftMouseDown = false;
                child.IsRightMouseDown = false;
                child.IsMouseInside = false;

                var childContainer = child as ControlContainerBase;
                if (childContainer != null)
                {
                    RemoveAllMouseInteractions(childContainer);
                    continue;
                }
                var dynamicControl = child as DynamicControl;
                if (dynamicControl != null)
                {
                    dynamicControl.SetDefaultState();
                }
            }
        }

        internal static void CallMouseUpMethods(ControlContainerBase container, bool leftButton = true)
        {
            CallMouseUpMethods(leftButton, container);
            foreach (var child in container.Children)
            {
                if (child.ExcludeFromParent)
                {
                    continue;
                }

                var childContainer = child as ControlContainerBase;
                if (childContainer != null)
                {
                    CallMouseUpMethods(childContainer, leftButton);
                }
                else
                {
                    CallMouseUpMethods(leftButton, child);
                }
            }
        }

        internal static void CallMouseUpMethods(bool leftButton, Control control)
        {
            if (control.OverlayControl != null)
            {
                var overlayContainer = control.OverlayControl as ControlContainerBase;
                if (overlayContainer != null)
                {
                    CallMouseUpMethods(overlayContainer, leftButton);
                }
                else
                {
                    CallMouseUpMethods(leftButton, control.OverlayControl);
                }
            }
            if (leftButton && control.IsLeftMouseDown)
            {
                control.IsLeftMouseDown = false;
                control.CallLeftMouseUp();
            }
            if (!leftButton && control.IsRightMouseDown)
            {
                control.IsRightMouseDown = false;
                control.CallRightMouseUp();
            }
        }

        internal static void CallMouseMoveMethods(ControlContainerBase container, Vector2 mousePosition, bool isOnOverlay = false, bool overlayCheck = true)
        {
            while (true)
            {
                var children = new List<Control>();

                if (overlayCheck)
                {
                    children.AddRange(Control.OverlayControls.Where(o => o.IsVisible));
                }
                else
                {
                    if (container.IsVisible)
                    {
                        children.Add(container);
                        children.AddRange(container.Children.Where(o => o.IsVisible));
                    }
                }

                foreach (var child in children)
                {
                    if (child.ExcludeFromParent)
                    {
                        continue;
                    }

                    var mouseInside = child.IsInside(mousePosition);

                    // Mouse is on overlay, but child is not an overlay
                    if (isOnOverlay && !child.IsOverlay)
                    {
                        // Make the mouse disappear from the child which is no overlay itself
                        if (child.IsMouseInside)
                        {
                            child.IsMouseInside = false;
                            child.CallMouseLeave();
                        }
                    }
                    else
                    {
                        if (mouseInside == child.IsMouseInside)
                        {
                            child.CallMouseMove();
                        }
                        else
                        {
                            child.IsMouseInside = mouseInside;
                            if (mouseInside)
                            {
                                child.CallMouseEnter();
                            }
                            else
                            {
                                child.CallMouseLeave();
                            }
                        }
                    }

                    if (child == container)
                    {
                        continue;
                    }

                    var childContainer = child as ControlContainerBase;
                    if (childContainer != null)
                    {
                        CallMouseMoveMethods(childContainer, mousePosition, isOnOverlay, false);
                    }
                }

                if (overlayCheck)
                {
                    overlayCheck = false;
                    continue;
                }
                break;
            }
        }

        internal static void OnThemeChanged(EventArgs args)
        {
            // Update the sprite
            Sprite = new Sprite(() => ThemeManager.CurrentTheme.Texture);

            // Update all controls
            Instance.OnThemeChange();
        }

        internal static void OnMenuDraw(EventArgs args)
        {
            // Draw the menu
            if (IsVisible)
            {
                // Draw the menu frame
                Instance.Draw();

                // Draw text objects
                Instance.TextObjects.ForEach(o => o.Draw());

                // Draw all overlays
                Control.OverlayControls.ForEach(overlay => { overlay.Draw(); });
            }
        }

        internal static string GetAddonId(this Assembly assembly)
        {
            try
            {
                return assembly.GetName().Name + "_" + ((GuidAttribute)assembly.GetCustomAttributes(typeof(GuidAttribute), true)[0]).Value;
            }
            catch (Exception)
            {
                throw new Exception("GUID attribute is not defined!");
            }
        }

        internal static void LoadAddonSavedValues(string addonId)
        {
            // Check if values have already been loaded
            if (SavedValues.ContainsKey(addonId))
            {
                return;
            }

            // Create a new entry
            SavedValues.Add(addonId, new Dictionary<string, List<Dictionary<string, object>>>());

            // Verify that the directory exists
            Directory.CreateDirectory(SaveDataDirectoryPath);

            // Get the file path
            var filePath = Path.Combine(SaveDataDirectoryPath, addonId + ".json");

            // Check if save file exists
            if (!File.Exists(filePath))
            {
                return;
            }

            // Deserialize file contents
            var data = JsonConvert.DeserializeObject<Dictionary<string, List<Dictionary<string, object>>>>(File.ReadAllText(filePath));

            // Apply saved data to dictionary
            SavedValues[addonId] = data;
        }

        internal static void OnSaveTimerElapsed(object sender, ElapsedEventArgs elapsedEventArgs)
        {
            // Verify that the directory exists
            Directory.CreateDirectory(SaveDataDirectoryPath);

            foreach (var entry in MenuInstances)
            {
                // Serialize current data
                var data = new Dictionary<string, List<Dictionary<string, object>>>();
                foreach (var menu in entry.Value)
                {
                    data[menu.UniqueMenuId] = menu.ValueContainer.Serialize();
                }

                // Verify that there is data to save
                if (data.Count > 0)
                {
                    // Merge previous data with current data
                    var dataToSave = SavedValues.ContainsKey(entry.Key) ? SavedValues[entry.Key] : new Dictionary<string, List<Dictionary<string, object>>>();
                    foreach (var entryKeys in data)
                    {
                        dataToSave[entryKeys.Key] = entryKeys.Value;
                    }

                    // Get the file path
                    var filePath = Path.Combine(SaveDataDirectoryPath, entry.Key + ".json");
                    var fileBackupPath = filePath + ".backup";

                    // Create a backup of the current file
                    if (File.Exists(filePath))
                    {
                        File.Copy(filePath, fileBackupPath, true);
                    }

                    try
                    {
                        // Write content to the file
                        File.WriteAllText(filePath, JsonConvert.SerializeObject(dataToSave));
                    }
                    catch (Exception e)
                    {
                        // Restore the backup
                        if (File.Exists(fileBackupPath))
                        {
                            Logger.Warn("Error during config file writing, restoring backup!");
                            File.Copy(fileBackupPath, filePath, true);
                        }
                    }

                    // Delete the backup file
                    File.Delete(fileBackupPath);
                }
            }
        }

        internal static void OnUnload(object sender, EventArgs eventArgs)
        {
            if (SaveTimer != null)
            {
                OnSaveTimerElapsed(null, null);
                SaveTimer.Dispose();
                SaveTimer = null;
            }

            TextureLoader.Dispose();
            TitleText.Dispose();
        }
    }
}
