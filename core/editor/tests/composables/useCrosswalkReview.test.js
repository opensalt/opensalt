import { describe, it, expect, vi } from 'vitest';

vi.mock('pinia', () => ({ defineStore: vi.fn(), storeToRefs: vi.fn() }));
vi.mock('../../src/stores/currentDocumentStore.ts', () => ({
  useCurrentDocumentStore: vi.fn(() => ({
    currentDocument: { id: '156', title: 'Crosswalk Framework' },
    currentDocumentAssociations: [
      {
        identifier: 'assoc-1',
        associationType: 'exactMatchOf',
        originNodeURI: { identifier: 'origin-uuid', title: 'Origin Item', uri: 'https://example.com/1' },
        destinationNodeURI: { identifier: 'dest-uuid', title: 'Dest Item', uri: 'https://example.com/2' },
        extensions: { 'crosswalk:confidence': 0.95, 'crosswalk:status': 'pending', 'crosswalk:subtype': 'exact' },
      },
      {
        identifier: 'assoc-2',
        associationType: 'isRelatedTo',
        originNodeURI: { identifier: 'origin-uuid-2', title: 'Origin Item 2', uri: 'https://example.com/3' },
        destinationNodeURI: { identifier: 'dest-uuid-2', title: 'Dest Item 2', uri: 'https://example.com/4' },
        extensions: { 'crosswalk:confidence': 0.80, 'crosswalk:status': 'pending', 'crosswalk:subtype': 'related' },
      },
    ],
  }))
}));

describe('useCrosswalkReview', () => {
  it('loads associations from current document', async () => {
    const { useCrosswalkReview } = await import('../../src/composables/useCrosswalkReview.js');
    const { associations } = useCrosswalkReview();
    expect(associations.value).toHaveLength(2);
  });

  it('filters by confidence range', async () => {
    const { useCrosswalkReview } = await import('../../src/composables/useCrosswalkReview.js');
    const { filters, filteredAssociations } = useCrosswalkReview();
    filters.value.confidenceMin = 0.85;
    filters.value.confidenceMax = 1.0;
    expect(filteredAssociations.value).toHaveLength(1);
    expect(filteredAssociations.value[0].identifier).toBe('assoc-1');
  });

  it('filters by status', async () => {
    const { useCrosswalkReview } = await import('../../src/composables/useCrosswalkReview.js');
    const { filters, filteredAssociations, approveAssociation } = useCrosswalkReview();
    await approveAssociation('assoc-1');
    filters.value.status = 'approved';
    expect(filteredAssociations.value).toHaveLength(1);
    expect(filteredAssociations.value[0].identifier).toBe('assoc-1');
  });

  it('supports bulk approve', async () => {
    const { useCrosswalkReview } = await import('../../src/composables/useCrosswalkReview.js');
    const { filteredAssociations, bulkApprove } = useCrosswalkReview();
    await bulkApprove();
    expect(filteredAssociations.value.every(a => a.extensions['crosswalk:status'] === 'approved')).toBe(true);
  });
});
