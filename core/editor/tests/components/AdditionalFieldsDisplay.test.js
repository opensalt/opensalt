import { describe, it, expect, beforeEach, vi } from 'vitest';
import { ref } from 'vue';
import { mount } from '@vue/test-utils';
import AdditionalFieldsDisplay from '@/components/tree/panels/AdditionalFieldsDisplay.vue';

// Shared ref for the mock composable
const mockFieldDefinitionsRef = ref([]);

vi.mock('@/composables/useAdditionalFields.js', () => ({
  useAdditionalFields: vi.fn(() => ({
    fieldDefinitions: mockFieldDefinitionsRef,
    fetchFields: vi.fn(async () => []),
  })),
}));

describe('AdditionalFieldsDisplay', () => {
  beforeEach(() => {
    mockFieldDefinitionsRef.value = [];
  });

  it('does not render when no field definitions or values', () => {
    const wrapper = mount(AdditionalFieldsDisplay, {
      props: { additionalFields: {}, scope: 'item' },
    });
    expect(wrapper.find('.additional-fields-section').exists()).toBe(false);
  });

  it('renders section when fields have values', async () => {
    mockFieldDefinitionsRef.value = [
      { name: 'customField', displayName: 'Custom Field' },
    ];
    const wrapper = mount(AdditionalFieldsDisplay, {
      props: {
        additionalFields: { customField: 'Some value' },
        scope: 'item',
      },
    });
    expect(wrapper.find('.additional-fields-section').exists()).toBe(true);
    expect(wrapper.text()).toContain('Custom Field');
    expect(wrapper.text()).toContain('Some value');
  });

  it('hides fields that have no value', async () => {
    mockFieldDefinitionsRef.value = [
      { name: 'field1', displayName: 'Field 1' },
      { name: 'field2', displayName: 'Field 2' },
    ];
    const wrapper = mount(AdditionalFieldsDisplay, {
      props: {
        additionalFields: { field1: 'value1' },
        scope: 'item',
      },
    });
    expect(wrapper.text()).toContain('Field 1');
    expect(wrapper.text()).toContain('value1');
    expect(wrapper.text()).not.toContain('Field 2');
  });

  it('uses field name when displayName is absent', async () => {
    mockFieldDefinitionsRef.value = [
      { name: 'rawName' },
    ];
    const wrapper = mount(AdditionalFieldsDisplay, {
      props: {
        additionalFields: { rawName: 'val' },
        scope: 'item',
      },
    });
    expect(wrapper.text()).toContain('rawName');
  });
});
