import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import ExtensionsEditor from '@/components/shared/ExtensionsEditor.vue';

const baseProps = {
  show: true,
  modelValue: { greeting: 'hello', count: 2 },
  entityLabel: 'Item',
  reservedKeys: ['salt:type']
};

describe('ExtensionsEditor.vue', () => {
  it('renders a row per editable extension with values serialized as JSON', () => {
    const wrapper = mount(ExtensionsEditor, { props: baseProps });
    const keys = wrapper.findAll('[data-testid="ext-key"]').map(i => i.element.value);
    const values = wrapper.findAll('[data-testid="ext-value"]').map(i => i.element.value);
    expect(keys).toEqual(expect.arrayContaining(['greeting', 'count']));
    expect(values).toContain('"hello"');
    expect(values).toContain('2');
  });

  it('adds a new empty row', async () => {
    const wrapper = mount(ExtensionsEditor, { props: baseProps });
    await wrapper.find('[data-testid="ext-add"]').trigger('click');
    expect(wrapper.findAll('[data-testid="ext-row"]').length).toBe(3);
  });

  it('removes a row', async () => {
    const wrapper = mount(ExtensionsEditor, { props: baseProps });
    await wrapper.findAll('[data-testid="ext-remove"]')[0].trigger('click');
    expect(wrapper.findAll('[data-testid="ext-row"]').length).toBe(1);
  });

  it('flags invalid JSON and disables Apply', async () => {
    const wrapper = mount(ExtensionsEditor, { props: baseProps });
    const value = wrapper.findAll('[data-testid="ext-value"]')[0];
    await value.setValue('not json');
    expect(wrapper.text()).toContain('Invalid JSON');
    expect(wrapper.find('[data-testid="ext-apply"]').attributes('disabled')).toBeDefined();
  });

  it('rejects a reserved key with an inline error and disables Apply', async () => {
    const wrapper = mount(ExtensionsEditor, { props: baseProps });
    await wrapper.find('[data-testid="ext-add"]').trigger('click');
    const newKey = wrapper.findAll('[data-testid="ext-key"]')[2];
    await newKey.setValue('salt:type');
    expect(wrapper.text()).toContain('Reserved key');
    expect(wrapper.find('[data-testid="ext-apply"]').attributes('disabled')).toBeDefined();
  });

  it('applies parsed values via v-model and closes', async () => {
    const wrapper = mount(ExtensionsEditor, { props: baseProps });
    // edit count value to an array literal
    const countValue = wrapper.findAll('[data-testid="ext-value"]').find(i => i.element.value === '2');
    await countValue.setValue('["a","b"]');
    await wrapper.find('[data-testid="ext-apply"]').trigger('click');
    const events = wrapper.emitted('update:modelValue');
    expect(events).toBeTruthy();
    const applied = events[0][0];
    expect(applied.count).toEqual(['a', 'b']);
    expect(applied.greeting).toBe('hello');
    expect(wrapper.emitted('update:show')[0]).toEqual([false]);
  });

  it('cancel closes without emitting modelValue changes', async () => {
    const wrapper = mount(ExtensionsEditor, { props: baseProps });
    await wrapper.find('[data-testid="ext-cancel"]').trigger('click');
    expect(wrapper.emitted('update:modelValue')).toBeFalsy();
    expect(wrapper.emitted('update:show')[0]).toEqual([false]);
  });
});