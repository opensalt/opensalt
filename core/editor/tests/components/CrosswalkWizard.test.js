import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import CrosswalkWizard from '@/components/crosswalk/CrosswalkWizard.vue';

const mockGet = vi.fn();
const mockPost = vi.fn();

vi.mock('@/services/api.js', () => ({
  api: {
    get: (...args) => mockGet(...args),
    post: (...args) => mockPost(...args),
  }
}));

const mockRoute = { params: { frameworkId: 'uuid-crosswalk-42' } };
const mockRouter = { push: vi.fn() };

vi.mock('vue-router', () => ({
  useRoute: () => mockRoute,
  useRouter: () => mockRouter,
}));

vi.mock('@/stores/documentStore', () => ({
  useDocumentStore: vi.fn(() => ({
    documents: [
      { id: 42, identifier: 'uuid-crosswalk-42', title: 'Crosswalk Framework', creator: 'Test Org' },
      { id: 99, identifier: 'uuid-99', title: 'Math Standards', creator: 'State Board' },
      { id: 100, identifier: 'uuid-100', title: 'ELA Standards', creator: 'State Board' },
      { id: 101, identifier: 'uuid-101', title: 'Science Framework', creator: 'National Org' },
    ],
    fetchDocuments: vi.fn(),
  })),
}));

describe('CrosswalkWizard', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    mockGet.mockReset();
    mockPost.mockReset();
  });

  it('disables submit when origin is same as destination', async () => {
    const wrapper = mount(CrosswalkWizard);
    await flushPromises();

    wrapper.vm.form.originIdentifier = 'uuid-99';
    wrapper.vm.form.destinationIdentifier = 'uuid-99';
    await wrapper.vm.$nextTick();

    const submitBtn = wrapper.find('[data-testid="create-crosswalk-btn"]');
    expect(submitBtn.attributes('disabled')).toBeDefined();
  });

  it('enables submit when origin differs from destination', async () => {
    const wrapper = mount(CrosswalkWizard);
    await flushPromises();

    wrapper.vm.form.originIdentifier = 'uuid-99';
    wrapper.vm.form.destinationIdentifier = 'uuid-100';
    await wrapper.vm.$nextTick();

    const submitBtn = wrapper.find('[data-testid="create-crosswalk-btn"]');
    expect(submitBtn.attributes('disabled')).toBeUndefined();
  });

  it('calls estimate API with selected origin and destination', async () => {
    mockGet.mockResolvedValueOnce({
      origin_items_with_embeddings: 1832,
    });

    const wrapper = mount(CrosswalkWizard);
    await flushPromises();

    wrapper.vm.form.originIdentifier = 'uuid-99';
    wrapper.vm.form.destinationIdentifier = 'uuid-100';
    await wrapper.vm.$nextTick();

    await wrapper.find('[data-testid="preview-btn"]').trigger('click');
    await flushPromises();

    expect(mockGet).toHaveBeenCalledWith(
      expect.stringContaining('/api/vector-search/crosswalk/estimate?origin=uuid-99&destination=uuid-100')
    );
  });

  it('emits create with crosswalkId from route', async () => {
    const wrapper = mount(CrosswalkWizard);
    await flushPromises();

    wrapper.vm.form.originIdentifier = 'uuid-99';
    wrapper.vm.form.destinationIdentifier = 'uuid-100';
    await wrapper.vm.$nextTick();

    await wrapper.find('[data-testid="create-crosswalk-btn"]').trigger('click');

    expect(wrapper.emitted('create')).toBeTruthy();
    expect(wrapper.emitted('create')[0][0]).toEqual(
      expect.objectContaining({
        originIdentifier: 'uuid-99',
        destinationIdentifier: 'uuid-100',
        crosswalkIdentifier: 'uuid-crosswalk-42',
        threshold: 0.75,
        exactMatchThreshold: 0.90,
      })
    );
  });

  it('renders framework selectors grouped by creator', async () => {
    const wrapper = mount(CrosswalkWizard);
    await flushPromises();

    // Scope to the origin selector — there are 2 selectors (origin + destination)
    const originSelect = wrapper.find('#originFramework');
    const optgroups = originSelect.findAll('optgroup');
    // Documents have 3 creators: 'National Org', 'State Board', 'Test Org' (sorted alphabetically)
    expect(optgroups).toHaveLength(3);

    // First group alphabetically: 'National Org'
    expect(optgroups[0].attributes('label')).toBe('National Org');
    // Second: 'State Board' with 2 docs
    expect(optgroups[1].attributes('label')).toBe('State Board');
    // Third: 'Test Org' (the crosswalk framework's creator)
    expect(optgroups[2].attributes('label')).toBe('Test Org');
  });

  it('sorts documents within each creator group by title', async () => {
    const wrapper = mount(CrosswalkWizard);
    await flushPromises();

    const originSelect = wrapper.find('#originFramework');
    const optgroups = originSelect.findAll('optgroup');
    // 'State Board' group should have 'ELA Standards' then 'Math Standards'
    const stateBoardGroup = optgroups[1];
    const options = stateBoardGroup.findAll('option');
    expect(options[0].text()).toContain('ELA Standards');
    expect(options[1].text()).toContain('Math Standards');
  });
});
