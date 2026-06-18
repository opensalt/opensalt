import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent } from 'vue';
import OrganizationModal from '@/components/tree/modals/item-types/OrganizationModal.vue';

const ExtensionsEditorStub = defineComponent({
  name: 'ExtensionsEditor',
  props: ['modelValue', 'show', 'entityLabel', 'reservedKeys'],
  emits: ['update:modelValue', 'update:show'],
  template: '<div />'
});

function mountOrg(props = {}) {
  return mount(OrganizationModal, {
    props: { show: false, ...props },
    global: { stubs: { ExtensionsEditor: ExtensionsEditorStub } }
  });
}

describe('OrganizationModal extensions integration', () => {
  it('hides managed keys and merges only editable extensions on update', async () => {
    const item = {
      identifier: 'o1',
      abbreviatedStatement: 'Acme',
      fullStatement: 'Desc',
      extensions: {
        'salt:type': 'organization',
        'ceterms:agentType': 'orgType:Business',
        'ceterms:subjectWebpage': 'https://x.test',
        'acme:custom': 'old'
      }
    };
    const wrapper = mountOrg({ item });

    await wrapper.find('[data-testid="open-extensions"]').trigger('click');
    const editor = wrapper.findComponent({ name: 'ExtensionsEditor' });
    // Verify the overlay was told the managed keys are reserved
    expect(editor.props('reservedKeys')).toEqual(expect.arrayContaining([
      'salt:type', 'ceterms:agentType', 'ceterms:subjectWebpage'
    ]));

    // Apply an edited editable set
    editor.vm.$emit('update:modelValue', { 'acme:custom': 'new', 'acme:added': 9 });

    await wrapper.find('[data-testid="save-item"]').trigger('click');

    const updated = wrapper.emitted('updated')[0][0];
    expect(updated.extensions).toEqual({
      'salt:type': 'organization',
      'ceterms:agentType': 'orgType:Business',
      'ceterms:subjectWebpage': 'https://x.test',
      'acme:custom': 'new',
      'acme:added': 9
    });
  });
});
