import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent } from 'vue';
import EditDocModal from '@/components/tree/modals/EditDocModal.vue';

// Stub the heavy/async child components and the overlay so we can drive wiring.
const ExtensionsEditorStub = defineComponent({
  name: 'ExtensionsEditor',
  emits: ['update:modelValue', 'update:show'],
  template: '<div data-testid="stub-editor" />'
});

function mountDoc(props = {}) {
  return mount(EditDocModal, {
    props: { show: false, document: null, isAdmin: false, ...props },
    global: {
      stubs: {
        BaseModal: defineComponent({
          name: 'BaseModal',
          props: { isOpen: Boolean, title: String, size: String },
          template: '<div><slot /><template v-if="$slots.footer"><footer><slot name="footer" /></footer></template></div>'
        }),
        SubjectSelector: true,
        LicenseSelector: true,
        AdditionalFields: true,
        ExtensionsEditor: ExtensionsEditorStub
      }
    }
  });
}

describe('EditDocModal extensions integration', () => {
  it('renders an Edit extensions button', () => {
    const wrapper = mountDoc({ document: { identifier: 'd1', title: 'T', extensions: {} } });
    expect(wrapper.find('[data-testid="open-extensions"]').exists()).toBe(true);
  });

  it('includes extensions (reserved preserved + edited) in the saved payload', async () => {
    const doc = {
      identifier: 'd1',
      title: 'T',
      adoptionStatus: 'Draft',
      extensions: { 'salt:relatedFrameworks': ['uuid-1'], 'acme:note': 'old' }
    };
    const wrapper = mountDoc({ show: true, document: doc });

    // open the overlay
    await wrapper.find('[data-testid="open-extensions"]').trigger('click');
    // simulate the overlay applying an edited set (acme:note changed, new key added)
    const editor = wrapper.findComponent({ name: 'ExtensionsEditor' });
    editor.vm.$emit('update:modelValue', { 'acme:note': 'new', 'acme:added': true });

    // trigger save
    await wrapper.find('[data-testid="save-doc"]').trigger('click');

    const saved = wrapper.emitted('saved')[0][0];
    expect(saved.extensions).toEqual({
      'salt:relatedFrameworks': ['uuid-1'], // reserved preserved
      'acme:note': 'new',
      'acme:added': true
    });
  });
});
