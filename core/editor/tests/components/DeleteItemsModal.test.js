import { describe, it, expect, vi, afterEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import DeleteItemsModal from '@/components/tree/modals/DeleteItemsModal.vue';

describe('DeleteItemsModal', () => {
  let wrapper;

  afterEach(() => {
    if (wrapper) wrapper.unmount();
  });

  // 3 descendants: c1, g1 (grandchild), c2
  const nodeWithChildren = {
    identifier: 'parent',
    title: 'Parent',
    children: [
      { identifier: 'c1', title: 'C1', children: [{ identifier: 'g1', title: 'G1', children: [] }] },
      { identifier: 'c2', title: 'C2', children: [] }
    ]
  };

  const mountModal = (props = {}) => mount(DeleteItemsModal, {
    props: { show: true, items: [], deleteType: 'single', confirmHandler: () => Promise.resolve(), ...props },
    attachTo: document.body
  });

  it('with-children: disables delete until DELETE is typed', async () => {
    wrapper = mountModal({ items: [nodeWithChildren], deleteType: 'with-children' });
    const btn = wrapper.find('button.btn-delete');
    expect(btn.attributes('disabled')).toBeDefined();
    await wrapper.find('input[type="text"]').setValue('DELETE');
    expect(btn.attributes('disabled')).toBeUndefined();
  });

  it('with-children: shows the total descendant count', () => {
    wrapper = mountModal({ items: [nodeWithChildren], deleteType: 'with-children' });
    expect(wrapper.text()).toContain('3 descendant');
  });

  it('calls confirmHandler and emits hidden on success', async () => {
    const handler = vi.fn().mockResolvedValue(undefined);
    wrapper = mountModal({ items: [{ identifier: 'leaf', title: 'Leaf' }], deleteType: 'single', confirmHandler: handler });
    await wrapper.find('button.btn-delete').trigger('click');
    await flushPromises();
    expect(handler).toHaveBeenCalledWith({ items: [{ identifier: 'leaf', title: 'Leaf' }], deleteType: 'single' });
    expect(wrapper.emitted('hidden')).toBeTruthy();
  });

  it('shows the backend error and stays open on failure', async () => {
    const handler = vi.fn().mockRejectedValue(new Error('Cannot delete an item with children.'));
    wrapper = mountModal({ items: [{ identifier: 'x', title: 'X' }], deleteType: 'single', confirmHandler: handler });
    await wrapper.find('button.btn-delete').trigger('click');
    await flushPromises();
    expect(wrapper.text()).toContain('Cannot delete an item with children.');
    expect(wrapper.emitted('hidden')).toBeFalsy();
  });

  it('does not invoke confirmHandler twice on a double click', async () => {
    let resolveHandler;
    const handler = vi.fn(() => new Promise((resolve) => { resolveHandler = resolve; }));
    wrapper = mountModal({ items: [{ identifier: 'leaf', title: 'Leaf' }], deleteType: 'single', confirmHandler: handler });
    const btn = wrapper.find('button.btn-delete');
    await btn.trigger('click');
    await btn.trigger('click'); // second click while first is still pending
    expect(handler).toHaveBeenCalledTimes(1);
    resolveHandler();
    await flushPromises();
  });
});
