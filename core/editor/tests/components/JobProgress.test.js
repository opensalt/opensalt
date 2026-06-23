import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { ref } from 'vue';
import JobProgress from '@/components/crosswalk/JobProgress.vue';

const jobState = ref({
  status: 'running',
  progress: { total: 100, processed: 50, matched: 40, exact_match_items: 10, related_items: 30, skipped_no_embedding: 0, skipped_below_threshold: 0, failed: 0 }
});

vi.mock('@/composables/useCrosswalkJob.js', () => ({
  useCrosswalkJob: () => ({
    jobState,
    startJob: vi.fn(),
    resumeJob: vi.fn(),
    cancelJob: vi.fn(),
  })
}));

describe('JobProgress', () => {
  it('shows progress bar when running', () => {
    jobState.value = {
      status: 'running',
      progress: { total: 100, processed: 50, matched: 40, exact_match_items: 10, related_items: 30, skipped_no_embedding: 0, skipped_below_threshold: 0, failed: 0 }
    };
    const wrapper = mount(JobProgress);
    expect(wrapper.find('.progress-bar').exists()).toBe(true);
    expect(wrapper.text()).toContain('Processing item 50 of 100');
  });

  it('shows all three non-matched categories while running', () => {
    jobState.value = {
      status: 'running',
      progress: { total: 100, processed: 50, matched: 40, exact_match_items: 10, related_items: 30, skipped_no_embedding: 5, skipped_below_threshold: 3, failed: 1 }
    };
    const wrapper = mount(JobProgress);
    expect(wrapper.text()).toContain('Items with no embedding: 5');
    expect(wrapper.text()).toContain('Items below threshold: 3');
    expect(wrapper.text()).toContain('Items failed: 1');
  });

  it('emits cancel when cancel button clicked', async () => {
    jobState.value = {
      status: 'running',
      progress: { total: 100, processed: 50, matched: 40, exact_match_items: 10, related_items: 30, skipped_no_embedding: 0, skipped_below_threshold: 0, failed: 0 }
    };
    const wrapper = mount(JobProgress);
    await wrapper.find('.btn-outline-danger').trigger('click');
    expect(wrapper.emitted('cancel')).toBeTruthy();
  });

  it('shows the non-matched breakdown in the completion summary', () => {
    jobState.value = {
      status: 'completed',
      progress: { total: 10, processed: 10, matched: 4, exact_match_items: 1, related_items: 3, skipped_no_embedding: 2, skipped_below_threshold: 3, failed: 1 }
    };
    const wrapper = mount(JobProgress);
    expect(wrapper.text()).toContain('4 matches created from 10 origin items');
    expect(wrapper.text()).toContain('Items with no embedding: 2');
    expect(wrapper.text()).toContain('Items below threshold: 3');
    expect(wrapper.text()).toContain('Items failed: 1');
  });

  it('hides non-matched categories while running when counts are 0', () => {
    jobState.value = {
      status: 'running',
      progress: { total: 100, processed: 50, matched: 40, exact_match_items: 10, related_items: 30, skipped_no_embedding: 0, skipped_below_threshold: 0, failed: 0 }
    };
    const wrapper = mount(JobProgress);
    expect(wrapper.text()).not.toContain('Items with no embedding');
    expect(wrapper.text()).not.toContain('Items below threshold');
    expect(wrapper.text()).not.toContain('Items failed');
  });

  it('omits the completion breakdown when nothing was non-matched', () => {
    jobState.value = {
      status: 'completed',
      progress: { total: 10, processed: 10, matched: 10, exact_match_items: 4, related_items: 6, skipped_no_embedding: 0, skipped_below_threshold: 0, failed: 0 }
    };
    const wrapper = mount(JobProgress);
    expect(wrapper.text()).not.toContain('Items with no embedding');
    expect(wrapper.text()).not.toContain('Items below threshold');
    expect(wrapper.text()).not.toContain('Items failed');
  });
});
