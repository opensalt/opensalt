import { describe, it, expect, vi, afterEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import ItemStatementPopover from '@/components/common/ItemStatementPopover.vue';

vi.mock('@/utils/markdownRenderer', () => ({
  renderMarkdown: (text) => `<p>${text}</p>`,
}));

describe('ItemStatementPopover', () => {
  let wrapper;

  afterEach(() => {
    if (wrapper) wrapper.unmount();
    document.body.querySelectorAll('.statement-popover').forEach(el => el.remove());
  });

  const mountPopover = (props = {}, slot = 'Label') => mount(ItemStatementPopover, {
    props: { statement: 'Hello world', ...props },
    slots: { default: slot },
    attachTo: document.body,
  });

  it('always renders its slot content', () => {
    wrapper = mountPopover({}, 'My Label');
    expect(wrapper.text()).toContain('My Label');
  });

  it('shows the popover after the hover delay', async () => {
    vi.useFakeTimers();
    wrapper = mountPopover();
    await wrapper.find('.item-statement-popover-trigger').trigger('mouseenter');
    await vi.advanceTimersByTimeAsync(500);
    await flushPromises();
    expect(document.body.querySelector('.statement-popover')).not.toBeNull();
    expect(document.body.querySelector('.statement-popover').innerHTML).toContain('Hello world');
    vi.useRealTimers();
  });

  it('hides the popover on mouseleave', async () => {
    vi.useFakeTimers();
    wrapper = mountPopover();
    await wrapper.find('.item-statement-popover-trigger').trigger('mouseenter');
    await vi.advanceTimersByTimeAsync(500);
    await flushPromises();
    expect(document.body.querySelector('.statement-popover')).not.toBeNull();
    await wrapper.find('.item-statement-popover-trigger').trigger('mouseleave');
    await flushPromises();
    expect(document.body.querySelector('.statement-popover')).toBeNull();
    vi.useRealTimers();
  });

  it('does not show the popover when disabled', async () => {
    vi.useFakeTimers();
    wrapper = mountPopover({ disabled: true });
    await wrapper.find('.item-statement-popover-trigger').trigger('mouseenter');
    await vi.advanceTimersByTimeAsync(500);
    await flushPromises();
    expect(document.body.querySelector('.statement-popover')).toBeNull();
    vi.useRealTimers();
  });

  it('does not show the popover when the statement is empty', async () => {
    vi.useFakeTimers();
    wrapper = mountPopover({ statement: '' });
    await wrapper.find('.item-statement-popover-trigger').trigger('mouseenter');
    await vi.advanceTimersByTimeAsync(500);
    await flushPromises();
    expect(document.body.querySelector('.statement-popover')).toBeNull();
    vi.useRealTimers();
  });

  it('cancels a pending popover when leaving before the delay', async () => {
    vi.useFakeTimers();
    wrapper = mountPopover();
    await wrapper.find('.item-statement-popover-trigger').trigger('mouseenter');
    await wrapper.find('.item-statement-popover-trigger').trigger('mouseleave');
    await vi.advanceTimersByTimeAsync(500);
    await flushPromises();
    expect(document.body.querySelector('.statement-popover')).toBeNull();
    vi.useRealTimers();
  });

  it('shows the popover on focus', async () => {
    vi.useFakeTimers();
    wrapper = mountPopover();
    await wrapper.find('.item-statement-popover-trigger').trigger('focus');
    await vi.advanceTimersByTimeAsync(500);
    await flushPromises();
    expect(document.body.querySelector('.statement-popover')).not.toBeNull();
    vi.useRealTimers();
  });

  it('hides the popover on blur', async () => {
    vi.useFakeTimers();
    wrapper = mountPopover();
    await wrapper.find('.item-statement-popover-trigger').trigger('focus');
    await vi.advanceTimersByTimeAsync(500);
    await flushPromises();
    expect(document.body.querySelector('.statement-popover')).not.toBeNull();
    await wrapper.find('.item-statement-popover-trigger').trigger('blur');
    await flushPromises();
    expect(document.body.querySelector('.statement-popover')).toBeNull();
    vi.useRealTimers();
  });
});
