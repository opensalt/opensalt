import { describe, it, expect, vi } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';

const mockRoute = { query: {} };
const routerReplace = vi.fn();

vi.mock('vue-router', () => ({
  useRoute: () => mockRoute,
  useRouter: () => ({ replace: routerReplace })
}));

vi.mock('@/components/crosswalk/CreateTab.vue', () => ({
  default: { template: '<div class="create-tab">Create</div>' }
}));
vi.mock('@/components/crosswalk/ReviewTab.vue', () => ({
  default: { template: '<div class="review-tab">Review</div>' }
}));

describe('CrosswalkView', () => {
  it('defaults to create tab', async () => {
    mockRoute.query = {};
    const { default: CrosswalkView } = await import('@/components/crosswalk/CrosswalkView.vue');
    const wrapper = mount(CrosswalkView);
    await flushPromises();
    expect(wrapper.find('.create-tab').exists()).toBe(true);
    expect(wrapper.find('.review-tab').exists()).toBe(false);
  });

  it('shows review tab when query param is review', async () => {
    mockRoute.query = { tab: 'review' };
    const { default: CrosswalkView } = await import('@/components/crosswalk/CrosswalkView.vue');
    const wrapper = mount(CrosswalkView);
    await flushPromises();
    expect(wrapper.find('.review-tab').exists()).toBe(true);
    expect(wrapper.find('.create-tab').exists()).toBe(false);
  });
});
