using System;
using System.Collections.Generic;
using SharpDX;

namespace EloBuddy.SDK
{
    public enum WindowMessages
    {
        LeftButtonDoubleClick = 0x203,
        MiddleButtonDoubleClick = 0x209,
        RightButtonDoubleClick = 0x206,
        MiddleButtonDown = 0x207,
        MiddleButtonUp = 0x208,
        MouseMove = 0x200,
        MouseWheel = 0x20A,
        LeftButtonDown = 0x201,
        LeftButtonUp = 0x202,
        RightButtonDown = 0x204,
        RightButtonUp = 0x205,
        KeyDown = 0x100,
        KeyUp = 0x101
    }

    public static class Messages
    {
        public delegate void MessageHandler(WindowMessage args);

        public delegate void MessageHandler<in T>(T args) where T : WindowMessage;

        public static event MessageHandler OnMessage;

        internal static Vector2 _previousMousePosition;

        internal static readonly Dictionary<Type, List<object>> EventHandlers = new Dictionary<Type, List<object>>();

        static Messages()
        {
            // Initialize properties
            _previousMousePosition = Game.CursorPos2D;

            // Listen to required events
            Game.OnWndProc += OnWndProc;
        }

        public static void RegisterEventHandler<T>(MessageHandler<T> handler) where T : WindowMessage
        {
            var handlerType = typeof (T);
            if (!EventHandlers.ContainsKey(handlerType))
            {
                EventHandlers.Add(handlerType, new List<object>());
            }
            EventHandlers[handlerType].Add(handler);
        }

        public static void UnregisterEventHandler<T>(MessageHandler<T> handler) where T : WindowMessage
        {
            var handlerType = typeof (T);
            if (EventHandlers.ContainsKey(handlerType))
            {
                EventHandlers[handlerType].Remove(handler);
                if (EventHandlers[handlerType].Count == 0)
                {
                    EventHandlers.Remove(handlerType);
                }
            }
        }

        internal static void NotifyEventHandlers(WindowMessage message)
        {
            var messageType = message.GetType();
            if (EventHandlers.ContainsKey(messageType))
            {
                EventHandlers[messageType].ForEach(handler =>
                {
                    try
                    {
                        switch (message.Message)
                        {
                            case WindowMessages.KeyDown:
                                ((MessageHandler<KeyDown>) handler).Invoke((KeyDown) message);
                                break;
                            case WindowMessages.KeyUp:
                                ((MessageHandler<KeyUp>) handler).Invoke((KeyUp) message);
                                break;
                            case WindowMessages.LeftButtonDoubleClick:
                                ((MessageHandler<LeftButtonDoubleClick>) handler).Invoke((LeftButtonDoubleClick) message);
                                break;
                            case WindowMessages.LeftButtonDown:
                                ((MessageHandler<LeftButtonDown>) handler).Invoke((LeftButtonDown) message);
                                break;
                            case WindowMessages.LeftButtonUp:
                                ((MessageHandler<LeftButtonUp>) handler).Invoke((LeftButtonUp) message);
                                break;
                            case WindowMessages.MiddleButtonDoubleClick:
                                ((MessageHandler<MiddleButtonDoubleClick>) handler).Invoke((MiddleButtonDoubleClick) message);
                                break;
                            case WindowMessages.MiddleButtonDown:
                                ((MessageHandler<MiddleButtonDown>) handler).Invoke((MiddleButtonDown) message);
                                break;
                            case WindowMessages.MiddleButtonUp:
                                ((MessageHandler<MiddleButtonUp>) handler).Invoke((MiddleButtonUp) message);
                                break;
                            case WindowMessages.MouseMove:
                                ((MessageHandler<MouseMove>) handler).Invoke((MouseMove) message);
                                break;
                            case WindowMessages.MouseWheel:
                                ((MessageHandler<MouseWheel>) handler).Invoke((MouseWheel) message);
                                break;
                            case WindowMessages.RightButtonDoubleClick:
                                ((MessageHandler<RightButtonDoubleClick>) handler).Invoke((RightButtonDoubleClick) message);
                                break;
                            case WindowMessages.RightButtonDown:
                                ((MessageHandler<RightButtonDown>) handler).Invoke((RightButtonDown) message);
                                break;
                            case WindowMessages.RightButtonUp:
                                ((MessageHandler<RightButtonUp>) handler).Invoke((RightButtonUp) message);
                                break;
                        }
                    }
                    catch (Exception e)
                    {
                        Console.WriteLine("Error notifying event handler for message '{0}'", message);
                        Console.WriteLine(e);
                    }
                });
            }
        }

        internal static void OnWndProc(WndEventArgs args)
        {
            if (OnMessage != null)
            {
                WindowMessage eventHandle = null;
                switch (args.Msg)
                {
                    case (uint) WindowMessages.KeyDown:
                        NotifyEventHandlers(eventHandle = new KeyDown(args));
                        break;
                    case (uint) WindowMessages.KeyUp:
                        NotifyEventHandlers(eventHandle = new KeyUp(args));
                        break;
                    case (uint) WindowMessages.LeftButtonDoubleClick:
                        NotifyEventHandlers(eventHandle = new LeftButtonDoubleClick(args));
                        break;
                    case (uint) WindowMessages.LeftButtonDown:
                        NotifyEventHandlers(eventHandle = new LeftButtonDown(args));
                        break;
                    case (uint) WindowMessages.LeftButtonUp:
                        NotifyEventHandlers(eventHandle = new LeftButtonUp(args));
                        break;
                    case (uint) WindowMessages.MiddleButtonDoubleClick:
                        NotifyEventHandlers(eventHandle = new MiddleButtonDoubleClick(args));
                        break;
                    case (uint) WindowMessages.MiddleButtonDown:
                        NotifyEventHandlers(eventHandle = new MiddleButtonDown(args));
                        break;
                    case (uint) WindowMessages.MiddleButtonUp:
                        NotifyEventHandlers(eventHandle = new MiddleButtonUp(args));
                        break;
                    case (uint) WindowMessages.MouseMove:
                        NotifyEventHandlers(eventHandle = new MouseMove(args));
                        break;
                    case (uint) WindowMessages.MouseWheel:
                        NotifyEventHandlers(eventHandle = new MouseWheel(args));
                        break;
                    case (uint) WindowMessages.RightButtonDoubleClick:
                        NotifyEventHandlers(eventHandle = new RightButtonDoubleClick(args));
                        break;
                    case (uint) WindowMessages.RightButtonDown:
                        NotifyEventHandlers(eventHandle = new RightButtonDown(args));
                        break;
                    case (uint) WindowMessages.RightButtonUp:
                        NotifyEventHandlers(eventHandle = new RightButtonUp(args));
                        break;
                }

                if (eventHandle != null)
                {
                    // Trigger the event
                    OnMessage(eventHandle);
                    args.Process = eventHandle.Process;

                    // Update previous mouse position
                    if (eventHandle is MouseEvent)
                    {
                        _previousMousePosition = Game.CursorPos2D;
                    }
                }
            }
        }

        public abstract class WindowMessage : EventArgs
        {
            public WndEventArgs Handle { get; protected internal set; }
            public WindowMessages Message
            {
                get { return (WindowMessages) Handle.Msg; }
            }

            public bool Process
            {
                get { return Handle.Process; }
                set { Handle.Process = value; }
            }

            protected WindowMessage(WndEventArgs args)
            {
                Handle = args;
            }
        }

        public abstract class MouseEvent : WindowMessage
        {
            public Vector2 PreviousMousePosition
            {
                get { return _previousMousePosition; }
            }

            public Vector2 MousePosition { get; internal set; }

            public bool IsCtrlDown
            {
                get { return (Handle.WParam & 0x0008) != 0; }
            }
            public bool IsLeftButtonDown
            {
                get { return (Handle.WParam & 0x0001) != 0; }
            }
            public bool IsMiddleButtonDown
            {
                get { return (Handle.WParam & 0x0010) != 0; }
            }
            public bool IsRightButtonDown
            {
                get { return (Handle.WParam & 0x0002) != 0; }
            }
            public bool IsShiftDown
            {
                get { return (Handle.WParam & 0x0004) != 0; }
            }

            protected MouseEvent(WndEventArgs args) : base(args)
            {
                MousePosition = Game.CursorPos2D;
            }
        }

        public abstract class KeyEvent : WindowMessage
        {
            public char KeyChar
            {
                get { return (char) Handle.WParam; }
            }

            public uint Key
            {
                get { return Handle.WParam; }
            }

            protected KeyEvent(WndEventArgs args) : base(args)
            {
            }
        }

        public class LeftButtonDoubleClick : MouseEvent
        {
            public LeftButtonDoubleClick(WndEventArgs args) : base(args)
            {
            }
        }

        public class MiddleButtonDoubleClick : MouseEvent
        {
            public MiddleButtonDoubleClick(WndEventArgs args) : base(args)
            {
            }
        }

        public class RightButtonDoubleClick : MouseEvent
        {
            public RightButtonDoubleClick(WndEventArgs args) : base(args)
            {
            }
        }

        public class MiddleButtonDown : MouseEvent
        {
            public MiddleButtonDown(WndEventArgs args) : base(args)
            {
            }
        }

        public class MiddleButtonUp : MouseEvent
        {
            public MiddleButtonUp(WndEventArgs args)
                : base(args)
            {
            }
        }

        public class MouseMove : MouseEvent
        {
            public MouseMove(WndEventArgs args) : base(args)
            {
            }
        }

        public class MouseWheel : MouseEvent
        {
            public enum Directions
            {
                Down,
                Up
            }

            public const int WHEEL_DELTA = 120;

            public short Rotation
            {
                get { return (short) (Handle.WParam >> 16); }
            }
            public Directions Direction
            {
                get { return Rotation < 0 ? Directions.Down : Directions.Up; }
            }
            public int ScrollSteps
            {
                get { return Math.Abs(Rotation) / WHEEL_DELTA; }
            }

            public MouseWheel(WndEventArgs args)
                : base(args)
            {
            }
        }

        public class LeftButtonDown : MouseEvent
        {
            public LeftButtonDown(WndEventArgs args) : base(args)
            {
            }
        }

        public class LeftButtonUp : MouseEvent
        {
            public LeftButtonUp(WndEventArgs args) : base(args)
            {
            }
        }

        public class RightButtonDown : MouseEvent
        {
            public RightButtonDown(WndEventArgs args) : base(args)
            {
            }
        }

        public class RightButtonUp : MouseEvent
        {
            public RightButtonUp(WndEventArgs args) : base(args)
            {
            }
        }

        public class KeyDown : KeyEvent
        {
            public KeyDown(WndEventArgs args) : base(args)
            {
            }
        }

        public class KeyUp : KeyEvent
        {
            public KeyUp(WndEventArgs args) : base(args)
            {
            }
        }
    }
}
