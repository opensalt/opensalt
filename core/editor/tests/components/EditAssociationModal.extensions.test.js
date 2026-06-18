import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent } from 'vue';
import EditAssociationModal from '@/components/association/EditAssociationModal.vue';

const ExtensionsEditorStub = defineComponent({
  name: 'ExtensionsEditor',
  props: ['modelValue', 'show', 'entityLabel', 'reservedKeys'],
  emits: ['update:modelValue', 'update:show'],
  template: '<div />'
});

function mountAssoc(props = {}) {
  return mount(EditAssociationModal, {
    props: {
      show: false,
      mode: 'edit',
      association: {
        identifier: 'assoc-1',
        associationType: 'related',
        originNodeURI: { identifier: 'a', title: 'A', uri: 'u' },
        destinationNodeURI: { identifier: 'b', title: 'B', uri: 'u' },
        extensions: { 'crosswalk:status': 'approved', 'acme:note': 'old' }
      },
      ...props
    },
    global: {
      stubs: {
        AssociationItemDisplay: true,
        DirectionSwitchButton: true,
        AssociationTypeSelector: defineComponent({ name: 'AssociationTypeSelector', template: '<div />' }),
        ExemplarFields: true,
        DestinationFields: true,
        AdditionalFields: true,
        ExtensionsEditor: ExtensionsEditorStub
      }
    }
  });
}

describe('EditAssociationModal extensions integration', () => {
  it('preserves reserved crosswalk keys and merges edited extensions on update', async () => {
    const wrapper = mountAssoc();

    await wrapper.find('[data-testid="open-extensions"]').trigger('click');
    const editor = wrapper.findComponent({ name: 'ExtensionsEditor' });
    expect(editor.props('reservedKeys')).toEqual(expect.arrayContaining(['crosswalk:status']));
    editor.vm.$emit('update:modelValue', { 'acme:note': 'new' });

    await wrapper.find('[data-testid="save-association"]').trigger('click');

    const updated = wrapper.emitted('updated')[0][0];
    expect(updated.extensions).toEqual({
      'crosswalk:status': 'approved',
      'acme:note': 'new'
    });
  });


  it('preserves existing extensions when saved without opening the overlay', async () => {
    const wrapper = mountAssoc();

    // Do NOT open the extensions overlay — just save.
    await wrapper.find('[data-testid="save-association"]').trigger('click');

    const updated = wrapper.emitted('updated')[0][0];
    // crosswalk:status (reserved) + acme:note (editable) both preserved
    expect(updated.extensions).toEqual({
      'crosswalk:status': 'approved',
      'acme:note': 'old'
    });
  });
});
