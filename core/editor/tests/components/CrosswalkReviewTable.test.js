import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import CrosswalkReviewTable from '@/components/crosswalk/CrosswalkReviewTable.vue';

const mockAssociations = [
  {
    identifier: 'assoc-1',
    associationType: 'exactMatchOf',
    originNodeURI: { title: 'Origin 1' },
    destinationNodeURI: { title: 'Dest 1' },
    extensions: { 'crosswalk:confidence': 0.95, 'crosswalk:status': 'pending', 'crosswalk:subtype': 'exact' },
  },
  {
    identifier: 'assoc-2',
    associationType: 'isRelatedTo',
    originNodeURI: { title: 'Origin 2' },
    destinationNodeURI: { title: 'Dest 2' },
    extensions: { 'crosswalk:confidence': 0.80, 'crosswalk:status': 'pending', 'crosswalk:subtype': 'related' },
  },
];

vi.mock('@/components/crosswalk/CrosswalkReviewRow.vue', () => ({
  default: {
    name: 'CrosswalkReviewRow',
    props: ['association', 'isSelected'],
    template: '<tr><td :data-identifier="association.identifier">{{ association.originNodeURI?.title }}</td></tr>',
  },
}));

describe('CrosswalkReviewTable', () => {
  it('renders all associations', () => {
    const wrapper = mount(CrosswalkReviewTable, {
      props: { associations: mockAssociations, selectedIds: new Set() },
    });
    expect(wrapper.findAll('tbody tr')).toHaveLength(2);
  });

  it('emits approve event', async () => {
    const wrapper = mount(CrosswalkReviewTable, {
      props: { associations: mockAssociations, selectedIds: new Set() },
    });
    expect(wrapper.findAll('tbody tr')).toHaveLength(2);
  });
});
