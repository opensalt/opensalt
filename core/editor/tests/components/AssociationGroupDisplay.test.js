import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import AssociationGroupDisplay from '@/components/association/AssociationGroupDisplay.vue';

// Must mirror the component's internal page size (AssociationGroupDisplay.vue).
const PAGE_SIZE = 10;

// Stub the row so the test doesn't pull in the full cross-framework fetch chain.
const AssociationItemStub = {
  name: 'AssociationItem',
  template: '<div class="assoc-stub" />',
  props: ['association']
};

describe('AssociationGroupDisplay incremental mounting', () => {
  let observerCallback;
  let observeMock;

  beforeEach(() => {
    setActivePinia(createPinia());
    observeMock = vi.fn();
    const MockObserver = vi.fn(function (callback) {
      observerCallback = callback;
      this.observe = observeMock;
    });
    vi.stubGlobal('IntersectionObserver', MockObserver);
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  const makeAssociations = (n) => Array.from({ length: n }, (_, i) => ({
    identifier: `a-${i}`,
    associationType: 'isRelatedTo',
    destinationNodeURI: { identifier: `d-${i}` }
  }));

  const mountGroup = (associations) => mount(AssociationGroupDisplay, {
    props: {
      associationType: 'isRelatedTo',
      associations,
      direction: 'normal'
    },
    global: {
      plugins: [createPinia()],
      stubs: { AssociationItem: AssociationItemStub }
    }
  });

  // Helper: fire intersection and flush.
  const reveal = async (wrapper) => {
    observerCallback([{ isIntersecting: true }]);
    await wrapper.vm.$nextTick();
  };

  describe('initial render boundaries', () => {
    it('renders nothing and no controls for an empty list', () => {
      const wrapper = mountGroup(makeAssociations(0));
      expect(wrapper.findAll('.assoc-stub')).toHaveLength(0);
      expect(wrapper.find('.infinite-sentinel').exists()).toBe(false);
      expect(wrapper.text()).not.toContain('Showing');
      expect(observeMock).not.toHaveBeenCalled();
    });

    it('renders all rows when fewer than a page', () => {
      const wrapper = mountGroup(makeAssociations(PAGE_SIZE - 3));
      expect(wrapper.findAll('.assoc-stub')).toHaveLength(PAGE_SIZE - 3);
      expect(wrapper.find('.infinite-sentinel').exists()).toBe(false);
      expect(wrapper.text()).not.toContain('Showing');
    });

    it('renders exactly one page and no controls at the page boundary', () => {
      const wrapper = mountGroup(makeAssociations(PAGE_SIZE));
      expect(wrapper.findAll('.assoc-stub')).toHaveLength(PAGE_SIZE);
      expect(wrapper.find('.infinite-sentinel').exists()).toBe(false);
      expect(wrapper.text()).not.toContain('Showing');
      expect(observeMock).not.toHaveBeenCalled();
    });

    it('renders only the first page when truncated', () => {
      const wrapper = mountGroup(makeAssociations(120));
      expect(wrapper.findAll('.assoc-stub')).toHaveLength(PAGE_SIZE);
    });

    it('shows a "showing N of M" indicator and binds the sentinel while truncated', () => {
      const wrapper = mountGroup(makeAssociations(120));
      expect(wrapper.text()).toContain(`Showing ${PAGE_SIZE} of 120`);
      expect(wrapper.find('.infinite-sentinel').exists()).toBe(true);
      expect(observeMock).toHaveBeenCalled();
    });

    it('renders just the first page when one item over the boundary', () => {
      const wrapper = mountGroup(makeAssociations(PAGE_SIZE + 1));
      expect(wrapper.findAll('.assoc-stub')).toHaveLength(PAGE_SIZE);
      expect(wrapper.text()).toContain(`Showing ${PAGE_SIZE} of ${PAGE_SIZE + 1}`);
    });
  });

  describe('reveal on intersection', () => {
    it('grows by one page on a single intersecting entry', async () => {
      const wrapper = mountGroup(makeAssociations(120));
      expect(wrapper.findAll('.assoc-stub')).toHaveLength(PAGE_SIZE);

      await reveal(wrapper);

      expect(wrapper.findAll('.assoc-stub')).toHaveLength(PAGE_SIZE * 2);
      expect(wrapper.text()).toContain(`Showing ${PAGE_SIZE * 2} of 120`);
    });

    it('does not grow on a non-intersecting entry', async () => {
      const wrapper = mountGroup(makeAssociations(120));
      observerCallback([{ isIntersecting: false }]);
      await wrapper.vm.$nextTick();

      expect(wrapper.findAll('.assoc-stub')).toHaveLength(PAGE_SIZE);
    });

    it('reveals the final partial page and removes the sentinel', async () => {
      const wrapper = mountGroup(makeAssociations(PAGE_SIZE + 3));
      await reveal(wrapper); // 10 -> 13

      expect(wrapper.findAll('.assoc-stub')).toHaveLength(PAGE_SIZE + 3);
      expect(wrapper.find('.infinite-sentinel').exists()).toBe(false);
      expect(wrapper.text()).not.toContain('Showing');
    });

    it('lands exactly on a page boundary and removes the sentinel', async () => {
      const wrapper = mountGroup(makeAssociations(PAGE_SIZE * 2));
      await reveal(wrapper); // 10 -> 20 (exact total)

      expect(wrapper.findAll('.assoc-stub')).toHaveLength(PAGE_SIZE * 2);
      expect(wrapper.find('.infinite-sentinel').exists()).toBe(false);
    });

    it('drains a large list across multiple intersections', async () => {
      const wrapper = mountGroup(makeAssociations(35));
      for (let i = 0; i < 4; i++) {
        await reveal(wrapper);
      }
      expect(wrapper.findAll('.assoc-stub')).toHaveLength(35);
      expect(wrapper.find('.infinite-sentinel').exists()).toBe(false);
      // one more intersection must be a no-op
      await reveal(wrapper);
      expect(wrapper.findAll('.assoc-stub')).toHaveLength(35);
    });
  });

  describe('reset on prop change', () => {
    it('returns to the first page when the associations array is replaced', async () => {
      const wrapper = mountGroup(makeAssociations(120));
      await reveal(wrapper);
      await reveal(wrapper);
      expect(wrapper.findAll('.assoc-stub')).toHaveLength(PAGE_SIZE * 3);

      await wrapper.setProps({ associations: makeAssociations(45) });

      expect(wrapper.findAll('.assoc-stub')).toHaveLength(PAGE_SIZE);
      expect(wrapper.text()).toContain(`Showing ${PAGE_SIZE} of 45`);
    });
  });
});
