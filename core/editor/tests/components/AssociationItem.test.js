import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createPinia } from 'pinia';
import AssociationItem from '@/components/association/AssociationItem.vue';
// Preload the lazily imported renderer so the component's dynamic import
// resolves from cache within flushPromises during tests.
import '@/utils/render-md.js';

// Mutable state the mocked composable reads from, so each test controls what
// the cross-framework item lookup "found".
const mockState = vi.hoisted(() => ({
  itemData: { value: null },
  itemTitle: { value: '' },
  nodeURI: { value: { identifier: 'dest-1' } },
  itemIdentifier: { value: 'dest-1' },
  isLoading: { value: false },
}));

vi.mock('@/composables/useCrossFrameworkItem', () => ({
  useCrossFrameworkItem: () => ({
    isLoading: mockState.isLoading,
    fetchError: { value: null },
    itemData: mockState.itemData,
    itemTitle: mockState.itemTitle,
    frameworkTitle: { value: '' },
    isCrossFramework: { value: false },
    itemIdentifier: mockState.itemIdentifier,
    targetTypeInfo: { value: { isCase: true } },
    nodeURI: mockState.nodeURI,
    resolvedFrameworkId: { value: null },
    displayedFrameworkId: { value: null },
    loadExternalItem: vi.fn(),
    reload: vi.fn(),
  }),
}));

vi.mock('@/composables/useRelatedFrameworksQueue', () => ({
  useRelatedFrameworksQueue: () => ({
    getQueueStatus: vi.fn(() => 'not_queued'),
  }),
}));

vi.mock('@/composables/useAssociationPermissions', () => ({
  useAssociationPermissions: () => ({
    isAssociationFromDifferentDisplayedFramework: { value: false },
    canManageAssociation: { value: false },
    sourceFrameworkTitle: { value: '' },
  }),
}));

const LONG_STATEMENT =
  'Interpret complicated expressions by viewing one or more of their parts as a single entity. ' +
  'For example, interpret P(1+r)^n as the product of P and a factor not depending on P.';

const mountItem = async () => {
  const wrapper = mount(AssociationItem, {
    props: {
      association: {
        identifier: 'assoc-1',
        associationType: 'exactMatchOf',
        destinationNodeURI: { identifier: 'dest-1' },
      },
    },
    global: {
      plugins: [createPinia()],
    },
  });
  // displayTitle uses the lazily imported markdown renderer, loaded in onMounted
  await flushPromises();
  return wrapper;
};

const titleEl = (wrapper) => wrapper.find('.association-title-clamp');

describe('AssociationItem display title', () => {
  beforeEach(() => {
    mockState.itemData.value = null;
    mockState.itemTitle.value = '';
    mockState.nodeURI.value = { identifier: 'dest-1' };
    mockState.itemIdentifier.value = 'dest-1';
    mockState.isLoading.value = false;
  });

  it('renders the title element with the line-clamp class', async () => {
    mockState.itemTitle.value = 'Plain title';
    const wrapper = await mountItem();
    expect(titleEl(wrapper).exists()).toBe(true);
    expect(titleEl(wrapper).text()).toBe('Plain title');
  });

  it('renders a long fullStatement in full instead of truncating at 100 characters', async () => {
    mockState.itemData.value = { fullStatement: LONG_STATEMENT };
    const wrapper = await mountItem();
    const text = titleEl(wrapper).text();
    expect(text).toContain('not depending on P.');
    expect(text).not.toContain('...');
  });

  it('renders inline math from fullStatement through katex', async () => {
    mockState.itemData.value = { fullStatement: 'Interpret $P(1+r)^n$ as the product.' };
    const wrapper = await mountItem();
    expect(titleEl(wrapper).html()).toContain('class="katex"');
  });

  it('renders allowed inline HTML tags from fullStatement', async () => {
    mockState.itemData.value = {
      fullStatement: 'For example, interpret <em>P(1+r)<sup>n</sup></em> as the product.',
    };
    const wrapper = await mountItem();
    const html = titleEl(wrapper).html();
    expect(html).toContain('<em>');
    expect(html).toContain('<sup>');
  });

  it('renders abbreviatedStatement through the markdown renderer as well', async () => {
    mockState.itemData.value = { abbreviatedStatement: 'Shortened $x^2$ statement' };
    const wrapper = await mountItem();
    expect(titleEl(wrapper).html()).toContain('class="katex"');
  });

  it('renders markdown in the node title fallback when item data is unavailable', async () => {
    mockState.itemData.value = null;
    mockState.itemTitle.value = 'A.SSE.A1.1b: Interpret $P(1+r)^n$ as the product.';
    const wrapper = await mountItem();
    expect(titleEl(wrapper).html()).toContain('class="katex"');
    expect(titleEl(wrapper).text()).not.toContain('$P(1+r)^n$');
  });
});
