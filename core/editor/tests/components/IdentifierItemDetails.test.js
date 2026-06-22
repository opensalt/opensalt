import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import IdentifierItemDetails from '@/components/tree/panels/item-types/IdentifierItemDetails.vue';

const sampleItem = {
  identifier: 'urn:opensalt:identifier:abc123',
  fullStatement: 'A digital object identifier for the parent resource',
  codedNotation: '10.1234/example',
  notes: 'Some notes here',
  extensions: {
    'salt:idType': 'DOI',
  },
};

describe('IdentifierItemDetails.vue', () => {
  it('renders "Item URI" label instead of "Identifier" for the CASE URI field', () => {
    const wrapper = mount(IdentifierItemDetails, {
      props: { item: sampleItem },
    });
    const html = wrapper.html();
    expect(html).toContain('Item URI');
    expect(html).not.toContain('>Identifier<');
  });

  it('renders fields in the expected order using dt elements', () => {
    const wrapper = mount(IdentifierItemDetails, {
      props: { item: sampleItem },
    });
    const dts = wrapper.findAll('dt');
    const labels = dts.map((dt) => dt.text());
    expect(labels).toEqual([
      'Identifier Type:',
      'Description:',
      'Coded Notation:',
      'Notes:',
      'Item URI:',
    ]);
  });

  it('renders Item URI after a horizontal rule at the bottom', () => {
    const wrapper = mount(IdentifierItemDetails, {
      props: { item: sampleItem },
    });
    const hr = wrapper.find('hr');
    const itemUriDt = wrapper.findAll('dt').at(-1);
    expect(hr.exists()).toBe(true);
    expect(itemUriDt.text()).toBe('Item URI:');
    const hrEl = hr.element;
    const itemUriEl = itemUriDt.element;
    const comparison = hrEl.compareDocumentPosition(itemUriEl);
    expect(comparison & Node.DOCUMENT_POSITION_FOLLOWING).toBeTruthy();
  });

  it('renders the identifier value as a link with accessibility text', () => {
    const wrapper = mount(IdentifierItemDetails, {
      props: { item: sampleItem },
    });
    const link = wrapper.find('a[href="/uri/urn:opensalt:identifier:abc123"]');
    expect(link.exists()).toBe(true);
    expect(link.text()).toContain('urn:opensalt:identifier:abc123');
    expect(link.find('.visually-hidden').text()).toBe('(opens in new window)');
  });

  it('renders all expected fields', () => {
    const wrapper = mount(IdentifierItemDetails, {
      props: { item: sampleItem },
    });
    const html = wrapper.html();
    expect(html).toContain('Identifier Type');
    expect(html).toContain('Description');
    expect(html).toContain('Coded Notation');
    expect(html).toContain('Notes');
    expect(html).toContain('Item URI');
  });

  it('shows dash when item has no identifier', () => {
    const noIdItem = { ...sampleItem, identifier: null };
    const wrapper = mount(IdentifierItemDetails, {
      props: { item: noIdItem },
    });
    expect(wrapper.html()).toContain('—');
  });

  it('hides hr when no content fields are present above Item URI', () => {
    const minimalItem = {
      identifier: 'urn:opensalt:identifier:abc123',
    };
    const wrapper = mount(IdentifierItemDetails, {
      props: { item: minimalItem },
    });
    expect(wrapper.find('hr').exists()).toBe(false);
    expect(wrapper.html()).toContain('Item URI');
  });

  it('does not render Identifier Type when salt:idType is absent', () => {
    const noTypeItem = {
      ...sampleItem,
      extensions: {},
    };
    const wrapper = mount(IdentifierItemDetails, {
      props: { item: noTypeItem },
    });
    expect(wrapper.html()).not.toContain('Identifier Type');
    const dts = wrapper.findAll('dt');
    expect(dts[0].text()).toBe('Description:');
  });
});
