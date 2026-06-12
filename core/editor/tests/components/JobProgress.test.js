import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { ref } from 'vue';
import JobProgress from '@/components/crosswalk/JobProgress.vue';

vi.mock('@/composables/useCrosswalkJob.js', () => ({
  useCrosswalkJob: () => ({
    jobState: ref({
      status: 'running',
      progress: { total: 100, processed: 50, matched: 40, exact_match_items: 10, related_items: 30, skipped: 5, failed: 0 }
    }),
    startJob: vi.fn(),
    resumeJob: vi.fn(),
    cancelJob: vi.fn(),
  })
}));

describe('JobProgress', () => {
  it('shows progress bar when running', () => {
    const wrapper = mount(JobProgress);
    expect(wrapper.find('.progress-bar').exists()).toBe(true);
    expect(wrapper.text()).toContain('Processing item 50 of 100');
  });

  it('emits cancel when cancel button clicked', async () => {
    const wrapper = mount(JobProgress);
    await wrapper.find('.btn-outline-danger').trigger('click');
    expect(wrapper.emitted('cancel')).toBeTruthy();
  });
});
