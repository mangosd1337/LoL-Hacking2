using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading;
using EloBuddy.Loader.Data;
using EloBuddy.Loader.Globals;
using EloBuddy.Loader.Logger;

namespace EloBuddy.Loader.Routines
{
    public static class AddonUpdateRoutine
    {
        private static readonly object SyncLock = new object();
        private static long _threadsWorking;

        public static bool IsRunning
        {
            get { return _threadsWorking > 0; }
        }

        public static void UpdateAddons(ElobuddyAddon[] addons, bool compileOnly = false)
        {
            if (IsRunning)
            {
                return;
            }

            Log.Instance.DoLog("Updating Elobuddy addons.");
            Events.RaiseOnAddonUpdatePrepare(EventArgs.Empty);

            // Group addons based on repository directory 
            var groups = from addon in addons
                         group addon by addon.GetRemoteAddonRepositoryDirectory()
                         into newGroup
                         orderby newGroup.Key
                         select newGroup;

            var threadDictionary = new Dictionary<AddonUpdateThread, ElobuddyAddon[]>();

            foreach (var group in groups)
            {
                var thread = new AddonUpdateThread();
                threadDictionary.Add(thread, group.ToArray());
            }

            // Update UI before starting
            foreach (var t in threadDictionary.Values)
            {
                var addon = t.FirstOrDefault();
                if (addon != null)
                {
                    addon.SetState(addon.IsLocal
                        ? AddonState.WaitingForCompile
                        : compileOnly ? AddonState.WaitingForCompile : AddonState.WaitingForUpdate);
                }

                for (var i = 1; i < t.Length; i++)
                {
                    t[i].SetState(AddonState.WaitingForCompile);
                }
            }

            var compileQueue = new Queue<ElobuddyAddon>();
            _threadsWorking = threadDictionary.Count + 1;

            foreach (var t in threadDictionary)
            {
                t.Key.Start(args =>
                {
                    var _addons = args as ElobuddyAddon[];
                    var _addon = _addons.FirstOrDefault();

                    if (_addon != null)
                    {
                        if (!_addon.IsLocal && !compileOnly)
                        {
                            _addon.SetState(AddonState.Updating);
                            _addon.Update(false, false);
                        }

                        _addon.SetState(AddonState.WaitingForCompile);
                    }

                    lock (SyncLock)
                    {
                        foreach (var a in _addons)
                        {
                            compileQueue.Enqueue(a);
                        }
                    }

                    Interlocked.Decrement(ref _threadsWorking);
                }, t.Value);
            }

            new AddonUpdateThread().Start(args =>
            {
                while (true)
                {
                    ElobuddyAddon _addon = null;

                    lock (SyncLock)
                    {
                        if (compileQueue.Count > 0)
                        {
                            _addon = compileQueue.Dequeue();
                        }
                    }

                    if (_addon != null)
                    {
                        _addon.SetState(AddonState.Compiling);
                        _addon.Compile();
                        _addon.RefreshDisplay();
                    }

                    lock (SyncLock)
                    {
                        if (Interlocked.Read(ref _threadsWorking) == 1 && compileQueue.Count == 0)
                        {
                            break;
                        }
                    }

                    Thread.Sleep(10);
                }

                Events.RaiseOnAddonUpdateFinished(EventArgs.Empty);
                Log.Instance.DoLog("Finished updating Elobuddy addons.");

                Interlocked.Decrement(ref _threadsWorking);
            }, null);
        }

        private class AddonUpdateThread
        {
            private Thread _thread;

            internal delegate void UpdateThreadDelegate(object args);

            public bool IsActive
            {
                get { return _thread != null && _thread.IsAlive; }
            }

            public void Start(UpdateThreadDelegate handler, object args)
            {
                if (_thread == null || !_thread.IsAlive)
                {
                    _thread = new Thread(() => { handler.Invoke(args); }) { IsBackground = true };
                    _thread.Start();
                }
            }
        }
    }
}
