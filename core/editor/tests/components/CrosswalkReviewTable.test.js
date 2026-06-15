import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import CrosswalkReviewTable from '@/components/crosswalk/CrosswalkReviewTable.vue';

vi.mock('@/components/crosswalk/CrosswalkReviewRow.vue', () => ({
  default: {
    name: 'CrosswalkReviewRow',
    props: ['row', 'isSelected'],
    template: '<tr><td>{{ row.id }}</td></tr>',
  },
}));

describe('CrosswalkReviewTable', () => {
  const matchedRow = (id) => ({ type: 'matched', id, pair: { id } });

  it('renders all rows', () => {
    const wrapper = mount(CrosswalkReviewTable, {
      props: {
        rows: [matchedRow('pair-1'), matchedRow('pair-2')],
        selectedIds: new Set(),
        loading: false,
      },
    });
    expect(wrapper.findAll('tbody tr')).toHaveLength(2);
  });

  it('renders matched and unmatched rows together', () => {
    const rows = [
      matchedRow('pair-1'),
      matchedRow('pair-2'),
      { type: 'unmatched-origin', id: 'unmatched-origin-uo1', item: { identifier: 'uo1', fullStatement: 'Unmatched Origin 1' } },
      { type: 'unmatched-destination', id: 'unmatched-dest-ud1', item: { identifier: 'ud1', fullStatement: 'Unmatched Dest 1' } },
    ];

    const wrapper = mount(CrosswalkReviewTable, {
      props: {
        rows,
        selectedIds: new Set(),
        loading: false,
      },
    });
    // 2 matched + 1 unmatched origin + 1 unmatched dest = 4 rows
    expect(wrapper.findAll('tbody tr')).toHaveLength(4);
  });

  it('selects all rows when the header checkbox is toggled on', async () => {
    const selectedIds = new Set();
    const rows = [matchedRow('pair-1'), matchedRow('pair-2')];

    const wrapper = mount(CrosswalkReviewTable, {
      props: {
        rows,
        selectedIds,
        loading: false,
      },
    });

    await wrapper.find('thead input[type="checkbox"]').setValue(true);
    expect(selectedIds.has('pair-1')).toBe(true);
    expect(selectedIds.has('pair-2')).toBe(true);
  });
});
