namespace EloBuddy.SDK.Menu.Values
{
    public interface IValue<T> : ISerializeable
    {
        event ValueBase<T>.ValueChangeHandler OnValueChange;

        T CurrentValue { get; set; }
    }
}
