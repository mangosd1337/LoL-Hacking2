using System;
using EloBuddy.Sandbox;
using EloBuddy.SDK.Events;
using EloBuddy.SDK.Menu.Values;
using EloBuddy.SDK.Utils;
using SharpDX;

namespace EloBuddy.SDK
{
    internal static class StreamingMode
    {
        internal static bool IsEnabled
        {
            get { return Bootstrap.IsStreamingMode; }
            set
            {
                // Update Bootstrap property
                Bootstrap.IsStreamingMode = value;

                // Set according disable state
                Hacks.DisableDrawings = value;
            }
        }

        internal static Vector3 LastPosition { get; set; }
        internal static Random Random { get; set; }

        //Menu Variables
        internal static Menu.Menu StreamMenu;
        private static int MinDelay;
        private static int MaxDelay;

        internal static void Initialize()
        {
            if (!SandboxConfig.IsBuddy)
            {
                Logger.Debug(" - StreamingMode mode is available only for buddy users. -");
                return;
            }

            // Disable drawings
            Hacks.DisableDrawings = true;

            // Listen to required events
            Loading.OnLoadingComplete += delegate
            {
                // Initialize properties
                Random = new Random(DateTime.Now.Millisecond);
                LastPosition = Game.CursorPos;
                Player.OnPostIssueOrder += OnIssueOrder;
                Spellbook.OnPostCastSpell += OnCastSpell;
                Messages.RegisterEventHandler<Messages.KeyUp>(OnKeyUp);
            };

            Logger.Debug(" - StreamingMode enabled! Press F4 to disable! -");
        }

        internal static void LoadMenu()
        {
            #region Menu

            StreamMenu = Core.Menu.AddSubMenu("Streaming Mode", "streamingmode");

            StreamMenu.AddGroupLabel("Click delay");

            StreamMenu.AddLabel("The less the slider values are the faster it will click.");

            var minDelaySlider = StreamMenu.Add("minSlider", new Slider("Minimum delay (Default 150)", 150, 50, 800));
            var maxDelaySlider = StreamMenu.Add("maxSlider", new Slider("Maximum delay (Default 350)", 350, 150, 1600));

            MinDelay = minDelaySlider.CurrentValue;
            minDelaySlider.OnValueChange += delegate
            {
                MinDelay = minDelaySlider.CurrentValue;
                maxDelaySlider.MinValue = minDelaySlider.CurrentValue + 200;
            };

            MaxDelay = maxDelaySlider.CurrentValue;
            maxDelaySlider.OnValueChange += delegate { MaxDelay = maxDelaySlider.CurrentValue; };

            #endregion Menu
        }

        private static int _nextTargetedOffset = 20;

        private static void RandomizeTargetedClick(GameObject target)
        {
            var angle = Random.Next(180);
            var pos2D = target.Position.To2D();
            var direction = (Player.Instance.Position.To2D() - pos2D).Normalized();
            var position = pos2D + direction.Rotated(angle * (float) Math.PI / 180f) * _nextTargetedOffset;
            _nextTargetedOffset = Random.Next((int) target.BoundingRadius);
            Hud.ShowClick(ClickType.Attack, new Vector3(position.X, position.Y, target.Position.Z));
        }

        private static void OnCastSpell(Spellbook sender, SpellbookCastSpellEventArgs args)
        {
            if (sender.Owner.IsMe)
            {
                if (args.Target != null && args.Slot != SpellSlot.Recall)
                {
                    RandomizeTargetedClick(args.Target);
                }
            }
        }

        private static int _lastMovementProcessed;
        private static int _lastAttackProcessed;
        private static int _nextIssueOrderOffset = 250;

        internal static void OnIssueOrder(Obj_AI_Base sender, PlayerIssueOrderEventArgs args)
        {
            if (sender.IsMe && (args.Order == GameObjectOrder.AttackTo || args.Order == GameObjectOrder.AttackUnit || args.Order == GameObjectOrder.MoveTo))
            {
                var isInRange = args.TargetPosition.IsInRange(LastPosition, 300);
                if (args.Order == GameObjectOrder.AttackTo || args.Order == GameObjectOrder.AttackUnit)
                {
                    if (args.Target != null && (Core.GameTickCount > _lastAttackProcessed + _nextIssueOrderOffset || !isInRange))
                    {
                        RandomizeTargetedClick(args.Target);
                        _lastAttackProcessed = Core.GameTickCount;
                        LastPosition = args.TargetPosition;
                        _nextIssueOrderOffset = Random.Next(MinDelay, MaxDelay);
                    }
                }
                else if (args.Order == GameObjectOrder.MoveTo)
                {
                    if (Core.GameTickCount > _lastMovementProcessed + _nextIssueOrderOffset || !isInRange)
                    {
                        Hud.ShowClick(ClickType.Move, args.TargetPosition);
                        _lastMovementProcessed = Core.GameTickCount;
                        LastPosition = args.TargetPosition;
                        _nextIssueOrderOffset = Random.Next(MinDelay, MaxDelay);
                    }
                }
            }
        }

        internal static void OnKeyUp(Messages.KeyUp args)
        {
            // Check for F4 key
            if (args.Key == 0x73)
            {
                // Invert enabled property
                IsEnabled = !IsEnabled;
            }
        }
    }
}
