import { ref, onMounted, onBeforeUnmount } from 'vue';

/**
 * Incrementally mounts a list by growing a visible count as a sentinel
 * element becomes visible. Designed for a per-group scroll box whose rows
 * have variable heights.
 *
 * @param {() => number} getCount - returns the current total number of items
 * @param {object} [options]
 * @param {import('vue').Ref} [options.containerRef] - scroll container; null/viewport if omitted
 * @param {number} [options.pageSize=50] - items to reveal per batch
 * @param {string} [options.rootMargin='200px'] - pre-load distance before the sentinel
 */
export function useInfiniteList(getCount, options = {}) {
  const { containerRef, pageSize = 50, rootMargin = '200px' } = options;

  const visibleCount = ref(pageSize);
  let observer = null;
  const pending = new Set();

  function revealMore() {
    const total = getCount();
    if (total == null) {
      visibleCount.value += pageSize;
      return;
    }
    if (visibleCount.value >= total) {
      return;
    }
    visibleCount.value = Math.min(visibleCount.value + pageSize, total);
  }

  function onIntersect(entries) {
    if (Array.isArray(entries) && entries.some((entry) => entry && entry.isIntersecting)) {
      revealMore();
    }
  }

  function bindSentinel(el) {
    if (!el) {
      return;
    }
    if (observer) {
      observer.observe(el);
    } else {
      pending.add(el);
    }
  }

  function reset() {
    visibleCount.value = pageSize;
  }

  onMounted(() => {
    if (typeof IntersectionObserver === 'undefined') {
      return;
    }
    const root = containerRef && containerRef.value ? containerRef.value : null;
    observer = new IntersectionObserver(onIntersect, { root, rootMargin });
    pending.forEach((el) => observer.observe(el));
    pending.clear();
  });

  onBeforeUnmount(() => {
    if (observer && typeof observer.disconnect === 'function') {
      observer.disconnect();
    }
    observer = null;
  });

  return { visibleCount, revealMore, reset, bindSentinel };
}

export default useInfiniteList;
