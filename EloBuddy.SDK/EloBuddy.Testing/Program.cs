using System;
using EloBuddy.SDK;
using EloBuddy.SDK.Enumerations;
using EloBuddy.SDK.Events;
using EloBuddy.SDK.Menu;
using EloBuddy.SDK.Menu.Values;
using EloBuddy.SDK.Utils;
using SharpDX;
using Color = System.Drawing.Color;

namespace EloBuddy.Testing
{
    public class Program
    {
        private static Menu Menu;

        public static void Main(string[] args)
        {
            AppDomain.CurrentDomain.UnhandledException += delegate(object sender, UnhandledExceptionEventArgs eventArgs) { Console.WriteLine(eventArgs.ExceptionObject); };
            Loading.OnLoadingComplete += OnLoadingComplete;
        }

        private static void OnLoadingComplete(EventArgs args)
        {
            Menu = MainMenu.AddMenu("Prediction Test", "Prediction Test");
            Menu.Add("CastHitchance", new Slider("Cast Hitchance", 5, 3, 8)).OnValueChange +=
                delegate(ValueBase<int> sender, ValueBase<int>.ValueChangeArgs changeArgs)
                {
                    sender.DisplayName = "Cast Hitchance: " + (HitChance)changeArgs.NewValue;
                };
            Menu.Add("Cast", new CheckBox("Cast Spell"));

            Game.OnTick += OnTick;
            Drawing.OnDraw += OnDraw;
        }

        private static void OnDraw(EventArgs args)
        {
            var drawX = 400;
            var drawY = 400;
            var drawYScale = 20;
            var range = 1500;
            var color = Color.Red;

            var target = TargetSelector.GetTarget(range, DamageType.Magical);

            if (target != null)
            {
                var result = Prediction.Position.PredictLinearMissile(target, range, 80, 250, 1300, 0);

                Drawing.DrawText(drawX, drawY + drawYScale * 0, color, "Hitchance %: " + result.HitChancePercent);
                Drawing.DrawText(drawX, drawY + drawYScale * 1, color, "Hitchance : " + result.HitChance);
                Drawing.DrawText(drawX, drawY + drawYScale * 2, color, "Collision Count : " + result.CollisionObjects.Length);
                Drawing.DrawText(drawX, drawY + drawYScale * 3, color, "Collision : " + result.Collision);

                foreach (var unit in result.CollisionObjects)
                {
                    SDK.Rendering.Circle.Draw(new ColorBGRA(255, 0, 0, 255), unit.BoundingRadius, 30, unit.Position);
                }

                SDK.Rendering.Circle.Draw(new ColorBGRA(0, 255, 0, 255), target.BoundingRadius, 6, result.CastPosition);

                if (Menu["Cast"].Cast<CheckBox>().CurrentValue && (int)result.HitChance >= Menu["CastHitchance"].Cast<Slider>().CurrentValue)
                {
                    Player.CastSpell(SpellSlot.Q, result.CastPosition);
                }
            }
        }

        private static void OnTick(EventArgs args)
        {

        }
    }
}
