using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;
using EloBuddy.SDK.Rendering;
using EloBuddy.SDK.ThirdParty.Glide;
using SharpDX;
using SharpDX.Direct3D9;
using Color = System.Drawing.Color;
using Sprite = EloBuddy.SDK.Rendering.Sprite;

namespace EloBuddy.SDK.Notifications
{
    public static class Notifications
    {
        internal static readonly TextureLoader TextureLoader = new TextureLoader();
        internal static readonly Tweener Tweener = new Tweener();
        internal static int _lastUpdate;

        public static int MaxNotificationHeight = 500;

        internal static readonly int NotificationStart = Drawing.Height - 425; //(int) (TacticalMap.Y - 175);
        internal const int NotificationPadding = 20;

        internal const int TextHeight = 16;
        internal const int HeaderToContentSpace = 2;
        internal static readonly Vector2 TextOuterPadding = new Vector2(8);

        internal const int FadeInTime = 500;
        internal const int FadeOutTime = 500;
        internal const int MoveDownTime = 200;

        internal static readonly List<ActiveNotification> ActiveNotifications = new List<ActiveNotification>();

        static Notifications()
        {
            // Initialize fields
            _lastUpdate = Core.GameTickCount;

            // Listen to required events
            Game.OnUpdate += OnUpdate;
            Drawing.OnEndScene += delegate
            {
                Tweener.Update((Core.GameTickCount - _lastUpdate) / 1000f);
                _lastUpdate = Core.GameTickCount;
                OnDraw();
            };
        }

        internal static void OnUpdate(EventArgs args)
        {
            lock (ActiveNotifications)
            {
                // Remove timed up notifications
                ActiveNotifications.RemoveAll(o => o.Tween == null && !o.IsValid);

                // Check for notifications to move down
                var notificationIndex = 1;
                foreach (var notification in ActiveNotifications.Where(notification => notification._initialized))
                {
                    // Check for spawn in
                    if (notification.Tween == null && notification.OffsetX < 0)
                    {
                        notification.Tween = Tweener.Tween(notification, new { OffsetX = 0, AlphaLevel = (int) byte.MaxValue }, FadeInTime / 1000f);
                    }

                    // Check for notifications to move down
                    if (notification.Index > notificationIndex)
                    {
                        if (notification.Tween == null)
                        {
                            // Set the new index
                            notification.Index = notificationIndex;

                            // Move notification down by one
                            notification.Tween = Tweener.Tween(notification, new { PositionY = GetIndexY(notification.Index) }, MoveDownTime / 1000f);
                            notification.ExtraTime += MoveDownTime;
                        }
                    }

                    // Check for fade out
                    if (notification.Tween == null && notification.ShouldFadeOut)
                    {
                        notification.Tween = Tweener.Tween(notification, new { OffsetX = 100, AlphaLevel = 0 }, FadeOutTime / 1000f);
                        notification.Index = int.MaxValue;
                        notification.IsFadingOut = true;
                    }

                    // Check if notification is fading out
                    if (!notification.IsFadingOut)
                    {
                        notificationIndex++;
                    }
                }
            }
        }

        internal static void OnDraw()
        {
            lock (ActiveNotifications)
            {
                foreach (var notification in ActiveNotifications.Where(notification => notification._initialized))
                {
                    // Draw the notification
                    notification.Draw();
                }
            }
        }

        internal static int GetIndexY(int index)
        {
            // ReSharper disable once InconsistentlySynchronizedField
            var result = ActiveNotifications.Where(o => o.Index <= index).TakeWhile(o => o.Index <= index).Sum(o => o.Height + NotificationPadding);
            return result - NotificationPadding;
        }

        public static void Show(INotification notification, int duration = 5000)
        {
            // Create active notification
            var active = new ActiveNotification(notification, duration);
            lock (ActiveNotifications)
            {
                ActiveNotifications.Add(active);
            }
        }

        internal class ActiveNotification
        {
            internal int Index { get; set; }

            #region Tween Values

            internal int OffsetX { get; set; }
            internal int PositionY { get; set; }
            internal int AlphaLevel { get; set; }

            #endregion

            internal Vector2 Position
            {
                get { return new Vector2(Drawing.Width - Handle.RightPadding - Width + OffsetX, NotificationStart - PositionY); }
            }

            internal int Height { get; set; }
            internal int Width { get; set; }
            internal float ContentScale { get; set; }

            internal INotification Handle { get; set; }
            internal int Duration { get; set; }
            internal int StartTick { get; set; }

            internal int ExtraTime { get; set; }

            internal Tween _tween;
            internal Tween Tween
            {
                get { return _tween; }
                set
                {
                    _tween = value;
                    if (value != null)
                    {
                        // Auto remove tween after finishing
                        value.OnComplete(() => { _tween = null; });
                    }
                }
            }

            internal bool IsValid
            {
                get { return !_initialized || StartTick + Duration + ExtraTime + FadeInTime + FadeOutTime > Core.GameTickCount; }
            }

            internal bool ShouldFadeOut
            {
                get { return StartTick + Duration + ExtraTime + FadeInTime < Core.GameTickCount; }
            }
            internal bool IsFadingOut { get; set; }

            internal Text HeaderText { get; set; }
            internal Text ContentText { get; set; }
            internal Sprite Header { get; set; }
            internal Sprite Content { get; set; }
            internal Sprite Footer { get; set; }

            internal bool _initialized;

            internal ActiveNotification(INotification handle, int duration)
            {
                // Initialize properties
                Handle = handle;
                Duration = duration;
                OffsetX = -200;
                ContentScale = 1;

                // Initialize rendering

                #region Text

                HeaderText = new Text(Handle.HeaderText, new FontDescription
                {
                    FaceName = Handle.FontName,
                    Height = TextHeight,
                    Quality = FontQuality.Antialiased,
                    Weight = FontWeight.Medium
                })
                {
                    Color = Handle.HeaderColor,
                    DrawFlags = FontDrawFlags.WordBreak | FontDrawFlags.Right
                };
                ContentText = new Text(Handle.ContentText, new FontDescription
                {
                    FaceName = Handle.FontName,
                    Height = TextHeight,
                    Quality = FontQuality.Antialiased,
                    Weight = FontWeight.Medium
                })
                {
                    Color = Handle.ContentColor,
                    DrawFlags = FontDrawFlags.WordBreak | FontDrawFlags.Right
                };

                #endregion

                #region Sprite

                if (Handle.Texture.Header != null)
                {
                    Header = new Sprite(Handle.Texture.Header.Texture);
                }
                if (Handle.Texture.Content != null)
                {
                    Content = new Sprite(Handle.Texture.Content.Texture);
                }
                if (Handle.Texture.Footer != null)
                {
                    Footer = new Sprite(Handle.Texture.Footer.Texture);
                }

                #endregion

                Task.Run(() =>
                {
                    // Get max width
                    var maxX = 0;
                    var maxY = 0;
                    foreach (var texture in new[] { Handle.Texture.Header, Handle.Texture.Content, Handle.Texture.Footer }.Where(texture => texture != null))
                    {
                        int width;
                        int height;
                        if (texture.SourceRectangle.HasValue)
                        {
                            width = texture.SourceRectangle.Value.Width;
                            height = texture.SourceRectangle.Value.Height;
                        }
                        else
                        {
                            var desc = texture.Texture().GetLevelDescription(0);
                            width = desc.Width;
                            height = desc.Height;
                        }

                        if (maxX < width)
                        {
                            maxX = width;
                        }
                        if (maxY < height)
                        {
                            maxY = height;
                        }
                    }

                    // Set width and height
                    Width = maxX;
                    Height = maxY;

                    // Calculate text height
                    var boundingRect = HeaderText.MeasureBounding(Handle.HeaderText, new Rectangle(0, 0, Width - (int) TextOuterPadding.X * 2, MaxNotificationHeight - (int) TextOuterPadding.Y * 2),
                        HeaderText.DrawFlags);
                    HeaderText.Size = new Vector2(boundingRect.Width, boundingRect.Height);

                    boundingRect = ContentText.MeasureBounding(Handle.ContentText,
                        new Rectangle(0, 0, Width - (int) TextOuterPadding.X * 2, MaxNotificationHeight - (int) TextOuterPadding.Y * 2 - boundingRect.Height - HeaderToContentSpace),
                        ContentText.DrawFlags);
                    ContentText.Size = new Vector2(boundingRect.Width, boundingRect.Height);

                    var notificationHeight = TextOuterPadding.Y * 2 + HeaderText.Size.Y + ContentText.Size.Y + HeaderToContentSpace;
                    if (notificationHeight > maxY)
                    {
                        // Override height
                        Height = (int) Math.Ceiling(notificationHeight);

                        if (Handle.Texture.Content != null)
                        {
                            var relativeHeight = Height;

                            if (Handle.Texture.Header != null)
                            {
                                if (Handle.Texture.Header.SourceRectangle.HasValue)
                                {
                                    relativeHeight -= Handle.Texture.Header.SourceRectangle.Value.Height;
                                }
                                else
                                {
                                    relativeHeight -= Handle.Texture.Header.Texture().GetLevelDescription(0).Height;
                                }
                            }
                            if (Handle.Texture.Footer != null)
                            {
                                if (Handle.Texture.Footer.SourceRectangle.HasValue)
                                {
                                    relativeHeight -= Handle.Texture.Footer.SourceRectangle.Value.Height;
                                }
                                else
                                {
                                    relativeHeight -= Handle.Texture.Footer.Texture().GetLevelDescription(0).Height;
                                }
                            }

                            var contentHeight = Handle.Texture.Content.SourceRectangle.HasValue
                                ? Handle.Texture.Content.SourceRectangle.Value.Height
                                : Handle.Texture.Content.Texture().GetLevelDescription(0).Height;

                            // Set content scaling
                            ContentScale = (float) relativeHeight / contentHeight;
                        }
                    }

                    lock (ActiveNotifications)
                    {
                        // Set notification index
                        Index = (ActiveNotifications.Where(o => !o.IsFadingOut).Max(o => (int?) o.Index) ?? 0) + 1;

                        // Set notification position
                        PositionY = GetIndexY(Index);
                    }

                    // Set the start tick
                    StartTick = Core.GameTickCount;

                    _initialized = true;
                });
            }

            internal void Draw()
            {
                // Get the start position of the notification
                var pos = Position;

                // Draw the textures
                if (Header != null)
                {
                    Header.Color = Color.FromArgb(AlphaLevel, Content.Color);
                    Header.Draw(pos + Handle.Texture.Header.Position ?? Vector2.Zero, Handle.Texture.Header.SourceRectangle);
                }
                if (Content != null)
                {
                    Content.Color = Color.FromArgb(AlphaLevel, Content.Color);
                    Content.Draw(pos + Handle.Texture.Content.Position ?? Vector2.Zero, Handle.Texture.Content.SourceRectangle, null, null, new Vector2(1, ContentScale));
                }
                if (Footer != null)
                {
                    Footer.Color = Color.FromArgb(AlphaLevel, Footer.Color);
                    if (ContentScale > 1)
                    {
                        int extraHeight;
                        if (Handle.Texture.Footer.SourceRectangle.HasValue)
                        {
                            extraHeight = Height - Handle.Texture.Footer.SourceRectangle.Value.Height;
                        }
                        else
                        {
                            extraHeight = Height - Footer.Texture.GetLevelDescription(0).Height;
                        }
                        Footer.Draw(pos + new Vector2(0, extraHeight), Handle.Texture.Footer.SourceRectangle);
                    }
                    else
                    {
                        Footer.Draw(pos + Handle.Texture.Footer.Position ?? Vector2.Zero, Handle.Texture.Footer.SourceRectangle);
                    }
                }

                // Draw the text
                HeaderText.Draw(Handle.HeaderText, Color.FromArgb(AlphaLevel, HeaderText.Color),
                    new Rectangle((int) (pos.X + Width - HeaderText.Width - TextOuterPadding.X), (int) (pos.Y + TextOuterPadding.Y), (int) HeaderText.Size.X, (int) HeaderText.Size.Y));
                ContentText.Draw(Handle.ContentText, Color.FromArgb(AlphaLevel, ContentText.Color),
                    new Rectangle((int) (pos.X + Width - ContentText.Width - TextOuterPadding.X), (int) (pos.Y + TextOuterPadding.Y + HeaderText.Size.Y + HeaderToContentSpace),
                        (int) ContentText.Size.X, (int) ContentText.Size.Y));
            }
        }
    }
}
