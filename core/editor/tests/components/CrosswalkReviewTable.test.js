import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import CrosswalkReviewTable from '@/components/crosswalk/CrosswalkReviewTable.vue';

vi.mock('@/components/crosswalk/CrosswalkReviewRow.vue', () => ({
  default: {
    name: 'CrosswalkReviewRow',
    props: ['pair', 'unmatchedOriginItem', 'unmatchedDestinationItem', 'isSelected'],
    template: '<tr><td>{{ pair?.id || "unmatched" }}</td></tr>',
  },
}));

describe('CrosswalkReviewTable', () => {
  const mockPairs = [
    {
      id: 'pair-1',
      originItem: { identifier: 'o1', fullStatement: 'Origin 1' },
      destinationItem: { identifier: 'd1', fullStatement: 'Dest 1' },
      confidence: 0.95,
      status: 'pending',
      type: 'exactMatchOf',
    },
    {
      id: 'pair-2',
      originItem: { identifier: 'o2', fullStatement: 'Origin 2' },
      destinationItem: { identifier: 'd2', fullStatement: 'Dest 2' },
      confidence: 0.80,
      status: 'pending',
      type: 'isRelatedTo',
    },
  ];

  it('renders all associations', () => {
    const wrapper = mount(CrosswalkReviewTable, {
      props: {
        matchedPairs: mockPairs,
        unmatchedOrigin: [],
        unmatchedDestination: [],
        selectedIds: new Set(),
        loading: false,
      },
    });
    expect(wrapper.findAll('tbody tr')).toHaveLength(2);
  });

  it('renders unmatched items alongside matched', () => {
    const unmatchedOrigin = [
      { identifier: 'uo1', fullStatement: 'Unmatched Origin 1' },
    ];
    const unmatchedDest = [
      { identifier: 'ud1', fullStatement: 'Unmatched Dest 1' },
    ];

    const wrapper = mount(CrosswalkReviewTable, {
      props: {
        matchedPairs: mockPairs,
        unmatchedOrigin,
        unmatchedDestination: unmatchedDest,
        selectedIds: new Set(),
        loading: false,
      },
    });
    // 2 matched + 1 unmatched origin + 1 unmatched dest = 4 rows
    expect(wrapper.findAll('tbody tr')).toHaveLength(4);
  });
});
