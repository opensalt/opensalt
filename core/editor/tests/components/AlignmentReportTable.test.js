import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AlignmentReportTable from '@/components/alignmentReport/AlignmentReportTable.vue';

function row(overrides = {}) {
  return {
    identifier: 'A1',
    smartLevel: '1.',
    humanCodingScheme: 'A.1',
    fullStatement: 'Left statement one',
    associationType: 'exactMatchOf',
    matchedHumanCodingScheme: 'B.1',
    matchedFullStatement: 'Right statement one',
    matchedIdentifier: 'B1',
    notes: '',
    diffSegments: [
      { value: 'Left ', added: undefined, removed: undefined },
      { value: 'statement', added: true, removed: undefined },
    ],
    exactMatch: false,
    unmatchedSide: null,
    ...overrides,
  };
}

describe('AlignmentReportTable', () => {
  it('renders rows and highlights exact matches', () => {
    const wrapper = mount(AlignmentReportTable, {
      props: { rows: [row({ exactMatch: true }), row({ identifier: 'A2', fullStatement: 'zzz' })] },
    });
    expect(wrapper.findAll('tbody tr')).toHaveLength(2);
    const exactCell = wrapper.findAll('tbody tr')[0].findAll('td')[4];
    expect(exactCell.classes()).toContain('text-success');
  });

  it('filters rows with the search box', async () => {
    const wrapper = mount(AlignmentReportTable, {
      props: { rows: [row(), row({ identifier: 'A2', fullStatement: 'zzz' })] },
    });
    await wrapper.find('input[type="search"]').setValue('zzz');
    expect(wrapper.findAll('tbody tr')).toHaveLength(1);
  });

  it('paginates with rows-per-page and supports All', async () => {
    const rows = Array.from({ length: 15 }, (_, i) => row({ identifier: 'A' + i }));
    const wrapper = mount(AlignmentReportTable, { props: { rows } });
    expect(wrapper.findAll('tbody tr')).toHaveLength(10);
    await wrapper.find('select').setValue('-1');
    expect(wrapper.findAll('tbody tr')).toHaveLength(15);
    expect(wrapper.find('.text-muted').text()).toBe('1–15 of 15');
  });

  it('pages through results with the pager controls', async () => {
    const rows = Array.from({ length: 25 }, (_, i) => row({ identifier: 'A' + i }));
    const wrapper = mount(AlignmentReportTable, { props: { rows } });
    expect(wrapper.findAll('tbody tr')).toHaveLength(10);
    const pageBox = () => wrapper.find('[data-testid="pager-input"]');
    expect(pageBox().element.value).toBe('1');

    const next = wrapper.find('[data-testid="pager-next"]');
    const prev = wrapper.find('[data-testid="pager-prev"]');
    expect(prev.attributes('disabled')).toBeDefined();

    await next.trigger('click');
    expect(wrapper.findAll('tbody tr')).toHaveLength(10);
    expect(pageBox().element.value).toBe('2');

    await next.trigger('click');
    expect(wrapper.findAll('tbody tr')).toHaveLength(5);
    expect(next.attributes('disabled')).toBeDefined();

    await prev.trigger('click');
    expect(wrapper.findAll('tbody tr')).toHaveLength(10);
    expect(pageBox().element.value).toBe('2');
  });

  it('jumps to a page entered in the pager input', async () => {
    const rows = Array.from({ length: 25 }, (_, i) => row({ identifier: 'A' + i }));
    const wrapper = mount(AlignmentReportTable, { props: { rows } });
    const input = wrapper.find('[data-testid="pager-input"]');
    expect(input.element.value).toBe('1');

    await input.setValue('3');
    await input.trigger('change');
    expect(wrapper.findAll('tbody tr')).toHaveLength(5);
    expect(input.element.value).toBe('3');

    // Out-of-range entries clamp to the nearest valid page.
    await input.setValue('99');
    await input.trigger('change');
    expect(wrapper.findAll('tbody tr')).toHaveLength(5);
    expect(input.element.value).toBe('3');

    // Enter also commits the jump.
    await input.setValue('1');
    await input.trigger('keyup.enter');
    expect(wrapper.findAll('tbody tr')).toHaveLength(10);
    expect(input.element.value).toBe('1');

    // The input stays in sync when navigating with the buttons.
    await wrapper.find('[data-testid="pager-next"]').trigger('click');
    expect(wrapper.findAll('tbody tr')).toHaveLength(10);
    expect(wrapper.find('[data-testid="pager-input"]').element.value).toBe('2');
  });

  it('tints unmatched right rows', () => {
    const wrapper = mount(AlignmentReportTable, {
      props: { rows: [row({ unmatchedSide: 'right', identifier: '', associationType: '-' })] },
    });
    expect(wrapper.findAll('tbody tr')[0].classes()).toContain('table-row-unmatched');
  });

  it('renders diff segments with added/removed styling', () => {
    const wrapper = mount(AlignmentReportTable, { props: { rows: [row()] } });
    expect(wrapper.findAll('.diff-added')).toHaveLength(1);
    expect(wrapper.find('.diff-cell').text()).toContain('Left');
  });
});
