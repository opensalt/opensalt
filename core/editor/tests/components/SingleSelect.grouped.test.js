import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import SingleSelect from '@/components/tree/SingleSelect.vue';

const groupedOptions = [
  {
    label: 'State',
    options: [
      { id: 'doc-1', text: 'Alpha Framework' },
      { id: 'doc-2', text: 'Beta Framework' },
    ],
  },
  {
    label: 'Organization',
    options: [
      { id: 'doc-3', text: 'Gamma Framework' },
    ],
  },
];

const flatOptions = [
  { id: 'a', text: 'Option A' },
  { id: 'b', text: 'Option B' },
];

describe('SingleSelect grouped options', () => {
  const mountSelect = (props = {}) =>
    mount(SingleSelect, {
      props: { options: groupedOptions, modelValue: null, allowClear: false, ...props },
      global: { stubs: { teleport: true } },
    });

  it('renders group labels as non-selectable headers between options', async () => {
    const wrapper = mountSelect();
    await wrapper.find('[role="combobox"]').trigger('keydown', { key: 'Enter' });

    const headers = wrapper.findAll('[role="presentation"]');
    expect(headers.map((h) => h.text())).toEqual(['State', 'Organization']);

    const options = wrapper.findAll('[role="option"]');
    expect(options.map((o) => o.text())).toEqual([
      'Alpha Framework',
      'Beta Framework',
      'Gamma Framework',
    ]);
    // Options under a header are indented (optgroup-style)
    expect(options.every((o) => o.classes().includes('singleselect__option--grouped'))).toBe(true);
  });

  it('keeps the whole group when the query matches its label', async () => {
    const wrapper = mountSelect();
    await wrapper.find('[role="combobox"]').trigger('keydown', { key: 'Enter' });
    await wrapper.find('.singleselect__input').setValue('organiz');

    const options = wrapper.findAll('[role="option"]');
    expect(options.map((o) => o.text())).toEqual(['Gamma Framework']);
    expect(wrapper.findAll('[role="presentation"]').map((h) => h.text())).toEqual(['Organization']);
  });

  it('matches options on non-label string fields such as their id', async () => {
    const wrapper = mountSelect();
    await wrapper.find('[role="combobox"]').trigger('keydown', { key: 'Enter' });
    await wrapper.find('.singleselect__input').setValue('doc-2');

    const options = wrapper.findAll('[role="option"]');
    expect(options.map((o) => o.text())).toEqual(['Beta Framework']);
  });

  it('skips group headers when moving keyboard highlight', async () => {
    const wrapper = mountSelect();
    const trigger = wrapper.find('[role="combobox"]');
    await trigger.trigger('keydown', { key: 'Enter' });
    await trigger.trigger('keydown', { key: 'ArrowDown' });

    const firstOptionId = wrapper.findAll('[role="option"]')[0].attributes('id');
    expect(trigger.attributes('aria-activedescendant')).toBe(firstOptionId);

    await trigger.trigger('keydown', { key: 'ArrowDown' });
    const secondOptionId = wrapper.findAll('[role="option"]')[1].attributes('id');
    expect(trigger.attributes('aria-activedescendant')).toBe(secondOptionId);
  });

  it('supports type, arrow, and Enter entirely from the search input', async () => {
    const wrapper = mountSelect();
    await wrapper.find('[role="combobox"]').trigger('keydown', { key: 'Enter' });
    const input = wrapper.find('.singleselect__input');
    await input.setValue('gamma');

    await input.trigger('keydown', { key: 'ArrowDown' });
    const trigger = wrapper.find('[role="combobox"]');
    const firstOptionId = wrapper.findAll('[role="option"]')[0].attributes('id');
    expect(trigger.attributes('aria-activedescendant')).toBe(firstOptionId);

    await input.trigger('keydown', { key: 'Enter' });
    expect(wrapper.emitted('update:modelValue').at(-1)).toEqual(['doc-3']);
    expect(wrapper.find('[role="combobox"]').attributes('aria-expanded')).toBe('false');
  });

  it('picks the first matching option on Enter when nothing is highlighted', async () => {
    const wrapper = mountSelect();
    await wrapper.find('[role="combobox"]').trigger('keydown', { key: 'Enter' });
    const input = wrapper.find('.singleselect__input');
    await input.setValue('beta');
    await input.trigger('keydown', { key: 'Enter' });

    expect(wrapper.emitted('update:modelValue').at(-1)).toEqual(['doc-2']);
  });

  it('announces filtered result counts in a live region', async () => {
    const wrapper = mountSelect();
    await wrapper.find('[role="combobox"]').trigger('keydown', { key: 'Enter' });

    const liveRegion = wrapper.find('[aria-live="polite"]');
    await wrapper.find('.singleselect__input').setValue('framework');
    expect(liveRegion.text()).toBe('3 options available');

    await wrapper.find('.singleselect__input').setValue('zzz');
    expect(liveRegion.text()).toBe('No results found');

    await wrapper.find('.singleselect__input').setValue('');
    expect(liveRegion.text()).toBe('');
  });

  it('names the combobox from an external label via labelledbyId', () => {
    const wrapper = mount(SingleSelect, {
      props: { options: groupedOptions, modelValue: null, labelledbyId: 'my-label' },
      global: { stubs: { teleport: true } },
    });

    const trigger = wrapper.find('[role="combobox"]');
    expect(trigger.attributes('aria-labelledby')).toBe('my-label');
    expect(trigger.attributes('aria-label')).toBeUndefined();
  });

  it('falls back to the placeholder as the accessible name', () => {
    const wrapper = mount(SingleSelect, {
      props: { options: groupedOptions, modelValue: null, placeholder: 'Pick one' },
      global: { stubs: { teleport: true } },
    });

    expect(wrapper.find('[role="combobox"]').attributes('aria-label')).toBe('Pick one');
  });

  it('does not open when disabled but stays focusable for discovery', async () => {
    const wrapper = mount(SingleSelect, {
      props: { options: groupedOptions, modelValue: null, disabled: true },
      global: { stubs: { teleport: true } },
    });

    const trigger = wrapper.find('[role="combobox"]');
    expect(trigger.attributes('aria-disabled')).toBe('true');

    await trigger.trigger('click');
    await trigger.trigger('keydown', { key: 'Enter' });
    expect(trigger.attributes('aria-expanded')).toBe('false');
  });
});

describe('SingleSelect flat options remain supported', () => {
  it('filters and selects flat options as before', async () => {
    const wrapper = mount(SingleSelect, {
      props: { options: flatOptions, modelValue: 'a', allowClear: false },
      global: { stubs: { teleport: true } },
    });

    expect(wrapper.find('.singleselect__selected-value').text()).toBe('Option A');

    await wrapper.find('[role="combobox"]').trigger('keydown', { key: 'Enter' });
    await wrapper.find('.singleselect__input').setValue('b');
    const options = wrapper.findAll('[role="option"]');
    expect(options.map((o) => o.text())).toEqual(['Option B']);
    await options[0].trigger('click');

    expect(wrapper.emitted('update:modelValue').at(-1)).toEqual(['b']);
  });
});
