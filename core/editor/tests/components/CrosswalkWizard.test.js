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

const mockRoute = { params: { frameworkId: '42' } };
const mockRouter = { push: vi.fn() };

vi.mock('vue-router', () => ({
  useRoute: () => mockRoute,
  useRouter: () => mockRouter,
}));

describe('CrosswalkWizard', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    mockGet.mockReset();
    mockPost.mockReset();
  });

  it('disables submit when origin and destination are the same', async () => {
    mockGet.mockResolvedValueOnce({ data: [] });

    const wrapper = mount(CrosswalkWizard);
    await flushPromises();

    const submitBtn = wrapper.find('[data-testid="create-crosswalk-btn"]');
    expect(submitBtn.attributes('disabled')).toBeDefined();
  });

  it('emits estimate event when preview is clicked', async () => {
    mockGet
      .mockResolvedValueOnce({ data: [] })
      .mockResolvedValueOnce({
        origin_items_with_embeddings: 1832,
        destination_items_with_embeddings: 2103,
      });

    const wrapper = mount(CrosswalkWizard);
    await flushPromises();

    wrapper.vm.form.originId = 'doc-1';
    wrapper.vm.form.destinationId = 'doc-2';
    await wrapper.vm.$nextTick();

    await wrapper.find('[data-testid="preview-btn"]').trigger('click');
    await flushPromises();

    expect(mockGet).toHaveBeenCalledWith(
      expect.stringContaining('/api/vector-search/crosswalk/estimate')
    );
  });
});
