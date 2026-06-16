import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

// Drive lifecycle hooks synchronously so the observer is created during the call
let mountedHooks = [];
let unmountedHooks = [];

vi.mock('vue', async () => {
  const actual = await vi.importActual('vue');
  return {
    ...actual,
    onMounted: (cb) => { mountedHooks.push(cb); },
    onBeforeUnmount: (cb) => { unmountedHooks.push(cb); }
  };
});

import { ref } from 'vue';
import { useInfiniteList } from '@/composables/useInfiniteList.js';

describe('useInfiniteList', () => {
  let observerConstructor;
  let observerInstances;

  beforeEach(() => {
    mountedHooks = [];
    unmountedHooks = [];
    observerInstances = [];
    const MockObserver = vi.fn(function (callback, options) {
      this.callback = callback;
      this.options = options;
      this.observe = vi.fn();
      this.unobserve = vi.fn();
      this.disconnect = vi.fn();
      observerInstances.push(this);
    });
    observerConstructor = MockObserver;
    vi.stubGlobal('IntersectionObserver', MockObserver);
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  function create(getCount, options) {
    const result = useInfiniteList(getCount, options);
    mountedHooks.forEach(hook => hook());
    return result;
  }

  describe('initial state', () => {
    it('starts visibleCount at the default page size', () => {
      const { visibleCount } = create(() => 1000);
      expect(visibleCount.value).toBe(50);
    });

    it('starts visibleCount at a custom page size', () => {
      const { visibleCount } = create(() => 1000, { pageSize: 25 });
      expect(visibleCount.value).toBe(25);
    });
  });

  describe('revealMore', () => {
    it('grows visibleCount by the page size', () => {
      const { visibleCount, revealMore } = create(() => 1000, { pageSize: 50 });
      revealMore();
      expect(visibleCount.value).toBe(100);
    });

    it('does not exceed the total count', () => {
      const { visibleCount, revealMore } = create(() => 70, { pageSize: 50 });
      revealMore();
      expect(visibleCount.value).toBe(70);
    });

    it('does nothing when already at the total', () => {
      const { visibleCount, revealMore } = create(() => 30, { pageSize: 10 });
      revealMore();
      revealMore();
      revealMore(); // now at 30
      expect(visibleCount.value).toBe(30);
      revealMore(); // already at total -> no-op
      expect(visibleCount.value).toBe(30);
    });

    it('reflects a growing total when getCount changes', () => {
      const count = ref(0);
      const { visibleCount, revealMore } = create(() => count.value, { pageSize: 10 });
      expect(visibleCount.value).toBe(10);

      count.value = 45;
      revealMore();
      expect(visibleCount.value).toBe(20);
    });

    it('lands exactly on a page boundary without overshooting', () => {
      const { visibleCount, revealMore } = create(() => 20, { pageSize: 10 });
      revealMore();
      expect(visibleCount.value).toBe(20);
      revealMore(); // exactly at total -> stays
      expect(visibleCount.value).toBe(20);
    });

    it('accumulates across multiple calls up to the total', () => {
      const { visibleCount, revealMore } = create(() => 35, { pageSize: 10 });
      [20, 30, 35, 35].forEach((expected) => {
        revealMore();
        expect(visibleCount.value).toBe(expected);
      });
    });

    it('does not move when the total is zero', () => {
      const { visibleCount, revealMore } = create(() => 0, { pageSize: 10 });
      revealMore();
      expect(visibleCount.value).toBe(10);
    });

    it('grows without a known total (getCount returns null)', () => {
      const { visibleCount, revealMore } = create(() => null, { pageSize: 10 });
      revealMore();
      expect(visibleCount.value).toBe(20);
      revealMore();
      expect(visibleCount.value).toBe(30);
    });
  });

  describe('reset', () => {
    it('resets visibleCount back to the page size', () => {
      const { visibleCount, revealMore, reset } = create(() => 1000, { pageSize: 50 });
      revealMore();
      revealMore();
      expect(visibleCount.value).toBe(150);
      reset();
      expect(visibleCount.value).toBe(50);
    });
  });

  describe('IntersectionObserver wiring', () => {
    it('creates an observer with the container as root and given rootMargin', () => {
      const containerRef = ref({ id: 'scroll-box' });
      create(() => 1000, { containerRef, rootMargin: '150px' });

      expect(observerConstructor).toHaveBeenCalledTimes(1);
      const [, options] = observerConstructor.mock.calls[0];
      expect(options.root).toBe(containerRef.value);
      expect(options.rootMargin).toBe('150px');
    });

    it('uses the viewport (null root) when no container is provided', () => {
      create(() => 1000);
      const [, options] = observerConstructor.mock.calls[0];
      expect(options.root).toBeNull();
    });

    it('grows visibleCount when an intersecting entry is reported', () => {
      const { visibleCount } = create(() => 1000, { pageSize: 50 });
      const [callback] = observerConstructor.mock.calls[0];

      callback([{ isIntersecting: true }]);
      expect(visibleCount.value).toBe(100);
    });

    it('does not grow when no entry is intersecting', () => {
      const { visibleCount } = create(() => 1000, { pageSize: 50 });
      const [callback] = observerConstructor.mock.calls[0];

      callback([{ isIntersecting: false }]);
      expect(visibleCount.value).toBe(50);
    });
  });

  describe('bindSentinel', () => {
    it('observes the element when an observer exists', () => {
      const el = {};
      const { bindSentinel } = create(() => 1000);
      bindSentinel(el);

      expect(observerInstances[0].observe).toHaveBeenCalledWith(el);
    });

    it('ignores a null element', () => {
      const { bindSentinel } = create(() => 1000);
      expect(() => bindSentinel(null)).not.toThrow();
      expect(observerInstances[0].observe).not.toHaveBeenCalled();
    });

    it('observes elements bound before mount once mounted', () => {
      // Simulate binding before the mounted hook fires
      const result = useInfiniteList(() => 1000);
      const el = {};
      result.bindSentinel(el);
      expect(observerInstances).toHaveLength(0);

      mountedHooks.forEach(hook => hook());
      expect(observerInstances[0].observe).toHaveBeenCalledWith(el);
    });
  });

  describe('cleanup', () => {
    it('disconnects the observer on unmount', () => {
      create(() => 1000);
      unmountedHooks.forEach(hook => hook());

      expect(observerInstances[0].disconnect).toHaveBeenCalledTimes(1);
    });
  });

  describe('environment without IntersectionObserver', () => {
    it('does not throw and never creates an observer', () => {
      vi.unstubAllGlobals();
      // jsdom does not provide IntersectionObserver by default
      const result = useInfiniteList(() => 1000);
      expect(() => mountedHooks.forEach(hook => hook())).not.toThrow();
      expect(observerInstances).toHaveLength(0);
      expect(() => result.bindSentinel({})).not.toThrow();
    });
  });
});
