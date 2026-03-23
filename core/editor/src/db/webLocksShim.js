const WEB_LOCKS_STATE_KEY = Symbol.for('opensalt.pglite.webLocksState');

function getNavigatorRef() {
  if (typeof globalThis.navigator !== 'undefined') {
    return globalThis.navigator;
  }

  const navigatorRef = {};
  Object.defineProperty(globalThis, 'navigator', {
    value: navigatorRef,
    configurable: true,
    writable: true,
  });
  return navigatorRef;
}

function getLockState() {
  if (!globalThis[WEB_LOCKS_STATE_KEY]) {
    globalThis[WEB_LOCKS_STATE_KEY] = new Map();
  }

  return globalThis[WEB_LOCKS_STATE_KEY];
}

export function ensureWebLocks() {
  const navigatorRef = getNavigatorRef();
  if (navigatorRef.locks && typeof navigatorRef.locks.request === 'function') {
    return navigatorRef.locks;
  }

  const state = getLockState();

  navigatorRef.locks = {
    request(name, optionsOrCallback, maybeCallback) {
      const callback = typeof optionsOrCallback === 'function' ? optionsOrCallback : maybeCallback;
      if (typeof callback !== 'function') {
        throw new TypeError('navigator.locks.request callback is required');
      }

      const key = String(name);
      const previous = state.get(key) || Promise.resolve();
      let releaseCurrent = () => {};
      const current = new Promise((resolve) => {
        releaseCurrent = resolve;
      });

      state.set(key, current);

      return previous
        .catch(() => {})
        .then(() => callback())
        .finally(() => {
          releaseCurrent();
          if (state.get(key) === current) {
            state.delete(key);
          }
        });
    },
  };

  return navigatorRef.locks;
}
