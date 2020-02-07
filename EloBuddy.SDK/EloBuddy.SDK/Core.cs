using System;
using System.Collections.Generic;
using System.Linq;
using EloBuddy.SDK.Events;
using EloBuddy.SDK.Menu;
using EloBuddy.SDK.Menu.Values;
using EloBuddy.SDK.Rendering;

namespace EloBuddy.SDK
{
    public static class Core
    {
        internal static Menu.Menu Menu { get; set; }
        public static int GameTickCount
        {
            get { return (int) (Game.Time * 1000); }
        }

        internal static readonly List<DelayedAction> ActionQueue = new List<DelayedAction>();

        static Core()
        {
            Game.OnUpdate += OnUpdate;
        }

        internal static void Initialize()
        {
            Menu = MainMenu.AddMenu("Core", "Core");

            #region Ticks

            var tickSlider = Menu.Add("TicksPerSecond", new Slider("Ticks Per Second", 25, 1, 75));
            tickSlider.OnValueChange += delegate(ValueBase<int> sender, ValueBase<int>.ValueChangeArgs args) { Game.TicksPerSecond = args.NewValue; };
            Game.TicksPerSecond = tickSlider.CurrentValue;
            Menu.AddLabel("         Recommended value: 25.");
            Menu.AddLabel("Note: Ticks per second means how often the Game.OnTick event should be fired.");
            Menu.AddLabel("This means this option is not a humanizer, just a performance option.");
            Menu.AddLabel("Example: 25 t/s means Game.OnTick is being fired 25 times per second.");
            Menu.AddLabel("Higher values mean more load each second, which could reduce FPS slightly.");

            #endregion Ticks

            Gapcloser.AddMenu();

            #region Hacks

            var hacksMenu = Menu.AddSubMenu("Hacks");

            var ingameChat = hacksMenu.Add("IngameChat", new CheckBox("Enable InGame Chat", Hacks.IngameChat));
            ingameChat.OnValueChange += delegate(ValueBase<bool> sender, ValueBase<bool>.ValueChangeArgs args) { Hacks.IngameChat = args.NewValue; };
            Hacks.IngameChat = ingameChat.CurrentValue;

            var antiAfk = hacksMenu.Add("AntiAFK", new CheckBox("Enable Anti AFK", Hacks.AntiAFK));
            antiAfk.OnValueChange += delegate(ValueBase<bool> sender, ValueBase<bool>.ValueChangeArgs args) { Hacks.AntiAFK = args.NewValue; };
            Hacks.AntiAFK = antiAfk.CurrentValue;

            var movementHack = hacksMenu.Add("MovementHack", new CheckBox("Enable Movement Hack", Hacks.MovementHack));
            movementHack.OnValueChange += delegate(ValueBase<bool> sender, ValueBase<bool>.ValueChangeArgs args) { Hacks.MovementHack = args.NewValue; };
            Hacks.MovementHack = movementHack.CurrentValue;

            var towersRange = hacksMenu.Add("TowerRanges", new CheckBox("Draw Tower Ranges", Hacks.TowerRanges));
            towersRange.OnValueChange += delegate(ValueBase<bool> sender, ValueBase<bool>.ValueChangeArgs args) { Hacks.TowerRanges = args.NewValue; };
            Hacks.TowerRanges = towersRange.CurrentValue;

            var renderWatermark = hacksMenu.Add("RenderWatermark", new CheckBox("Draw EloBuddy Watermark", Hacks.RenderWatermark));
            renderWatermark.OnValueChange += delegate(ValueBase<bool> sender, ValueBase<bool>.ValueChangeArgs args) { Hacks.RenderWatermark = args.NewValue; };
            Hacks.RenderWatermark = renderWatermark.CurrentValue;

            Hacks.ZoomHack = false;

            #endregion Hacks

            if (Sandbox.SandboxConfig.IsBuddy)
            {
                StreamingMode.LoadMenu();
            }
        }

        private static void OnUpdate(EventArgs args)
        {
            // Handle action queue
            foreach (var action in ActionQueue.Where(o => o.DelayTime < GameTickCount).ToArray())
            {
                try
                {
                    action.Action();
                }
                catch (Exception e)
                {
                    Console.WriteLine(e);
                }

                if (action.RepeatEndTime == 0 || action.RepeatEndTime < GameTickCount)
                {
                    ActionQueue.Remove(action);
                }
            }
        }

        /// <summary>
        /// Delay an Action with the given delayTime. This method is thread-safe.
        /// </summary>
        /// <param name="action">The Action to delay</param>
        /// <param name="delayTime">The delay time when the Action should be invoked</param>
        public static void DelayAction(Action action, int delayTime)
        {
            ActionQueue.Add(new DelayedAction { Action = action, DelayTime = GameTickCount + delayTime });
        }

        public static void RepeatAction(Action action, int delayTime, int repeatEndTime)
        {
            ActionQueue.Add(new DelayedAction { Action = action, DelayTime = GameTickCount + delayTime, RepeatEndTime = GameTickCount + delayTime + repeatEndTime });
        }

        internal class DelayedAction
        {
            public Action Action { get; set; }
            public int DelayTime { get; set; }
            public int RepeatEndTime { get; set; }
        }

        internal static void EndAllDrawing(RenderingType exclusion = RenderingType.None)
        {
            if (exclusion != RenderingType.Sprite)
            {
                if (Sprite.IsDrawing)
                {
                    Sprite.Handle.End();
                    Sprite.IsDrawing = false;
                }
            }
            if (exclusion != RenderingType.Line)
            {
                if (Line.IsDrawing)
                {
                    Line.Handle.End();
                    Line.IsDrawing = false;
                }
            }
        }

        internal enum RenderingType
        {
            None,
            Sprite,
            Line
        }
    }
}
