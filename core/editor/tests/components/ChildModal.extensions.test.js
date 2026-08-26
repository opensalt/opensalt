import { describe, it, expect, vi, afterEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { defineComponent } from 'vue';
import ChildModal from '@/components/tree/modals/item-types/ChildModal.vue';

vi.mock('@/services/api.js', () => ({
  api: {
    get: vi.fn().mockResolvedValue([])
  }
}));

const ExtensionsEditorStub = defineComponent({
  name: 'ExtensionsEditor',
  emits: ['update:modelValue', 'update:show'],
  template: '<div />'
});

function mountChild(props = {}) {
  return mount(ChildModal, {
    props: { show: false, ...props },
    global: {
      stubs: {
        EasyMDE: true,
        MultiSelect: true,
        SingleSelect: true,
        SubjectSelector: true,
        LicenseSelector: true,
        AdditionalFields: true,
        ExtensionsEditor: ExtensionsEditorStub
      }
    }
  });
}

describe('ChildModal extensions integration', () => {
  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('does not show the create form until setup finishes', async () => {
    let release;
    const gate = new Promise((resolve) => {
      release = resolve;
    });
    vi.stubGlobal('fetch', vi.fn(() => gate.then(() => ({
      ok: true,
      text: async () => JSON.stringify({ results: [] })
    }))));

    const wrapper = mountChild({ show: true });
    await wrapper.vm.$nextTick();

    expect(wrapper.find('#ls_item').exists()).toBe(false);

    release();
    await flushPromises();

    expect(wrapper.find('#ls_item').exists()).toBe(true);
    wrapper.unmount();
  });

  it('renders an Edit extensions button and merges edited extensions on update', async () => {
    const item = {
      identifier: 'i1',
      fullStatement: 'Statement',
      humanCodingScheme: '1',
      extensions: { 'salt:type': 'general', 'acme:x': 'old' }
    };
    const wrapper = mountChild({ item });

    expect(wrapper.find('[data-testid="open-extensions"]').exists()).toBe(true);
    await wrapper.find('[data-testid="open-extensions"]').trigger('click');

    const editor = wrapper.findComponent({ name: 'ExtensionsEditor' });
    editor.vm.$emit('update:modelValue', { 'acme:x': 'new', 'acme:y': 5 });

    await wrapper.find('[data-testid="save-item"]').trigger('click');

    const updated = wrapper.emitted('updated')[0][0];
    expect(updated.extensions).toEqual({ 'salt:type': 'general', 'acme:x': 'new', 'acme:y': 5 });
  });


  it('preserves existing extensions when saved without opening the overlay', async () => {
    const item = {
      identifier: 'i1',
      fullStatement: 'Statement',
      humanCodingScheme: '1',
      extensions: { 'salt:type': 'general', 'acme:keep': 'v', 'acme:num': 7 }
    };
    const wrapper = mountChild({ item });

    // Do NOT open the extensions overlay — just save.
    await wrapper.find('[data-testid="save-item"]').trigger('click');

    const updated = wrapper.emitted('updated')[0][0];
    expect(updated.extensions).toEqual({ 'salt:type': 'general', 'acme:keep': 'v', 'acme:num': 7 });
  });
});
