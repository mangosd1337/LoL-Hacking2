namespace EloBuddy.Loader.Protections
{
    internal abstract class Protection
    {
        public abstract string Name { get; }
        protected internal abstract void Initialize();
    }
}
