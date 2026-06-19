import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import ExtensionDisplayRows from '@/components/tree/panels/ExtensionDisplayRows.vue';

describe('ExtensionDisplayRows.vue', () => {
  it('renders nothing when config is absent', () => {
    const wrapper = mount(ExtensionDisplayRows, {
      props: { extensions: { localCode: 'LC1' } }
    });
    expect(wrapper.find('.extension-display-rows').exists()).toBe(false);
    expect(wrapper.text()).toBe('');
  });

  it('renders nothing when config is an empty object', () => {
    const wrapper = mount(ExtensionDisplayRows, {
      props: { config: {}, extensions: { localCode: 'LC1' } }
    });
    expect(wrapper.find('.extension-display-rows').exists()).toBe(false);
  });

  it('renders nothing when config is not an object', () => {
    const wrapper = mount(ExtensionDisplayRows, {
      props: { config: 'nope', extensions: { localCode: 'LC1' } }
    });
    expect(wrapper.find('.extension-display-rows').exists()).toBe(false);
  });

  it('renders a bold-labeled row for each key present in extensions', () => {
    const wrapper = mount(ExtensionDisplayRows, {
      props: {
        config: { localCode: 'Local Code', subjectArea: 'Subject Area' },
        extensions: { localCode: 'LC1', subjectArea: 'Math' }
      }
    });
    const dts = wrapper.findAll('dt');
    const dds = wrapper.findAll('dd');
    expect(dts).toHaveLength(2);
    expect(dds).toHaveLength(2);
    expect(dts[0].text()).toBe('Local Code');
    expect(dds[0].text()).toBe('LC1');
  });

  it('hides rows for keys missing from extensions', () => {
    const wrapper = mount(ExtensionDisplayRows, {
      props: {
        config: { localCode: 'Local Code', missing: 'Missing' },
        extensions: { localCode: 'LC1' }
      }
    });
    const dds = wrapper.findAll('dd');
    expect(dds).toHaveLength(1);
    expect(dds[0].text()).toBe('LC1');
  });

  it('hides a row when the value is null or undefined', () => {
    const wrapper = mount(ExtensionDisplayRows, {
      props: {
        config: { a: 'A', b: 'B', c: 'C' },
        extensions: { a: 'x', b: null, c: undefined }
      }
    });
    const dds = wrapper.findAll('dd');
    expect(dds).toHaveLength(1);
    expect(dds[0].text()).toBe('x');
  });

  it('serializes arrays as comma-joined values', () => {
    const wrapper = mount(ExtensionDisplayRows, {
      props: {
        config: { tags: 'Tags' },
        extensions: { tags: ['a', 'b', 'c'] }
      }
    });
    expect(wrapper.find('dd').text()).toBe('a, b, c');
  });

  it('serializes objects as JSON', () => {
    const wrapper = mount(ExtensionDisplayRows, {
      props: {
        config: { meta: 'Meta' },
        extensions: { meta: { x: 1 } }
      }
    });
    expect(wrapper.find('dd').text()).toBe('{"x":1}');
  });

  it('serializes booleans and numbers as strings', () => {
    const wrapper = mount(ExtensionDisplayRows, {
      props: {
        config: { flag: 'Flag', count: 'Count' },
        extensions: { flag: true, count: 42 }
      }
    });
    const dds = wrapper.findAll('dd').map(d => d.text());
    expect(dds).toEqual(expect.arrayContaining(['true', '42']));
  });

  it('falls back to the key as label when display name is empty', () => {
    const wrapper = mount(ExtensionDisplayRows, {
      props: {
        config: { localCode: '' },
        extensions: { localCode: 'LC1' }
      }
    });
    expect(wrapper.find('dt').text()).toBe('localCode');
  });
});
